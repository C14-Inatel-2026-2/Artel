<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseService $service;

    private User $owner;

    private User $joao;

    private User $maria;

    private User $pedro;

    private User $ana;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExpenseService::class);
        $this->owner = User::factory()->create(['name' => 'Dono']);
        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);
        $this->pedro = User::factory()->create(['name' => 'Pedro']);
        $this->ana = User::factory()->create(['name' => 'Ana']);

        $this->group = Group::create([
            'name' => 'Viagem',
            'user_id' => $this->owner->id,
        ]);

        $this->group->members()->attach([
            $this->owner->id,
            $this->joao->id,
            $this->maria->id,
            $this->pedro->id,
            $this->ana->id,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function centsByUser(Expense $expense): array
    {
        return $expense->splits
            ->mapWithKeys(fn ($split) => [$split->user_id => (int) round($split->amount * 100)])
            ->all();
    }

    /**
     * @param  array{balances: list<array{user: array{id: int}}>}  $balance
     * @return array{user: array{id: int, name: string, email: string}, total_paid: float, total_owed: float, net_balance: float, status: string}
     */
    private function balanceOf(array $balance, User $user): array
    {
        return collect($balance['balances'])->firstWhere('user.id', $user->id);
    }

    /**
     * Teste 2 (Sem Mock | Negativo): Criação de despesa em grupo sem membros lança InvalidArgumentException
     */
    public function test_create_expense_fails_when_group_has_no_members(): void
    {
        $service = new ExpenseService;
        $group = new Group;
        $group->setRelation('members', collect([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O grupo não possui participantes para divisão da despesa.');

        $service->createExpense($group, [
            'description' => 'Conta de Luz',
            'amount' => 150.00,
            'paid_by' => 1,
        ]);
    }

    public function test_equal_distribui_centavos_residuais_sem_perder_valor(): void
    {
        $expense = $this->service->createExpense($this->group, [
            'description' => 'Jantar',
            'amount' => 10,
            'paid_by' => $this->owner->id,
            'participants' => [$this->owner->id, $this->joao->id, $this->maria->id],
        ]);

        $cents = $this->centsByUser($expense);

        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $cents,
        );
        $this->assertSame(1000, array_sum($cents));
    }

    public function test_equal_com_participants_da_resto_aos_primeiros(): void
    {
        $expense = $this->service->createExpense($this->group, [
            'description' => 'Café',
            'amount' => 0.10,
            'paid_by' => $this->owner->id,
            'participants' => [$this->maria->id, $this->joao->id, $this->owner->id],
        ]);

        $this->assertSame(
            [$this->maria->id => 4, $this->joao->id => 3, $this->owner->id => 3],
            $this->centsByUser($expense),
        );
    }

    public function test_exact_persiste_valores_informados(): void
    {
        $expense = $this->service->createExpense($this->group, [
            'description' => 'Almoço',
            'amount' => 100,
            'paid_by' => $this->owner->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 70],
                ['user_id' => $this->joao->id, 'amount' => 30],
            ],
        ]);

        $this->assertSame('exact', $expense->split_type);
        $this->assertSame(
            [$this->owner->id => 7000, $this->joao->id => 3000],
            $this->centsByUser($expense),
        );
    }

    public function test_exact_com_soma_diferente_lanca_excecao(): void
    {
        try {
            $this->service->createExpense($this->group, [
                'description' => 'Almoço',
                'amount' => 100,
                'paid_by' => $this->owner->id,
                'split_type' => 'exact',
                'splits' => [
                    ['user_id' => $this->owner->id, 'amount' => 60],
                    ['user_id' => $this->joao->id, 'amount' => 30],
                ],
            ]);
            $this->fail('Era esperada InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'A soma das parcelas (R$ 90,00) é diferente do valor da despesa (R$ 100,00).',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_splits', 0);
    }

    public function test_percentage_fecha_valor_exato(): void
    {
        $expense = $this->service->createExpense($this->group, [
            'description' => 'Taxi',
            'amount' => 10,
            'paid_by' => $this->owner->id,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 33.34],
                ['user_id' => $this->joao->id, 'percentage' => 33.33],
                ['user_id' => $this->maria->id, 'percentage' => 33.33],
            ],
        ]);

        $cents = $this->centsByUser($expense);

        $this->assertSame('percentage', $expense->split_type);
        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $cents,
        );
        $this->assertSame(1000, array_sum($cents));
    }

    public function test_percentage_distribui_resto_pelo_maior_resto(): void
    {
        $expense = $this->service->createExpense($this->group, [
            'description' => 'Água',
            'amount' => 0.05,
            'paid_by' => $this->owner->id,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 50],
                ['user_id' => $this->joao->id, 'percentage' => 30],
                ['user_id' => $this->maria->id, 'percentage' => 20],
            ],
        ]);

        $cents = $this->centsByUser($expense);

        $this->assertSame(5, array_sum($cents));
        $this->assertSame(3, $cents[$this->owner->id]);
    }

    public function test_calculate_balance_considera_quitacoes(): void
    {
        $this->service->createExpense($this->group, [
            'description' => 'Hotel',
            'amount' => 100,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        $before = $this->service->calculateBalance($this->group);

        $this->assertSame(50.0, $this->balanceOf($before, $this->joao)['net_balance']);
        $this->assertSame(-50.0, $this->balanceOf($before, $this->maria)['net_balance']);
        $this->assertSame('a_receber', $this->balanceOf($before, $this->joao)['status']);
        $this->assertSame('a_pagar', $this->balanceOf($before, $this->maria)['status']);

        app(SettlementService::class)->create($this->group, [
            'payer_id' => $this->maria->id,
            'payee_id' => $this->joao->id,
            'amount' => 20,
        ]);

        $partial = $this->service->calculateBalance($this->group);
        $joao = $this->balanceOf($partial, $this->joao);
        $maria = $this->balanceOf($partial, $this->maria);

        // net = paid - owed + settledPaid - settledReceived
        $this->assertSame(100.0, $joao['total_paid']);
        $this->assertSame(50.0, $joao['total_owed']);
        $this->assertSame(30.0, $joao['net_balance']);
        $this->assertSame(0.0, $maria['total_paid']);
        $this->assertSame(50.0, $maria['total_owed']);
        $this->assertSame(-30.0, $maria['net_balance']);

        app(SettlementService::class)->create($this->group, [
            'payer_id' => $this->maria->id,
            'payee_id' => $this->joao->id,
            'amount' => 30,
        ]);

        $after = $this->service->calculateBalance($this->group);

        $this->assertSame(0.0, $this->balanceOf($after, $this->joao)['net_balance']);
        $this->assertSame(0.0, $this->balanceOf($after, $this->maria)['net_balance']);
        $this->assertSame('quitado', $this->balanceOf($after, $this->joao)['status']);
        $this->assertSame('quitado', $this->balanceOf($after, $this->maria)['status']);
        $this->assertSame([], $after['suggested_transfers']);
    }

    public function test_calculate_balance_sugere_transferencias_com_multiplos_devedores_e_credores(): void
    {
        $this->service->createExpense($this->group, [
            'description' => 'Hotel',
            'amount' => 100,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        $this->service->createExpense($this->group, [
            'description' => 'Carro',
            'amount' => 80,
            'paid_by' => $this->pedro->id,
            'participants' => [$this->pedro->id, $this->ana->id],
        ]);

        $balance = $this->service->calculateBalance($this->group);

        $this->assertSame(50.0, $this->balanceOf($balance, $this->joao)['net_balance']);
        $this->assertSame(-50.0, $this->balanceOf($balance, $this->maria)['net_balance']);
        $this->assertSame(40.0, $this->balanceOf($balance, $this->pedro)['net_balance']);
        $this->assertSame(-40.0, $this->balanceOf($balance, $this->ana)['net_balance']);
        $this->assertSame(0.0, $this->balanceOf($balance, $this->owner)['net_balance']);

        $transfers = $balance['suggested_transfers'];

        $this->assertCount(2, $transfers);

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

        $this->assertArrayNotHasKey($this->owner->id, $involvedIds);
        $this->assertEquals(50.0, $paid[$this->maria->id]);
        $this->assertEquals(40.0, $paid[$this->ana->id]);
        $this->assertEquals(50.0, $received[$this->joao->id]);
        $this->assertEquals(40.0, $received[$this->pedro->id]);
        $this->assertEqualsWithDelta(array_sum($paid), array_sum($received), 0.001);
    }
}
