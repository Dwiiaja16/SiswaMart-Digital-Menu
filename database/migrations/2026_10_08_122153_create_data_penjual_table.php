<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_penjual', function (Blueprint $table) {
            $table->id('id_penjual');
            
            $table->string('username');
            $table->foreign('username')->references('username')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->string('nama_lengkap');
            $table->string('nama_kelas');
            $table->string('foto_penjual')->nullable();
            $table->text('alamat')->nullable();
            
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->foreign('created_by_admin_id')->references('user_id')->on('users')->onDelete('set null');

            $table->string('email')->unique();
            $table->string('nomor_whatsapp');

            // Status operasional toko live
            $table->enum('status_toko', ['buka', 'tutup'])->default('buka');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_penjual');
    }
};