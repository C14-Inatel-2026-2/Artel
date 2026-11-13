<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddGroupMemberRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;

class GroupMemberController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    /**
     * Lista todos os membros do grupo.
     */
    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $members = $group->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        return response()->json($members, 200);
    }

    /**
     * Adiciona um novo participante ao grupo (via e-mail ou user_id).
     */
    public function store(AddGroupMemberRequest $request, Group $group): JsonResponse
    {
        $user = $request->filled('user_id')
            ? User::findOrFail($request->integer('user_id'))
            : User::where('email', $request->string('email'))->firstOrFail();

        if ($group->hasMember($user)) {
            return response()->json([
                'message' => 'Este usuário já faz parte do grupo.',
            ], 422);
        }

        $group->members()->attach($user->id);

        return response()->json([
            'message' => 'Participante adicionado com sucesso.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Remove um participante do grupo.
     */
    public function destroy(Group $group, User $user): JsonResponse
    {
        $this->authorize('view', $group);

        // O criador/dono não pode ser removido do grupo
        if ($user->id === $group->user_id) {
            return response()->json([
                'message' => 'O criador do grupo não pode ser removido.',
            ], 422);
        }

        $this->authorize('removeMember', [$group, $user]);

        if (! $group->hasMember($user)) {
            return response()->json([
                'message' => 'Este usuário não é participante deste grupo.',
            ], 404);
        }

        if ($this->expenseService->netBalanceForMember($group, $user) !== 0.0) {
            return response()->json([
                'message' => 'Não é possível remover o participante enquanto houver saldo pendente.',
            ], 422);
        }

        if ($group->expenses()->where('paid_by', $user->id)->exists()) {
            return response()->json([
                'message' => 'Não é possível remover o participante que é pagador de uma despesa ativa no grupo.',
            ], 422);
        }

        $group->members()->detach($user->id);

        return response()->json([
            'message' => 'Participante removido do grupo com sucesso.',
        ], 200);
    }
}
