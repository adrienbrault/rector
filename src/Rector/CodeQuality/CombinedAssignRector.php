<?php declare(strict_types=1);

namespace Rector\Rector\CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignOp\BitwiseAnd as AssignBitwiseAnd;
use PhpParser\Node\Expr\AssignOp\BitwiseOr as AssignBitwiseOr;
use PhpParser\Node\Expr\AssignOp\BitwiseXor as AssignBitwiseXor;
use PhpParser\Node\Expr\AssignOp\Concat as AssignConcat;
use PhpParser\Node\Expr\AssignOp\Div as AssignDiv;
use PhpParser\Node\Expr\AssignOp\Minus as AssignMinus;
use PhpParser\Node\Expr\AssignOp\Mod as AssignMod;
use PhpParser\Node\Expr\AssignOp\Mul as AssignMul;
use PhpParser\Node\Expr\AssignOp\Plus as AssignPlus;
use PhpParser\Node\Expr\AssignOp\Pow as AssignPow;
use PhpParser\Node\Expr\AssignOp\ShiftLeft as AssignShiftLeft;
use PhpParser\Node\Expr\AssignOp\ShiftRight as AssignShiftRight;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Pow;
use PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use PhpParser\Node\Expr\BinaryOp\ShiftRight;
use Rector\Printer\BetterStandardPrinter;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class CombinedAssignRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $binaryOpClassToAssignOpClass = [
        BitwiseOr::class => AssignBitwiseOr::class,
        BitwiseAnd::class => AssignBitwiseAnd::class,
        BitwiseXor::class => AssignBitwiseXor::class,
        Plus::class => AssignPlus::class,
        Div::class => AssignDiv::class,
        Mul::class => AssignMul::class,
        Minus::class => AssignMinus::class,
        Concat::class => AssignConcat::class,
        Pow::class => AssignPow::class,
        Mod::class => AssignMod::class,
        ShiftLeft::class => AssignShiftLeft::class,
        ShiftRight::class => AssignShiftRight::class,
    ];

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    public function __construct(BetterStandardPrinter $betterStandardPrinter)
    {
        $this->betterStandardPrinter = $betterStandardPrinter;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Simplify $value = $value + 5; assignments to shorter ones',
            [new CodeSample('$value = $value + 5;', '$value += 5;')]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Assign::class];
    }

    /**
     * @param Assign $assignNode
     */
    public function refactor(Node $assignNode): ?Node
    {
        if (! $assignNode->expr instanceof BinaryOp) {
            return $assignNode;
        }

        /** @var BinaryOp $binaryNode */
        $binaryNode = $assignNode->expr;

        if (! $this->areNodesEqual($assignNode->var, $binaryNode->left)) {
            return $assignNode;
        }

        $binaryNodeClass = get_class($binaryNode);
        if (! isset($this->binaryOpClassToAssignOpClass[$binaryNodeClass])) {
            return $assignNode;
        }

        $newAssignNodeClass = $this->binaryOpClassToAssignOpClass[$binaryNodeClass];

        /** @var AssignOp $newAssignNodeClass */
        return new $newAssignNodeClass($assignNode->var, $binaryNode->right);
    }

    private function areNodesEqual(Node $firstNode, Node $secondNode): bool
    {
        return $this->betterStandardPrinter->prettyPrint([$firstNode]) === $this->betterStandardPrinter->prettyPrint(
            [$secondNode]
        );
    }
}
