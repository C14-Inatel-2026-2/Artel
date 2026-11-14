<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteGroupMemberRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MemberPageController extends Controller
{
    public function store(InviteGroupMemberRequest $request, Group $group): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        if ($group->hasMember($user)) {
            return back()->withInput()->withErrors(['email' => 'Este usuário já faz parte do grupo.']);
        }

        $group->members()->attach($user->id);

        return redirect()
            ->route('web.groups.show', $group)
            ->with('status', $user->name.' foi adicionado ao grupo.');
    }
}
