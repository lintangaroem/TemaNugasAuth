<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id(); // Kolom id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('name'); // Nama grup
            $table->text('description')->nullable(); // Deskripsi grup, bisa kosong
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // User yang membuat grup, foreign key ke tabel users
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
};
