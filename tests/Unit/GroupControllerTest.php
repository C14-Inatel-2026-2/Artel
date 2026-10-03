<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\GroupController;
use App\Models\Group;

class GroupControllerTest extends TestCase
{
    public function test_destroy_deletes_group_and_returns_success_response(): void
    {
        $groupMock = $this->createMock(Group::class);
        $groupMock->expects($this->once())->method('delete');

        $controller = new GroupController();

        $response = $controller->destroy($groupMock);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['message' => 'Grupo excluído com sucesso.'], $response->getData(true));
    }

    public function test_destroy_propagates_exception_on_delete_failure(): void
    {
        $groupMock = $this->createMock(Group::class);
        $groupMock->expects($this->once())->method('delete')->willThrowException(new \RuntimeException('Erro no banco de dados ao excluir grupo'));

        $controller = new GroupController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erro no banco de dados ao excluir grupo');

        $controller->destroy($groupMock);
    }

}
