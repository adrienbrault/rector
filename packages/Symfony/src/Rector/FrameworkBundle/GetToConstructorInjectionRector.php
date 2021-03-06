<?php declare(strict_types=1);

namespace Rector\Symfony\Rector\FrameworkBundle;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class GetToConstructorInjectionRector extends AbstractToConstructorInjectionRector
{
    /**
     * @var string[]
     */
    private $getMethodAwareTypes = [];

    /**
     * @todo merge to $getMethodAwareTypes
     */
    public function __construct(
        string $controllerClass = 'Symfony\Bundle\FrameworkBundle\Controller\Controller',
        string $traitClass = 'Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait'
    ) {
        $this->getMethodAwareTypes[] = $controllerClass;
        $this->getMethodAwareTypes[] = $traitClass;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Turns fetching of dependencies via `$this->get()` to constructor injection in Command and Controller in Symfony',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class MyCommand extends ContainerAwareCommand
{
    public function someMethod()
    {
        // ...
        $this->get('some_service');
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class MyCommand extends Command
{
    public function __construct(SomeService $someService)
    {
        $this->someService = $someService;
    }

    public function someMethod()
    {
        $this->someService;
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $methodCallNode
     */
    public function refactor(Node $methodCallNode): ?Node
    {
        if (! $this->methodCallAnalyzer->isTypesAndMethod($methodCallNode, $this->getMethodAwareTypes, 'get')) {
            return null;
        }

        return $this->processMethodCallNode($methodCallNode);
    }
}
