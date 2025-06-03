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
        'title',
        'is_completed',
        'created_by_user_id', // Jika Anda menambahkannya
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi ke user yang membuat todo (jika ada kolom created_by_user_id)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

}
