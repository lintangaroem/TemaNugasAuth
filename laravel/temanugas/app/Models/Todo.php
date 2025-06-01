<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'user_id', // User yang ditugaskan
        'title',
        'description',
        'is_completed',
        'due_date',
    ];

    protected $casts = [
        'is_completed' => 'boolean', // Casting is_completed ke tipe boolean
        'due_date' => 'date',       // Casting due_date ke tipe date
    ];

    /**
     * Proyek tempat to-do ini berada.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User yang ditugaskan untuk to-do ini.
     */
    public function assignee(): BelongsTo // Menggunakan nama 'assignee' agar lebih jelas
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
