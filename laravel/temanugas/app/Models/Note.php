<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'user_id', // User yang membuat catatan
        'title',
        'content',
    ];

    /**
     * Proyek tempat catatan ini berada.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User yang membuat catatan ini.
     */
    public function creator(): BelongsTo // Menggunakan nama 'creator' agar lebih jelas
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
