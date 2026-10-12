<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
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
