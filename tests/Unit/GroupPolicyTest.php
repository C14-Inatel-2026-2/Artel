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
        $user = new User();
        $user->id = 1;

        $group = new Group();
        $group->user_id = 1;

        $policy = new GroupPolicy();

        $canUpdate = $policy->update($user, $group);

        $this->assertTrue($canUpdate);
    }
}
