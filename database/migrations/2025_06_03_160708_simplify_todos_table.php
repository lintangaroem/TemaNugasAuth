<?php
// File: database/migrations/xxxx_xx_xx_xxxxxx_simplify_todos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // Hapus kolom yang tidak diperlukan jika ada
            if (Schema::hasColumn('todos', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('todos', 'user_id')) {
                $table->dropForeign(['user_id']); 
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('todos', 'due_date')) {
                $table->dropColumn('due_date');
            }

            // Tambah kolom created_by_user_id jika belum ada dan diinginkan
            if (!Schema::hasColumn('todos', 'created_by_user_id')) {
                 $table->foreignId('created_by_user_id')->nullable()->after('is_completed')->constrained('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // Tambahkan kembali kolom jika di-rollback (sesuaikan tipe datanya)
            if (!Schema::hasColumn('todos', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('todos', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('todos', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (Schema::hasColumn('todos', 'created_by_user_id')) {
                // $table->dropForeign(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};
