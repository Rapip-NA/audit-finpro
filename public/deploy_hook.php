<?php

use Illuminate\Contracts\Console\Kernel;

/**
 * MainYuk! - Automated Self-Contained Deployment Webhook
 * Runs entirely inside PHP 8.4 web process (No CLI version conflicts).
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

$secretKey = 'rapip_deploy_secret_key_12345';
$receivedKey = $_SERVER['HTTP_X_DEPLOY_KEY'] ?? $_REQUEST['token'] ?? '';

if (empty($receivedKey) || ! hash_equals($secretKey, $receivedKey)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Tentukan direktori root Laravel
$rootDir = __DIR__;
if (! file_exists($rootDir.'/artisan') && file_exists(dirname($rootDir).'/artisan')) {
    $rootDir = dirname($rootDir);
}

$action = $_REQUEST['action'] ?? (isset($_GET['ping']) ? 'ping' : 'deploy');

// 1. Health check (ping)
if ($action === 'ping') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Deploy webhook is ready.',
        'php_web_version' => PHP_VERSION,
        'root_dir' => $rootDir,
        'has_env' => file_exists($rootDir.'/.env'),
        'server_time' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

$logs = [];

// 2. Download dan ekstrak kode terbaru dari GitHub
try {
    $zipUrl = 'https://github.com/Rapip-NA/audit-finpro/zip/refs/heads/main';
    $tmpZip = $rootDir.'/temp_update.zip';

    // Download via curl dengan User-Agent agar tidak ditolak GitHub
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MainYuk-Deploy-Bot/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $zipData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $zipData) {
        file_put_contents($tmpZip, $zipData);
        $logs[] = 'Downloaded zip: '.strlen($zipData).' bytes';

        // Ekstrak zip menggunakan ZipArchive bawaan PHP
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($tmpZip) === true) {
                // Ekstrak ke folder sementara
                $extractTo = $rootDir.'/temp_extract';
                @mkdir($extractTo, 0775, true);
                $zip->extractTo($extractTo);
                $zip->close();
                @unlink($tmpZip);

                $extractedFolder = $extractTo.'/NAMA_REPO-main';
                if (is_dir($extractedFolder)) {
                    // Salin rekursif semua file ke rootDir (kecuali .env)
                    $copyCount = copyRecursiveExcludingEnv($extractedFolder, $rootDir);
                    $logs[] = "Extracted and copied $copyCount files from Game-main";

                    // Hapus folder sementara
                    deleteRecursive($extractTo);
                }
            } else {
                $logs[] = 'Failed to open zip with ZipArchive';
            }
        } else {
            // Fallback ke command unzip
            exec('unzip -o -q '.escapeshellarg($tmpZip).' -d '.escapeshellarg($rootDir).' 2>&1', $logs);
            exec('cp -rf '.escapeshellarg($rootDir.'/Game-main/').'* '.escapeshellarg($rootDir).'/ 2>&1', $logs);
            exec('rm -rf '.escapeshellarg($tmpZip).' '.escapeshellarg($rootDir.'/Game-main'), $logs);
        }
    } else {
        $logs[] = "Failed to download zip from GitHub (HTTP $httpCode)";
    }
} catch (Throwable $e) {
    $logs[] = 'Download/Extract Error: '.$e->getMessage();
}

// 3. Pastikan .env ada dan memiliki APP_KEY
if (! file_exists($rootDir.'/.env')) {
    if (file_exists($rootDir.'/.env.example')) {
        copy($rootDir.'/.env.example', $rootDir.'/.env');
        $logs[] = 'Created .env from .env.example';
    }
}

// Cek apakah APP_KEY ada di .env, jika belum buat langsung
$envFile = $rootDir.'/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (! str_contains($envContent, 'APP_KEY=base64:') && ! preg_match('/^APP_KEY=.+$/m', $envContent)) {
        $newKey = 'base64:'.base64_encode(random_bytes(32));
        if (str_contains($envContent, 'APP_KEY=')) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$newKey, $envContent);
        } else {
            $envContent .= "\nAPP_KEY=".$newKey."\n";
        }
        file_put_contents($envFile, $envContent);
        $logs[] = 'Generated APP_KEY directly in .env';
    }
}

// 4. Bersihkan Cache Laravel Langsung dari File (Tanpa bergantung pada CLI)
@unlink($rootDir.'/bootstrap/cache/config.php');
@unlink($rootDir.'/bootstrap/cache/routes-v7.php');
@unlink($rootDir.'/bootstrap/cache/packages.php');
@unlink($rootDir.'/bootstrap/cache/services.php');

$viewFiles = glob($rootDir.'/storage/framework/views/*.php') ?: [];
foreach ($viewFiles as $vf) {
    @unlink($vf);
}
$logs[] = 'Cleared config cache, route cache, and '.count($viewFiles).' compiled view files';

// 5. Pastikan folder storage ada dan writable
@mkdir($rootDir.'/storage/framework/sessions', 0775, true);
@mkdir($rootDir.'/storage/framework/views', 0775, true);
@mkdir($rootDir.'/storage/framework/cache/data', 0775, true);
@mkdir($rootDir.'/storage/logs', 0775, true);
@mkdir($rootDir.'/bootstrap/cache', 0775, true);
@chmod($rootDir.'/storage', 0775);
@chmod($rootDir.'/bootstrap/cache', 0775);

// 6. Jalankan Migrasi Database lewat PHP Artisan internal (jika autoload ada)
if (file_exists($rootDir.'/vendor/autoload.php') && file_exists($rootDir.'/bootstrap/app.php')) {
    try {
        require_once $rootDir.'/vendor/autoload.php';
        $app = require_once $rootDir.'/bootstrap/app.php';
        $kernel = $app->make(Kernel::class);
        $kernel->call('migrate', ['--force' => true]);
        $logs[] = 'Migrate output: '.trim($kernel->output());
    } catch (Throwable $e) {
        $logs[] = 'Artisan migration note: '.$e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment selesai & cache berhasil diperbarui!',
    'php_version' => PHP_VERSION,
    'logs' => $logs,
]);

/**
 * Salin rekursif folder tanpa menimpa .env
 */
function copyRecursiveExcludingEnv($src, $dst): int
{
    $dir = opendir($src);
    @mkdir($dst, 0775, true);
    $count = 0;
    while (false !== ($file = readdir($dir))) {
        if ($file !== '.' && $file !== '..') {
            // JANGAN PERNAH menimpa file .env milik server!
            if ($file === '.env' || str_starts_with($file, '.env.')) {
                continue;
            }
            if (is_dir($src.'/'.$file)) {
                $count += copyRecursiveExcludingEnv($src.'/'.$file, $dst.'/'.$file);
            } else {
                copy($src.'/'.$file, $dst.'/'.$file);
                $count++;
            }
        }
    }
    closedir($dir);

    return $count;
}

/**
 * Hapus folder rekursif
 */
function deleteRecursive($target): void
{
    if (is_dir($target)) {
        $files = glob($target.'*', GLOB_MARK);
        foreach ($files as $file) {
            deleteRecursive($file);
        }
        @rmdir($target);
    } elseif (is_file($target)) {
        @unlink($target);
    }
}
