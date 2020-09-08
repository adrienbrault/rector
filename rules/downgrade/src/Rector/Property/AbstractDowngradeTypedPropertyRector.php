<?php

declare(strict_types=1);

namespace Rector\Downgrade\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Downgrade\Contract\Rector\DowngradeTypedPropertyRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;

abstract class AbstractDowngradeTypedPropertyRector extends AbstractDowngradeRector implements DowngradeTypedPropertyRectorInterface
{
    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->isAtLeastPhpVersion($this->getPhpVersionFeature())) {
            return null;
        }

        if ($node->type === null) {
            return null;
        }

        if (! $this->shouldRemoveProperty($node)) {
            return null;
        }

        if ($this->addDocBlock) {
            /** @var PhpDocInfo|null $phpDocInfo */
            $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
            if ($phpDocInfo === null) {
                $phpDocInfo = $this->phpDocInfoFactory->createEmpty($node);
            }

            $newType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node->type);
            $phpDocInfo->changeVarType($newType);
        }
        $node->type = null;

        return $node;
    }
}
