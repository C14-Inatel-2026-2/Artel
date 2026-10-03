<?php

namespace Tests\Unit;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    /**
     * Teste 1 (Sem Mock | Positivo): Regras de requisição de despesa obrigatórias e valores mínimos
     */
    public function test_store_expense_request_contains_required_rules(): void
    {
        // Arrange
        $request = new StoreExpenseRequest;

        // Act
        $rules = $request->rules();

        // Assert
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('paid_by', $rules);
        $this->assertContains('required', $rules['amount']);
        $this->assertContains('min:0.01', $rules['amount']);
    }

    /**
     * Teste 2 (Sem Mock | Negativo): Criação de despesa em grupo sem membros lança InvalidArgumentException
     */
    public function test_create_expense_fails_when_group_has_no_members(): void
    {
        // Arrange
        $service = new ExpenseService;
        $group = new Group;
        $group->setRelation('members', collect([])); // Coleção vazia de membros em memória

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O grupo não possui participantes para divisão da despesa.');

        $service->createExpense($group, [
            'description' => 'Conta de Luz',
            'amount' => 150.00,
            'paid_by' => 1,
        ]);
    }
}
