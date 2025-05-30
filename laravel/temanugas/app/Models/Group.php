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
    //anggota grup
    public function members(): BelongsToMany{
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')->withTimestamps();
    }
    public function projects(): HasMany{
        return $this->hasMany( Project::class);
    }
}
