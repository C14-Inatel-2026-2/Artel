<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GroupPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = Group::query()
            ->where('user_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->with(['owner:id,name', 'members:id,name'])
            ->withCount(['members', 'expenses'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('groups.index', ['groups' => $groups]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $group = Group::create([
                'name' => $request->validated('name'),
                'user_id' => $user->id,
            ]);

            $group->members()->attach($user->id);
        });

        return redirect()
            ->route('web.groups.index')
            ->with('status', 'Grupo criado com sucesso.');
    }

    public function show(Group $group): View
    {
        Gate::authorize('view', $group);

        $group->load(['owner:id,name,email', 'members:id,name,email'])
            ->loadCount('expenses');

        return view('groups.show', ['group' => $group]);
    }
}
