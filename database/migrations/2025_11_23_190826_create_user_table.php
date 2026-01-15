<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user'); // PK

            $table->enum('role', ['siswa','guru','admin','kep_perpus','kepsek','petugas']);
            $table->string('nama', 50);

            $table->string('username', 50)->unique();
            $table->string('password', 255);

            $table->enum('kelamin', ['pria', 'wanita'])->nullable();

            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->string('telpon', 15)->nullable();
            $table->text('alamat')->nullable();

            $table->string('foto', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
