<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
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
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Daftar to-do dalam proyek ini.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Catatan-catatan dalam proyek ini.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
