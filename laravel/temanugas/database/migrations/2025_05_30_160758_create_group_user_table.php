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
        Schema::create('group_user', function (Blueprint $table) {
            $table->id(); // Pivot table ID
            $table->foreignId('group_id')->constrained()->onDelete('cascade'); // Foreign key ke tabel groups
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key ke tabel users
            $table->timestamps(); // Kapan user bergabung

            $table->unique(['group_id', 'user_id']); // Mencegah user yang sama bergabung ke grup yang sama berkali-kali
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_user');
    }
};
