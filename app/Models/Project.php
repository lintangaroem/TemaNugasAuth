<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date', // Casting deadline ke tipe date
    ];

    /**
     * Grup tempat proyek ini berada.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function allMemberEntries(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('status', 'responded_at', 'approved_by', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    /**
     * Anggota yang sudah disetujui untuk proyek ini.
     */
    public function approvedMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->wherePivot('status', 'approved')
            ->withPivot('status', 'responded_at', 'approved_by')
            ->withTimestamps();
    }
    public function pendingRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->wherePivot('status', 'pending')
            ->withPivot('status', 'created_at')
            ->withTimestamps();
    }
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
