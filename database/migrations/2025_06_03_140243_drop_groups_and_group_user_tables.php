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
        // Debugging / Cleanup sebelumnya (dari diskusi 'projects' table)
        // Kita telah mengkonfirmasi tidak ada group_id di projects, jadi baris ini tidak perlu ada di up()
        // dan saya hapus di jawaban sebelumnya. Jika masih ada di file Anda, hapus saja.
        /*
        if (Schema::hasColumn('projects', 'group_id')) {
            Schema::table('projects', function (Blueprint $table) {
                try {
                    $table->dropForeign(['group_id']);
                } catch (\Exception $e) {
                    // Log error if needed, but continue
                }
                $table->dropColumn('group_id');
            });
        }
        */

        // LANGKAH PENTING 1: Drop foreign key dari tabel 'group_user'
        // Tabel 'group_user' memiliki foreign key ke 'groups' dan 'users'.
        // Anda harus menghapus foreign key 'group_id' dari 'group_user' sebelum menghapus tabel 'groups'.
        if (Schema::hasTable('group_user')) { // Pastikan tabel ada sebelum mencoba memodifikasinya
            Schema::table('group_user', function (Blueprint $table) {
                // Drop foreign key yang mereferensikan 'groups'
                // Laravel secara default akan mencari nama foreign key: group_user_group_id_foreign
                $table->dropForeign(['group_id']); 
                
                // Jika Anda juga ingin menghapus foreign key user_id dari group_user (jika ada)
                // $table->dropForeign(['user_id']); 
            });
        }
        
        // LANGKAH PANTIING 2: Sekarang Anda bisa menghapus tabel 'groups'
        // Urutan: Child table (group_user) foreign key dulu, baru Parent table (groups)
        Schema::dropIfExists('groups');
        
        // LANGKAH PENTING 3: Hapus tabel 'group_user'
        // Ini harus setelah drop foreign key di Langkah 1 jika tidak ada foreign key lain.
        Schema::dropIfExists('group_user');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // PENTING: Jika Anda ingin bisa me-rollback migrasi ini, Anda harus
        // membuat ulang tabel 'groups' dan 'group_user' di sini (metode down()).
        // Contoh:
        // Schema::create('groups', function (Blueprint $table) {
        //     $table->id();
        //     // Tambahkan kolom-kolom lain yang ada di tabel groups sebelumnya
        //     $table->timestamps();
        // });

        // Schema::create('group_user', function (Blueprint $table) {
        //     $table->foreignId('group_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');
        //     $table->primary(['group_id', 'user_id']);
        // });

        // Jika Anda juga menghapus kolom group_id dari projects, Anda harus menambahkannya kembali di sini.
        // Schema::table('projects', function (Blueprint $table) {
        //     $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null'); // Sesuaikan dengan definisi asli Anda
        // });
    }
};