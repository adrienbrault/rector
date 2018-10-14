<?php declare(strict_types=1);

namespace Rector\PhpParser\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use Rector\Node\MethodCallNodeFactory;
use Rector\NodeAnalyzer\PropertyFetchAnalyzer;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * Covers part of https://github.com/nikic/PHP-Parser/blob/master/UPGRADE-4.0.md
 */
final class IdentifierRector extends AbstractRector
{
    /**
     * @var PropertyFetchAnalyzer
     */
    private $propertyFetchAnalyzer;

    /**
     * @var string[][]
     */
    private $typeToPropertiesMap = [
        'PhpParser\Node\Const_' => ['name'],
        'PhpParser\Node\NullableType' => ['type'], // sometimes only
        'PhpParser\Node\Param' => ['type'],  // sometimes only
        'PhpParser\Node\Expr\ClassConstFetch' => ['name'],
        'PhpParser\Node\Expr\Closure' => ['returnType'], // sometimes only
        'PhpParser\Node\Expr\MethodCall' => ['name'],
        'PhpParser\Node\Expr\PropertyFetch' => ['name'],
        'PhpParser\Node\Expr\StaticCall' => ['name'],
        'PhpParser\Node\Expr\StaticPropertyFetch' => ['name'],
        'PhpParser\Node\Stmt\Class_' => ['name'],
        'PhpParser\Node\Stmt\ClassMethod' => ['name', 'returnType' /* sometimes only */],
        'PhpParser\Node\Stmt\Function' => ['name', 'returnType' /* sometimes only */],
        'PhpParser\Node\Stmt\Goto_' => ['name'],
        'PhpParser\Node\Stmt\Interface_' => ['name'],
        'PhpParser\Node\Stmt\Label' => ['name'],
        'PhpParser\Node\Stmt\PropertyProperty' => ['name'],
        'PhpParser\Node\Stmt\TraitUseAdaptation\Alias' => ['method', 'newName'],
        'PhpParser\Node\Stmt\TraitUseAdaptation\Precedence' => ['method'],
        'PhpParser\Node\Stmt\Trait_' => ['name'],
        'PhpParser\Node\Stmt\UseUse' => ['alias'],
    ];

    /**
     * @var MethodCallNodeFactory
     */
    private $methodCallNodeFactory;

    public function __construct(
        PropertyFetchAnalyzer $propertyFetchAnalyzer,
        MethodCallNodeFactory $methodCallNodeFactory
    ) {
        $this->propertyFetchAnalyzer = $propertyFetchAnalyzer;
        $this->methodCallNodeFactory = $methodCallNodeFactory;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns node string names to Identifier object in php-parser', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$constNode = new PhpParser\Node\Const_;
$name = $constNode->name;
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$constNode = new PhpParser\Node\Const_;
$name = $constNode->name->toString();'
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [PropertyFetch::class];
    }

    /**
     * @param PropertyFetch $propertyFetchNode
     */
    public function refactor(Node $propertyFetchNode): ?Node
    {
        if (! $this->propertyFetchAnalyzer->isTypes($propertyFetchNode, array_keys($this->typeToPropertiesMap))) {
            return null;
        }

        $nodeTypes = $this->getTypes($propertyFetchNode->var);
        $properties = $this->matchTypeToProperties($nodeTypes);
        if (! $this->propertyFetchAnalyzer->isProperties($propertyFetchNode, $properties)) {
            return null;
        }

        $parentNode = $propertyFetchNode->getAttribute(Attribute::PARENT_NODE);
        if ($parentNode instanceof MethodCall) {
            return $propertyFetchNode;
        }

        return $this->methodCallNodeFactory->createWithVariableAndMethodName($propertyFetchNode, 'toString');
    }

    /**
     * @param string[] $nodeTypes
     * @return string[]
     */
    private function matchTypeToProperties(array $nodeTypes): array
    {
        foreach ($nodeTypes as $nodeType) {
            if ($this->typeToPropertiesMap[$nodeType]) {
                return $this->typeToPropertiesMap[$nodeType];
            }
        }

        return [];
    }
}
