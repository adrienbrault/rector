<?php declare(strict_types=1);

namespace Rector\Php\Rector\FuncCall;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Rector\NodeAnalyzer\CallAnalyzer;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\Php\EregToPcreTransformer;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * @see http://php.net/reference.pcre.pattern.posix
 * @see https://stackoverflow.com/a/17033826/1348344
 * @see https://docstore.mik.ua/orelly/webprog/pcook/ch13_02.htm
 */
final class EregToPregMatchRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $oldNamesToNewOnes = [
        'ereg' => 'preg_match',
        'eregi' => 'preg_match',
        'ereg_replace' => 'preg_replace',
        'eregi_replace' => 'preg_replace',
        'split' => 'preg_split',
        'spliti' => 'preg_split',
    ];

    /**
     * @var EregToPcreTransformer
     */
    private $eregToPcreTransformer;

    /**
     * @var CallAnalyzer
     */
    private $callAnalyzer;

    public function __construct(EregToPcreTransformer $eregToPcreTransformer, CallAnalyzer $callAnalyzer)
    {
        $this->eregToPcreTransformer = $eregToPcreTransformer;
        $this->callAnalyzer = $callAnalyzer;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Changes ereg*() to preg*() calls',
            [new CodeSample('ereg("hi")', 'preg_match("#hi#");')]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $funcCallNode
     */
    public function refactor(Node $funcCallNode): ?Node
    {
        $functionName = $this->callAnalyzer->resolveName($funcCallNode);

        if (! isset($this->oldNamesToNewOnes[$functionName])) {
            return $funcCallNode;
        }

        $patternNode = $funcCallNode->args[0]->value;
        if ($patternNode instanceof String_) {
            $this->processStringPattern($funcCallNode, $patternNode, $functionName);
        } elseif ($patternNode instanceof Variable) {
            $this->processVariablePattern($funcCallNode, $patternNode, $functionName);
        }

        $this->processSplitLimitArgument($funcCallNode, $functionName);

        $funcCallNode->name = new Name($this->oldNamesToNewOnes[$functionName]);

        // ereg|eregi 3rd argument return value fix
        if (in_array($functionName, ['ereg', 'eregi'], true) && isset($funcCallNode->args[2])) {
            $parentNode = $funcCallNode->getAttribute(Attribute::PARENT_NODE);
            if ($parentNode instanceof Assign) {
                return $this->createTernaryWithStrlenOfFirstMatch($funcCallNode);
            }
        }

        return $funcCallNode;
    }

    private function processStringPattern(FuncCall $funcCallNode, String_ $patternNode, string $functionName): void
    {
        $pattern = $patternNode->value;
        $pattern = $this->eregToPcreTransformer->transform($pattern, $this->isCaseInsensitiveFunction($functionName));

        $funcCallNode->args[0]->value = new String_($pattern);
    }

    private function processVariablePattern(FuncCall $funcCallNode, Variable $patternNode, string $functionName): void
    {
        $pregQuotePatternNode = new FuncCall(new Name('preg_quote'), [
            new Arg($patternNode),
            new Arg(new String_('#')),
        ]);

        $startConcat = new Concat(new String_('#'), $pregQuotePatternNode);

        $endDelimiter = $this->isCaseInsensitiveFunction($functionName) ? '#mi' : '#m';
        $concat = new Concat($startConcat, new String_($endDelimiter));

        $funcCallNode->args[0]->value = $concat;
    }

    private function isCaseInsensitiveFunction(string $functionName): bool
    {
        if (Strings::contains($functionName, 'eregi')) {
            return true;
        }

        if (Strings::contains($functionName, 'spliti')) {
            return true;
        }

        return false;
    }

    /**
     * Equivalent of:
     * split(' ', 'hey Tom', 0);
     * ↓
     * preg_split('# #', 'hey Tom', 1);
     */
    private function processSplitLimitArgument(FuncCall $funcCallNode, string $functionName): void
    {
        if (! Strings::startsWith($functionName, 'split')) {
            return;
        }

        // 3rd argument - $limit, 0 → 1
        if (! isset($funcCallNode->args[2])) {
            return;
        }

        if (! $funcCallNode->args[2]->value instanceof LNumber) {
            return;
        }

        /** @var LNumber $limitNumberNode */
        $limitNumberNode = $funcCallNode->args[2]->value;
        if ($limitNumberNode->value !== 0) {
            return;
        }

        $limitNumberNode->value = 1;
    }

    private function createTernaryWithStrlenOfFirstMatch(FuncCall $funcCallNode): Ternary
    {
        $arrayDimFetch = new ArrayDimFetch($funcCallNode->args[2]->value, new LNumber(0));
        $strlenFuncCall = new FuncCall(new Name('strlen'), [new Arg($arrayDimFetch)]);

        return new Ternary($funcCallNode, $strlenFuncCall, new ConstFetch(new Name('false')));
    }
}
