<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'desctription',
        'created_by', //id user pembuat grup
    ];

    public function creator(): BelongsTo{
        return $this->belongsTo(User::class, 'created_by');
    }
    public function allMemberEntries(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
                    ->withPivot('status', 'responded_at', 'approved_by', 'created_at', 'updated_at') // Muat semua kolom pivot yang relevan
                    ->withTimestamps();
    }

    /**
     * Anggota yang sudah disetujui.
     */
    public function approvedMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
                    ->wherePivot('status', 'approved')
                    ->withPivot('status', 'responded_at', 'approved_by')
                    ->withTimestamps();
    }

    /**
     * Permintaan bergabung yang masih pending.
     */
    public function pendingRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
                    ->wherePivot('status', 'pending')
                    ->withPivot('status', 'created_at') // Kapan request dibuat (menggunakan created_at dari pivot)
                    ->withTimestamps(); // withTimestamps akan memuat created_at dan updated_at dari pivot
    }

    /**
     * Anggota yang ditolak.
     */
    public function rejectedMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
                    ->wherePivot('status', 'rejected')
                    ->withPivot('status', 'responded_at', 'approved_by')
                    ->withTimestamps();
    }
    public function projects(): HasMany{
        return $this->hasMany( Project::class);
    }
}
