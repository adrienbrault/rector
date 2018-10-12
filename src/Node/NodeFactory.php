<?php declare(strict_types=1);

namespace Rector\Node;

use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Builder\Class_\VariableInfo;
use Rector\Exception\NotImplementedException;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\Php\TypeAnalyzer;
use function Safe\sprintf;

final class NodeFactory
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var PropertyFetchNodeFactory
     */
    private $propertyFetchNodeFactory;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    public function __construct(
        BuilderFactory $builderFactory,
        PropertyFetchNodeFactory $propertyFetchNodeFactory,
        TypeAnalyzer $typeAnalyzer
    ) {
        $this->builderFactory = $builderFactory;
        $this->propertyFetchNodeFactory = $propertyFetchNodeFactory;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * Creates "null"
     */
    public function createNullConstant(): ConstFetch
    {
        return BuilderHelpers::normalizeValue(null);
    }

    /**
     * Creates "true"
     */
    public function createTrueConstant(): ConstFetch
    {
        return BuilderHelpers::normalizeValue(true);
    }

    /**
     * Creates "\SomeClass::CONSTANT"
     */
    public function createClassConstant(string $className, string $constantName): ClassConstFetch
    {
        $classNameNode = new FullyQualified($className);

        $classConstFetchNode = $this->builderFactory->classConstFetch($classNameNode, $constantName);
        $classConstFetchNode->class->setAttribute(Attribute::RESOLVED_NAME, $classNameNode);

        return $classConstFetchNode;
    }

    /**
     * Creates "\SomeClass::class"
     */
    public function createClassConstantReference(string $className): ClassConstFetch
    {
        $nameNode = new FullyQualified($className);

        return $this->builderFactory->classConstFetch($nameNode, 'class');
    }

    /**
     * Creates "use \SomeTrait;"
     */
    public function createTraitUse(string $traitName): TraitUse
    {
        $traitNameNode = new FullyQualified($traitName);

        return new TraitUse([$traitNameNode]);
    }

    /**
     * Creates "['item', $variable]"
     *
     * @param mixed|Node[] ...$items
     */
    public function createArray(...$items): Array_
    {
        $arrayItems = [];

        foreach ($items as $item) {
            if ($item instanceof Variable) {
                $arrayItems[] = new ArrayItem($item);
            } elseif ($item instanceof Identifier) {
                $string = new String_($item->toString());
                $arrayItems[] = new ArrayItem($string);
            } else {
                throw new NotImplementedException(sprintf(
                    'Not implemented yet. Go to "%s()" and add check for "%s" node.',
                    __METHOD__,
                    get_class($item)
                ));
            }
        }

        return new Array_($arrayItems);
    }

    /**
     * Creates "($args)"
     *
     * @param mixed[] $arguments
     * @return Arg[]
     */
    public function createArgs(array $arguments): array
    {
        return $this->builderFactory->args($arguments);
    }

    /**
     * Creates $this->property = $property;
     */
    public function createPropertyAssignment(string $propertyName): Expression
    {
        $variable = new Variable($propertyName, ['name' => $propertyName]);

        return $this->createPropertyAssignmentWithExpr($propertyName, $variable);
    }

    public function createPropertyAssignmentWithExpr(string $propertyName, Expr $rightExprNode): Expression
    {
        $leftExprNode = $this->propertyFetchNodeFactory->createLocalWithPropertyName($propertyName);
        $assign = new Assign($leftExprNode, $rightExprNode);
        return new Expression($assign);
    }

    /**
     * Creates "($arg)"
     *
     * @param mixed $argument
     */
    public function createArg($argument): Arg
    {
        $value = BuilderHelpers::normalizeValue($argument);

        return new Arg($value);
    }

    /**
     * Creates:
     * - namespace NamespaceName;
     */
    public function createNamespace(string $namespace): Namespace_
    {
        return $this->builderFactory->namespace($namespace)
            ->getNode();
    }

    public function createParam(string $name, string $type): Param
    {
        return $this->builderFactory->param($name)
            ->setType(new FullyQualified($type))
            ->getNode();
    }

    public function createPublicMethod(string $name): ClassMethod
    {
        return $this->builderFactory->method($name)
            ->makePublic()
            ->getNode();
    }

    public function createParamFromVariableInfo(VariableInfo $variableInfo): Param
    {
        $paramBuild = $this->builderFactory->param($variableInfo->getName());

        foreach ($variableInfo->getTypes() as $type) {
            $paramBuild->setType($this->createTypeName($type));
        }

        return $paramBuild->getNode();
    }

    public function createString(string $name): String_
    {
        return new String_($name);
    }

    public function createVariable(string $name): Variable
    {
        return $this->builderFactory->var($name);
    }

    public function createTypeName(string $name): Name
    {
        if ($this->typeAnalyzer->isPhpReservedType($name)) {
            return new Name($name);
        }

        return new FullyQualified($name);
    }
}
