<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name',
        'waha_session_id',
        'phone_number',
        'base_url',
        'status',
        'api_key',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function messages()
    {
        return $this->hasManyThrough(Message::class, Chat::class);
    }
}
