<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'status',
        'notes',
        'user_id',
    ];

    protected $attributes = ['status' => 'active'];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
