<?php

namespace Tests\Unit;

use App\Http\Controllers\ExpenseController;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Teste 3 (Com Mock | Positivo): Criação de despesa com sucesso retorna HTTP 201
     */
    public function test_store_creates_expense_and_returns_status_201(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $expense = new Expense;
        $mockService->expects($this->once())
            ->method('createExpense')
            ->willReturn($expense);

        $mockRequest = Mockery::mock(StoreExpenseRequest::class);
        $mockRequest->shouldReceive('validated')
            ->once()
            ->andReturn(['description' => 'Mercado', 'amount' => 80.0, 'paid_by' => 1]);

        $controller = new ExpenseController($mockService);
        $group = new Group;

        // Act
        $response = $controller->store($mockRequest, $group);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Teste 4 (Com Mock | Negativo): Captura erro de negócio da service e retorna HTTP 422
     */
    public function test_store_catches_invalid_argument_exception_and_returns_422(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('createExpense')
            ->willThrowException(new InvalidArgumentException('Participante inválido para divisão.'));

        $mockRequest = Mockery::mock(StoreExpenseRequest::class);
        $mockRequest->shouldReceive('validated')->once()->andReturn([]);

        $controller = new ExpenseController($mockService);
        $group = new Group;

        // Act
        $response = $controller->store($mockRequest, $group);

        // Assert
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Participante inválido para divisão.', $response->getData(true)['message']);
    }
}
