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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            
            // Password dibuat nullable (opsional untuk login via Google)
            $table->string('password')->nullable(); 
            
            $table->string('role')->default('penjual'); // 'penjual' atau 'admin'
            $table->string('whatsapp_number')->nullable();
            
            // Kolom pendukung login Google & status suspend
            $table->string('google_id')->nullable();
            $table->enum('login_method', ['google', 'manual'])->default('manual');
            $table->boolean('is_suspended')->default(false);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};