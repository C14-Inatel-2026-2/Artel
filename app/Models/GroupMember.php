<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $fillable = ['group_id', 'user_id'];

    /**
     * Grupo ao qual o membro pertence.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Usuário que é membro.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
