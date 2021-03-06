<?php declare(strict_types=1);

namespace Rector\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use Rector\NodeAnalyzer\StaticMethodCallAnalyzer;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class StaticCallToFunctionRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $staticCallToFunction = [];

    /**
     * @var StaticMethodCallAnalyzer
     */
    private $staticMethodCallAnalyzer;

    /**
     * @param string[] $staticCallToFunction
     */
    public function __construct(array $staticCallToFunction, StaticMethodCallAnalyzer $staticMethodCallAnalyzer)
    {
        $this->staticCallToFunction = $staticCallToFunction;
        $this->staticMethodCallAnalyzer = $staticMethodCallAnalyzer;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns static call to function call.', [
            new ConfiguredCodeSample(
                'OldClass::oldMethod("args");',
                'new_function("args");',
                [
                    '$staticCallToFunction' => [
                        'OldClass::oldMethod' => 'new_function',
                    ],
                ]
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $staticCalls = array_keys($this->staticCallToFunction);
        $activeStaticCall = null;
        foreach ($staticCalls as $staticCall) {
            [$class, $method] = explode('::', $staticCall);
            if ($this->staticMethodCallAnalyzer->isTypeAndMethod($node, $class, $method)) {
                $activeStaticCall = $staticCall;
            }
        }

        if (! isset($this->staticCallToFunction[$activeStaticCall])) {
            return null;
        }

        $newFunctionName = $this->staticCallToFunction[$activeStaticCall];

        return new FuncCall(new FullyQualified($newFunctionName), $node->args);
    }
}
