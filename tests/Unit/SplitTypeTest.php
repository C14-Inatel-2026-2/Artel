<?php

namespace Tests\Unit;

use App\Enums\SplitType;
use App\Services\ExpenseSplits\EqualSplitStrategy;
use App\Services\ExpenseSplits\ExactSplitStrategy;
use App\Services\ExpenseSplits\ExpenseSplitStrategyFactory;
use App\Services\ExpenseSplits\PercentageSplitStrategy;
use PHPUnit\Framework\TestCase;

class SplitTypeTest extends TestCase
{
    public function test_valores_aceitos_seguem_a_ordem_da_api(): void
    {
        $this->assertSame(['equal', 'exact', 'percentage'], SplitType::values());
    }

    public function test_valor_ausente_ou_desconhecido_vira_divisao_igual(): void
    {
        $this->assertSame(SplitType::Equal, SplitType::fromInput(null));
        $this->assertSame(SplitType::Equal, SplitType::fromInput(''));
        $this->assertSame(SplitType::Equal, SplitType::fromInput('qualquer-coisa'));
    }

    public function test_valores_validos_sao_reconhecidos(): void
    {
        $this->assertSame(SplitType::Equal, SplitType::fromInput('equal'));
        $this->assertSame(SplitType::Exact, SplitType::fromInput('exact'));
        $this->assertSame(SplitType::Percentage, SplitType::fromInput('percentage'));
    }

    public function test_factory_devolve_a_estrategia_de_cada_tipo(): void
    {
        $factory = new ExpenseSplitStrategyFactory;

        $this->assertInstanceOf(EqualSplitStrategy::class, $factory->make('equal'));
        $this->assertInstanceOf(ExactSplitStrategy::class, $factory->make('exact'));
        $this->assertInstanceOf(PercentageSplitStrategy::class, $factory->make('percentage'));
        $this->assertInstanceOf(EqualSplitStrategy::class, $factory->make('desconhecido'));
    }
}
