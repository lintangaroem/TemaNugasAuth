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
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade'); // Todo ini milik proyek mana
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User yang ditugaskan, bisa null. Jika user dihapus, user_id jadi null
            $table->string('title'); // Judul to-do
            $table->text('description')->nullable(); // Deskripsi to-do
            $table->boolean('is_completed')->default(false); // Status selesai atau belum
            $table->date('due_date')->nullable(); // Batas waktu to-do
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
        Schema::dropIfExists('todos');
    }
};
