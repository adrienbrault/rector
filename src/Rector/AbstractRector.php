<?php declare(strict_types=1);

namespace Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Rector\Builder\ExpressionAdder;
use Rector\Builder\PropertyAdder;
use Rector\Contract\Rector\PhpRectorInterface;

abstract class AbstractRector extends NodeVisitorAbstract implements PhpRectorInterface
{
    use TypeAnalyzerTrait;

    /**
     * @var bool
     */
    protected $removeNode = false;

    /**
     * @var ExpressionAdder
     */
    private $expressionAdder;

    /**
     * @var PropertyAdder
     */
    private $propertyAdder;

    /**
     * @required
     */
    public function setAbstractRectorDependencies(PropertyAdder $propertyAdder, ExpressionAdder $expressionAdder): void
    {
        $this->propertyAdder = $propertyAdder;
        $this->expressionAdder = $expressionAdder;
    }

    /**
     * @return int|Node|null
     */
    final public function enterNode(Node $node)
    {
        $nodeClass = get_class($node);
        if (! $this->isMatchingNodeType($nodeClass)) {
            return null;
        }

        $newNode = $this->refactor($node);
        if ($newNode !== null) {
            return $newNode;
        }

        if ($this->removeNode) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @return bool|int|Node
     */
    public function leaveNode(Node $node)
    {
        if ($this->removeNode) {
            $this->removeNode = false;
            return NodeTraverser::REMOVE_NODE;
        }

        return $node;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        $nodes = $this->expressionAdder->addExpressionsToNodes($nodes);
        return $this->propertyAdder->addPropertiesToNodes($nodes);
    }

    protected function addNodeAfterNode(Expr $newNode, Node $positionNode): void
    {
        $this->expressionAdder->addNodeAfterNode($newNode, $positionNode);
    }

    private function isMatchingNodeType(string $nodeClass): bool
    {
        foreach ($this->getNodeTypes() as $nodeType) {
            if (is_a($nodeClass, $nodeType, true)) {
                return true;
            }
        }

        return false;
    }
}
