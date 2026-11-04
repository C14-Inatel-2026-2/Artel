<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Grupos que o usuário é dono.
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Grupos dos quais o usuário é membro.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members')->withTimestamps();
    }

    /**
     * Despesas pagas pelo usuário.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'paid_by');
    }

    /**
     * Divisões de despesa atribuídas ao usuário.
     */
    public function expenseSplits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }
}
