<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "SUM" "(" ["DISTINCT"] StringPrimary ")"
 */
final class SumFunction extends FunctionNode
{
    private AggregateExpression $aggregateExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $this->aggregateExpression->dispatch($sqlWalker);
    }

    public function parse(Parser $parser): void
    {
        $this->aggregateExpression = $parser->AggregateExpression();
    }
}
