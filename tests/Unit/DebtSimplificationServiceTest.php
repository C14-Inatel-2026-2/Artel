<?php

namespace Tests\Unit;

use App\Services\DebtSimplificationService;
use Tests\TestCase;

class DebtSimplificationServiceTest extends TestCase
{
    private DebtSimplificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DebtSimplificationService;
    }

    public function test_collapses_chain_of_debts_into_single_transfer(): void
    {
        // A deve 50 para B e B deve 50 para C → saldos líquidos: A=-50, B=0, C=+50
        $transfers = $this->service->suggestTransfers([
            ['user' => ['id' => 1, 'name' => 'A', 'email' => 'a@test.com'], 'net_balance' => -50.0],
            ['user' => ['id' => 2, 'name' => 'B', 'email' => 'b@test.com'], 'net_balance' => 0.0],
            ['user' => ['id' => 3, 'name' => 'C', 'email' => 'c@test.com'], 'net_balance' => 50.0],
        ]);

        $this->assertCount(1, $transfers);
        $this->assertSame(1, $transfers[0]['from']['id']);
        $this->assertSame(3, $transfers[0]['to']['id']);
        $this->assertSame(50.0, $transfers[0]['amount']);
    }

    public function test_returns_empty_list_when_everyone_is_settled(): void
    {
        $transfers = $this->service->suggestTransfers([
            ['user' => ['id' => 1, 'name' => 'A', 'email' => 'a@test.com'], 'net_balance' => 0.0],
            ['user' => ['id' => 2, 'name' => 'B', 'email' => 'b@test.com'], 'net_balance' => 0.0],
        ]);

        $this->assertSame([], $transfers);
    }

    public function test_excludes_settled_members_and_conserves_balances(): void
    {
        $balances = [
            ['user' => ['id' => 1, 'name' => 'A', 'email' => 'a@test.com'], 'net_balance' => -30.0],
            ['user' => ['id' => 2, 'name' => 'B', 'email' => 'b@test.com'], 'net_balance' => -20.0],
            ['user' => ['id' => 3, 'name' => 'C', 'email' => 'c@test.com'], 'net_balance' => 0.0],
            ['user' => ['id' => 4, 'name' => 'D', 'email' => 'd@test.com'], 'net_balance' => 25.0],
            ['user' => ['id' => 5, 'name' => 'E', 'email' => 'e@test.com'], 'net_balance' => 25.0],
        ];

        $transfers = $this->service->suggestTransfers($balances);

        $paid = [];
        $received = [];
        $involvedIds = [];

        foreach ($transfers as $transfer) {
            $fromId = $transfer['from']['id'];
            $toId = $transfer['to']['id'];
            $involvedIds[$fromId] = true;
            $involvedIds[$toId] = true;
            $paid[$fromId] = ($paid[$fromId] ?? 0) + $transfer['amount'];
            $received[$toId] = ($received[$toId] ?? 0) + $transfer['amount'];
        }

        $this->assertArrayNotHasKey(3, $involvedIds);
        $this->assertEquals(30.0, $paid[1]);
        $this->assertEquals(20.0, $paid[2]);
        $this->assertEquals(25.0, $received[4]);
        $this->assertEquals(25.0, $received[5]);
    }
}
