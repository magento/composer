<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer;

use Composer\Console\Application;
use Composer\IO\BufferIO;
use Composer\Factory as ComposerFactory;

class MagentoComposerApplication
{
    private $configIsSet = false;

    private $composerHome;

    private $composerJson;

    public function __construct(
        Application $consoleApplication = null
    ) {
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
    }

    public function setConfig($pathToComposerHome, $pathToComposerJson)
    {
        $this->composerJson = $pathToComposerJson;
        $this->composerHome = $pathToComposerHome;

        putenv('COMPOSER_HOME=' . $pathToComposerHome);
        putenv('COMPOSER=' . $pathToComposerJson);

        $this->consoleApplication->setAutoExit(false);
        $this->configIsSet = true;

    }

    public function getComposer()
    {
        if (!$this->configIsSet) {
            throw new \Exception('Please call setConfig method to set config');
        }

        return ComposerFactory::create(new BufferIO(), $this->composerJson);

    }
}
