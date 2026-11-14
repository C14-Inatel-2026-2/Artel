<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = ['group_id', 'paid_by', 'paid_to', 'amount', 'settled_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * Grupo do acerto.
     *
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Usuário que pagou o acerto (quitou a dívida).
     *
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Usuário que recebeu o acerto.
     *
     * @return BelongsTo<User, $this>
     */
    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_to');
    }
}
