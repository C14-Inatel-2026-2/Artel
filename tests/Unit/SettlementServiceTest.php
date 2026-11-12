<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettlementService $service;

    private User $owner;

    private User $joao;

    private User $maria;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SettlementService;
        $this->owner = User::factory()->create(['name' => 'Dono']);
        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);

        $this->group = Group::create([
            'name' => 'Viagem',
            'user_id' => $this->owner->id,
        ]);

        $this->group->members()->attach([
            $this->owner->id,
            $this->joao->id,
            $this->maria->id,
        ]);
    }

    public function test_create_persists_settlement_and_loads_relations(): void
    {
        $settlement = $this->service->create($this->group, [
            'payer_id' => $this->joao->id,
            'payee_id' => $this->maria->id,
            'amount' => 40.00,
        ]);

        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'group_id' => $this->group->id,
            'paid_by' => $this->joao->id,
            'paid_to' => $this->maria->id,
            'amount' => 40.00,
        ]);
        $this->assertTrue($settlement->relationLoaded('payer'));
        $this->assertTrue($settlement->relationLoaded('payee'));
        $this->assertSame('João', $settlement->payer->name);
        $this->assertSame('Maria', $settlement->payee->name);
        $this->assertNotNull($settlement->settled_at);
    }

    public function test_list_for_group_returns_settlements_newest_first(): void
    {
        $older = $this->service->create($this->group, [
            'payer_id' => $this->joao->id,
            'payee_id' => $this->maria->id,
            'amount' => 10.00,
        ]);
        $older->forceFill(['settled_at' => now()->subDay()])->save();

        $newer = $this->service->create($this->group, [
            'payer_id' => $this->maria->id,
            'payee_id' => $this->joao->id,
            'amount' => 25.00,
        ]);
        $newer->forceFill(['settled_at' => now()])->save();

        $listed = $this->service->listForGroup($this->group);

        $this->assertCount(2, $listed);
        $this->assertSame($newer->id, $listed->first()->id);
        $this->assertSame($older->id, $listed->last()->id);
    }

    public function test_list_for_group_returns_empty_when_there_are_no_settlements(): void
    {
        $listed = $this->service->listForGroup($this->group);

        $this->assertTrue($listed->isEmpty());
    }
}
