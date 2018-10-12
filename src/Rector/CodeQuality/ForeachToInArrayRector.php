<?php declare(strict_types=1);

namespace Rector\Rector\CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;
use Rector\Node\NodeFactory;

final class ForeachToInArrayRector extends AbstractRector
{
    public function __construct(NodeFactory $nodeFactory)
    {
        $this->nodeFactory = $nodeFactory;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Simplify `foreach` loops into `in_array` when possible',
            [new CodeSample('in_array("key", array_keys($array), true);', 'array_key_exists("key", $array);')]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Foreach_::class];
    }

    /**
     * @param Foreach_ $foreach
     */
    public function refactor(Node $foreach): ?Node
    {
        if (isset($foreach->keyVar)) {
            return $foreach;
        }

        $nextNode = $foreach->getAttribute('nextNode')->expr;

        if ($nextNode->name->parts[0] !== 'false') {
            return $foreach;
        }

        $firstNodeInsideForeach = $foreach->stmts[0];

        if (! $firstNodeInsideForeach instanceof If_) {
            return $foreach;
        }

        $ifCondition = $firstNodeInsideForeach->cond;

        if (! $ifCondition instanceof Identical) {
            return $foreach;
        }

        $leftVariable = $ifCondition->left;
        $rightVariable = $ifCondition->right;

        if ($leftVariable->name !== $foreach->valueVar->name) {
            return $foreach;
        }

        $ifStatment = $firstNodeInsideForeach->stmts[0];

        if (! $ifStatment instanceof Return_) {
            return $foreach;
        }

        if ($ifStatment->expr->name->parts[0] !== 'true') {
            return $foreach;
        }

        $inArrayFunctionCall = new FuncCall(new Name('in_array'), [
            $rightVariable,
            $foreach->expr,
            $this->nodeFactory->createTrueConstant(),
        ]);

        return new Return_($inArrayFunctionCall);
    }
}
