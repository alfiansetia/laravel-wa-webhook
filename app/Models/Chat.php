<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    protected $fillable = [
        'account_id',
        'chat_id',
        'chat_id_alt',
        'user_name',
        'last_message',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
