<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->string('id', 64)->primary();
                $table->string('parent_id', 64)->nullable()->index();
                $table->string('type', 32)->default('folder');
                $table->string('name');
                $table->string('color', 32)->nullable()->default('black');
                $table->string('password')->nullable();
                $table->longText('content')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('items', 'password')) {
            Schema::table('items', fn (Blueprint $table) => $table->string('password')->nullable()->after('color'));
        }
    }

    public function down(): void
    {
        // Keep existing audit data when rolling back the Laravel migration.
    }
};
