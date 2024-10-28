<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return (new Configuration())
    ->addPathToScan('./api', false)
    //->ignoreUnknownClasses([
    //    'Gmagick',
    //    'Imagick',
    //])
    //->enableAnalysisOfUnusedDevDependencies()
    //->disableReportingUnmatchedIgnores()
;
