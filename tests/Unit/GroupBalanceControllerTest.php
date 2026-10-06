<?php

namespace Tests\Unit;

use App\Http\Controllers\GroupBalanceController;
use App\Models\Group;
use App\Services\ExpenseService;
use InvalidArgumentException;
use Tests\TestCase;

class GroupBalanceControllerTest extends TestCase
{
    /**
     * Teste 3 (Com Mock | Positivo): Consulta de balanço com sucesso retornando status 200
     */
    public function test_balance_index_returns_balance_data_successfully(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('calculateBalance')
            ->willReturn([
                'group_id' => 1,
                'group_name' => 'Churrasco de Sexta',
                'balances' => [],
            ]);

        $controller = new GroupBalanceController($mockService);
        $group = new Group();

        // Act
        $response = $controller->index($group);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Churrasco de Sexta', $response->getData(true)['group_name']);
    }

    /**
     * Teste 4 (Com Mock | Negativo): Falha ao calcular balanço quando a service lança exceção
     */
    public function test_balance_index_fails_when_service_throws_exception(): void
    {
        // Arrange
        $mockService = $this->createMock(ExpenseService::class);
        $mockService->expects($this->once())
            ->method('calculateBalance')
            ->willThrowException(new InvalidArgumentException('Grupo sem dados financeiros'));

        $controller = new GroupBalanceController($mockService);
        $group = new Group();

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Grupo sem dados financeiros');

        $controller->index($group);
    }
}
