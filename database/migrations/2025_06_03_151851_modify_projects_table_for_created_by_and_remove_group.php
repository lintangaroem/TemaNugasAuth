<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Tambah kolom created_by (pastikan tabel users sudah ada)
            // Jika Anda belum bisa menambahkan foreign key karena data yang ada,
            // Anda bisa tambahkan kolomnya dulu, isi datanya, baru tambahkan foreign key di migrasi lain.
            // Untuk sekarang, kita asumsikan bisa langsung.
            if (!Schema::hasColumn('projects', 'created_by')) { // Cek jika kolom belum ada
                $table->foreignId('created_by')->after('id')->nullable()->constrained('users')->onDelete('cascade');
            }

            // Hapus kolom group_id jika ada
            if (Schema::hasColumn('projects', 'group_id')) {
                // Hapus foreign key dulu jika ada sebelum menghapus kolom
                // Nama constraint bisa berbeda, cek di database Anda: e.g., projects_group_id_foreign
                // $table->dropForeign(['group_id']); // Atau $table->dropForeign('nama_constraint_foreign_key');
                $table->dropColumn('group_id');
            }
        });

        // Jika Anda menambahkan created_by sebagai nullable di atas karena data yang ada,
        // Anda mungkin perlu mengupdate data yang ada agar created_by tidak null sebelum membuatnya not nullable.
        // Misalnya: DB::table('projects')->whereNull('created_by')->update(['created_by' => 1]); // Ganti 1 dengan user ID default
        // Kemudian buat migrasi lain untuk mengubah created_by menjadi not nullable:
        // Schema::table('projects', function (Blueprint $table) {
        //     $table->unsignedBigInteger('created_by')->nullable(false)->change();
        // });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'created_by')) {
                // $table->dropForeign(['created_by']); // Jika foreign key ditambahkan
                $table->dropColumn('created_by');
            }

            // Tambahkan kembali kolom group_id jika di-rollback (sesuaikan tipenya)
            if (!Schema::hasColumn('projects', 'group_id')) {
                // $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade');
            }
        });
    }
};