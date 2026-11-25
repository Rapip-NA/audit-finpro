# Audit Final Project

Workspace audit berbasis Laravel 13 dan Livewire 4. Folder, catatan, file HTML, login admin, dan proteksi folder diproses oleh Laravel. Navigasi beranda, folder, file, dan login memakai wire:navigate. Daftar item memakai wire:poll.5s, sehingga perubahan dari pengguna lain terlihat dalam sekitar lima detik tanpa memuat ulang halaman.

## Menjalankan lokal

Persyaratan: PHP 8.3+, Composer, Node.js, dan npm.

~~~powershell
composer install
npm install
npm run build
php artisan migrate
php artisan serve
~~~

Buka http://localhost:8000. Konfigurasi .env lokal sudah memakai SQLite dan memiliki admin yang dimigrasikan dari aplikasi lama. Untuk instalasi baru, salin .env.example menjadi .env, jalankan php artisan key:generate, lalu isi ADMIN_PASSWORD_HASH dengan hash bcrypt. Username admin diatur melalui ADMIN_USERNAME.

## Memakai database MySQL lama

Konfigurasi MySQL lama disimpan secara lokal dalam .env.mysql.local (diabaikan Git). Untuk deployment, salin pengaturan DB_* dan admin dari profil itu ke .env server, lalu jalankan:

~~~powershell
php artisan migrate --force
~~~

Migrasi items mempertahankan tabel dan data yang sudah ada. Password folder lama tetap bisa dibuka; password folder baru disimpan sebagai hash. Atur document root web server ke folder public, bukan akar proyek. File HTML lama dan skema SQL disimpan di legacy/ sebagai referensi.

## Pemeriksaan

~~~powershell
php artisan test
~~~
