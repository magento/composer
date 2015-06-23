<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer;

use Composer\Console\Application;

class MagentoComposerApplication
{
    public function __construct(
        Application $consoleApplication = null
    ) {
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
        $this->consoleApplication->setAutoExit(false);
    }

    public function setConfig($pathToComposerHome, $pathToComposerJson)
    {
        putenv('COMPOSER_HOME=' . $pathToComposerHome);
        putenv('COMPOSER=' . $pathToComposerJson);
    }
}
