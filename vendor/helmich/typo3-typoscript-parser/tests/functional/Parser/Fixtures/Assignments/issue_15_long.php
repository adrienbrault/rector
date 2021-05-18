<?php

declare (strict_types=1);
namespace RectorPrefix20210518;

use RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\NestedAssignment;
use RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath;
use RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Operator\Assignment;
use RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Operator\ObjectCreation;
use RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Scalar;
return [new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\NestedAssignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page', 'page'), [new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Operator\ObjectCreation(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20', '20'), new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Scalar('USER'), 2), new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\NestedAssignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20', '20'), [new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Operator\Assignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20.userFunc', 'userFunc'), new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Scalar('TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run'), 4), new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\NestedAssignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20.switchableControllerActions', 'switchableControllerActions'), [new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\NestedAssignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20.switchableControllerActions.{$plugin.tx_foo.settings.bar.controllerName}', '{$plugin.tx_foo.settings.bar.controllerName}'), [new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Operator\Assignment(new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\ObjectPath('page.20.switchableControllerActions.{$plugin.tx_foo.settings.bar.controllerName}.1', '1'), new \RectorPrefix20210518\Helmich\TypoScriptParser\Parser\AST\Scalar('{$plugin.tx_foo.settings.bar.actionName}'), 7)], 6)], 5)], 3)], 1)];