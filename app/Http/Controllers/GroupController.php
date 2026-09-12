<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Retorna os grupos dos quais o usuário autenticado participa.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? User::first();

        if (! $user) {
            return response()->json([], 200);
        }

        $groups = Group::query()
            ->where('user_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->with(['owner:id,name,email', 'members:id,name,email'])
            ->withCount(['members', 'expenses'])
            ->latest()
            ->get();

        return response()->json($groups, 200);
    }

    /**
     * Cria um novo grupo e adiciona o criador automaticamente como membro.
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        $user = $request->user() ?? User::first();

        if (! $user) {
            return response()->json(['message' => 'Nenhum usuário autenticado para criar o grupo.'], 401);
        }

        $group = DB::transaction(function () use ($request, $user) {
            $group = Group::create([
                'name' => $request->validated('name'),
                'user_id' => $user->id,
            ]);

            // O criador do grupo é automaticamente inserido como participante
            $group->members()->attach($user->id);

            return $group;
        });

        return response()->json(
            $group->load(['owner:id,name,email', 'members:id,name,email']),
            201
        );
    }

    /**
     * Exibe os detalhes de um grupo, seus membros e histórico de despesas.
     */
    public function show(Group $group): JsonResponse
    {
        $group->load([
            'owner:id,name,email',
            'members:id,name,email',
            'expenses.payer:id,name,email',
        ]);

        return response()->json($group, 200);
    }

    /**
     * Atualiza o nome do grupo.
     */
    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $group->update($request->validated());

        return response()->json(
            $group->fresh(['owner:id,name,email', 'members:id,name,email']),
            200
        );
    }

    /**
     * Exclui um grupo (as relações no banco possuem cascadeOnDelete).
     */
    public function destroy(Group $group): JsonResponse
    {
        $group->delete();

        return response()->json([
            'message' => 'Grupo excluído com sucesso.',
        ], 200);
    }
}
