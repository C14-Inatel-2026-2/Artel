<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\User;
use App\Policies\GroupPolicy;
use PHPUnit\Framework\TestCase;

class GroupPolicyTest extends TestCase
{
    public function test_owner_can_update_group(): void
    {
        $user = new User;
        $user->id = 1;

        $group = new Group;
        $group->user_id = 1;

        $policy = new GroupPolicy;

        $canUpdate = $policy->update($user, $group);

        $this->assertTrue($canUpdate);
    }

    public function test_non_owner_cannot_update_group(): void
    {
        $user = new User;
        $user->id = 2;

        $group = new Group;
        $group->user_id = 1;

        $policy = new GroupPolicy;

        $canUpdate = $policy->update($user, $group);

        $this->assertFalse($canUpdate);
    }

    public function test_member_can_view_group(): void
    {
        $user = new User;
        $user->id = 2;

        $groupMock = $this->createPartialMock(Group::class, ['hasMember']);
        $groupMock->user_id = 1;

        $groupMock->expects($this->once())
            ->method('hasMember')
            ->with($user)
            ->willReturn(true);

        $policy = new GroupPolicy;

        $canView = $policy->view($user, $groupMock);

        $this->assertTrue($canView);
    }

    public function test_non_member_cannot_view_group(): void
    {
        $user = new User;
        $user->id = 2;

        $groupMock = $this->createPartialMock(Group::class, ['hasMember']);
        $groupMock->user_id = 1;

        $groupMock->expects($this->once())
            ->method('hasMember')
            ->with($user)
            ->willReturn(false);

        $policy = new GroupPolicy;

        $canView = $policy->view($user, $groupMock);

        $this->assertFalse($canView);
    }
}
