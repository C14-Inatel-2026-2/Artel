<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Group $group): bool
    {
        return $group->user_id === $user->id 
            || $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->user_id === $user->id;
    }

    /**
     * Determine whether the user can add members to the group.
     */
    public function addMember(User $user, Group $group): bool
    {
        return $group->user_id === $user->id 
            || $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can remove a member from the group.
     */
    public function removeMember(User $user, Group $group, User $member): bool
    {
        // Dono não pode ser removido do próprio grupo
        if ($member->id === $group->user_id) {
            return false;
        }

        // Dono do grupo pode remover qualquer membro OU o próprio membro pode sair
        return $group->user_id === $user->id || $user->id === $member->id;
    }
}
