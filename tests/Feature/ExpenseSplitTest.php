<?php

namespace Tests\Feature;

use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExpenseSplitTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $joao;

    private User $maria;

    private User $outsider;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Dono']);
        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);
        $this->outsider = User::factory()->create(['name' => 'Fora do grupo']);

        $this->group = Group::create(['name' => 'Viagem', 'user_id' => $this->owner->id]);
        $this->group->members()->attach([$this->owner->id, $this->joao->id, $this->maria->id]);
    }

    private function storeExpense(array $overrides = []): TestResponse
    {
        return $this->postJson("/api/groups/{$this->group->id}/expenses", array_merge([
            'description' => 'Jantar',
            'amount' => 100,
            'paid_by' => $this->owner->id,
        ], $overrides));
    }

    private function centsByUser(TestResponse $response): array
    {
        return collect($response->json('splits'))
            ->mapWithKeys(fn (array $split) => [$split['user_id'] => (int) round($split['amount'] * 100)])
            ->all();
    }

    public function test_equal_com_participantes_divide_apenas_entre_os_escolhidos(): void
    {
        $response = $this->storeExpense([
            'split_type' => 'equal',
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        $response->assertCreated()->assertJsonPath('split_type', 'equal');
        $this->assertSame([$this->joao->id => 5000, $this->maria->id => 5000], $this->centsByUser($response));
    }

    public function test_equal_sem_participantes_divide_entre_todos_sem_perder_centavos(): void
    {
        $response = $this->storeExpense(['amount' => 10]);

        $response->assertCreated()->assertJsonPath('split_type', 'equal');
        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $this->centsByUser($response),
        );
        $this->assertSame(1000, array_sum($this->centsByUser($response)));
    }

    public function test_equal_com_participantes_distribui_o_resto_entre_os_primeiros(): void
    {
        $response = $this->storeExpense([
            'amount' => 0.10,
            'participants' => [$this->maria->id, $this->joao->id, $this->owner->id],
        ]);

        $response->assertCreated();
        $this->assertSame(
            [$this->maria->id => 4, $this->joao->id => 3, $this->owner->id => 3],
            $this->centsByUser($response),
        );
    }

    public function test_exact_grava_os_valores_informados(): void
    {
        $response = $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 70],
                ['user_id' => $this->joao->id, 'amount' => 30],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('split_type', 'exact');
        $this->assertSame([$this->owner->id => 7000, $this->joao->id => 3000], $this->centsByUser($response));
    }

    public function test_exact_com_soma_diferente_do_total_retorna_422_e_nao_grava(): void
    {
        $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 60],
                ['user_id' => $this->joao->id, 'amount' => 30],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A soma das parcelas (R$ 90,00) é diferente do valor da despesa (R$ 100,00).');

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_splits', 0);
    }

    public function test_exact_sem_parcelas_retorna_422(): void
    {
        $this->storeExpense(['split_type' => 'exact'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['splits']);
    }

    public function test_percentage_com_100_por_cento_fecha_o_valor_exato(): void
    {
        $response = $this->storeExpense([
            'amount' => 10,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 33.34],
                ['user_id' => $this->joao->id, 'percentage' => 33.33],
                ['user_id' => $this->maria->id, 'percentage' => 33.33],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('split_type', 'percentage');
        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $this->centsByUser($response),
        );
        $this->assertSame(1000, array_sum($this->centsByUser($response)));
    }

    public function test_percentage_distribui_o_resto_pelo_maior_resto(): void
    {
        $response = $this->storeExpense([
            'amount' => 0.05,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 50],
                ['user_id' => $this->joao->id, 'percentage' => 30],
                ['user_id' => $this->maria->id, 'percentage' => 20],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(5, array_sum($this->centsByUser($response)));
        $this->assertSame(3, $this->centsByUser($response)[$this->owner->id]);
    }

    public function test_percentage_com_soma_diferente_de_100_retorna_422_e_nao_grava(): void
    {
        $this->storeExpense([
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 60],
                ['user_id' => $this->joao->id, 'percentage' => 39],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A soma dos percentuais (99,00%) deve ser exatamente 100%.');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_participante_fora_do_grupo_retorna_422_no_modo_equal(): void
    {
        $this->storeExpense(['participants' => [$this->joao->id, $this->outsider->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants.1']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_usuario_fora_do_grupo_nas_parcelas_retorna_422_em_exact_e_percentage(): void
    {
        $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 50],
                ['user_id' => $this->outsider->id, 'amount' => 50],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['splits.1.user_id']);

        $this->storeExpense([
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 50],
                ['user_id' => $this->outsider->id, 'percentage' => 50],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['splits.1.user_id']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_tipo_de_divisao_invalido_retorna_422(): void
    {
        $this->storeExpense(['split_type' => 'aleatorio'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['split_type']);
    }

    public function test_usuario_repetido_nas_parcelas_retorna_422(): void
    {
        $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->joao->id, 'amount' => 50],
                ['user_id' => $this->joao->id, 'amount' => 50],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['splits.0.user_id', 'splits.1.user_id']);
    }

    public function test_valor_zero_ou_percentual_acima_de_100_retorna_422(): void
    {
        $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [['user_id' => $this->joao->id, 'amount' => 0]],
        ])->assertStatus(422)->assertJsonValidationErrors(['splits.0.amount']);

        $this->storeExpense([
            'split_type' => 'percentage',
            'splits' => [['user_id' => $this->joao->id, 'percentage' => 101]],
        ])->assertStatus(422)->assertJsonValidationErrors(['splits.0.percentage']);
    }

    public function test_saldos_refletem_a_divisao_personalizada(): void
    {
        $this->storeExpense([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 70],
                ['user_id' => $this->joao->id, 'amount' => 30],
            ],
        ])->assertCreated();

        $balances = collect($this->getJson("/api/groups/{$this->group->id}/balances")->json('balances'))
            ->mapWithKeys(fn (array $balance) => [$balance['user']['id'] => $balance['net_balance']]);

        $this->assertEquals(30.0, $balances[$this->owner->id]);
        $this->assertEquals(-30.0, $balances[$this->joao->id]);
        $this->assertEquals(0.0, $balances[$this->maria->id]);
        $this->assertSame(2, ExpenseSplit::count());
    }
}
