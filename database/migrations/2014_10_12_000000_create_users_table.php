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
            $table->string('matric_no')->nullable()->unique();
            $table->string('faculty')->nullable();
            $table->string('department')->nullable();
            $table->string('course')->nullable();
            $table->string('email')->unique();
            $table->text('bio')->nullable();
            $table->longText('profile_photo')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('role')->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
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
