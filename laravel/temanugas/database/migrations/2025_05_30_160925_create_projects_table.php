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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade'); // Proyek milik grup mana
            $table->string('name'); // Nama proyek
            $table->text('description')->nullable(); // Deskripsi proyek
            $table->date('deadline')->nullable(); // Batas waktu proyek
            $table->string('status', 50)->default('pending'); // Status proyek: pending, in_progress, completed, dll.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
