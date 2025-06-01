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
        Schema::table('group_user', function (Blueprint $table) {
            // Kolom status: 'pending', 'approved', 'rejected'
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('user_id');
            // Kapan permintaan dibuat (awalnya adalah created_at, tapi ini lebih eksplisit untuk permintaan)
            // $table->timestamp('requested_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->after('status'); // Jika ingin timestamp khusus request
            // Untuk created_at dan updated_at yang sudah ada bisa dianggap sebagai requested_at dan responded_at secara implisit
            // Atau kita bisa tambahkan kolom spesifik:
            $table->timestamp('responded_at')->nullable()->after('updated_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'responded_at', 'approved_by']);
        });
    }
};
