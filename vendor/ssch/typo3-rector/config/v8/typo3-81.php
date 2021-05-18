<?php

declare (strict_types=1);
namespace RectorPrefix20210518;

use Ssch\TYPO3Rector\Rector\v8\v1\Array2XmlCsToArray2XmlRector;
use Ssch\TYPO3Rector\Rector\v8\v1\RefactorDbConstantsRector;
use Ssch\TYPO3Rector\Rector\v8\v1\TypoScriptFrontendControllerCharsetConverterRector;
use RectorPrefix20210518\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
return static function (\RectorPrefix20210518\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $containerConfigurator->import(__DIR__ . '/../config.php');
    $services = $containerConfigurator->services();
    $services->set(\Ssch\TYPO3Rector\Rector\v8\v1\RefactorDbConstantsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v8\v1\Array2XmlCsToArray2XmlRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v8\v1\TypoScriptFrontendControllerCharsetConverterRector::class);
};