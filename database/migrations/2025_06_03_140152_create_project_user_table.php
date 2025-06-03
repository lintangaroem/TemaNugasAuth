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
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // User yang menyetujui (bisa jadi pembuat proyek)
            $table->timestamps(); // created_at akan menjadi requested_at

            $table->unique(['project_id', 'user_id']); // User hanya bisa request/join sekali ke proyek yang sama
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_user');
    }
};
