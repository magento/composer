<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer;

use Composer\Console\Application;
use Composer\IO\BufferIO;
use Composer\Factory as ComposerFactory;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class MagentoComposerApplication
 *
 * This class provides ability to set composer application settings and run any composer command.
 * Also provides method to get Composer instance so you can have access to composer properties lie Locker
 */
class MagentoComposerApplication
{

    const COMPOSER_WORKING_DIR = '--working-dir';

    /**
     * Trigger checks config
     *
     * @var bool
     */
    private $configIsSet = false;

    /**
     * Path to Composer home directory
     *
     * @var string
     */
    private $composerHome;

    /**
     * Path to composer.json file
     *
     * @var string
     */
    private $composerJson;

    /**
     * Buffered output
     *
     * @var BufferedOutput
     */
    private $consoleOutput;

    /**
     * @var ConsoleArrayInputFactory
     */
    private $consoleArrayInputFactory;

    /**
     * Constructs class
     *
     * @param Application $consoleApplication
     * @param ConsoleArrayInputFactory $consoleArrayInputFactory
     * @param BufferedOutput $consoleOutput
     */
    public function __construct(
        Application $consoleApplication = null,
        ConsoleArrayInputFactory $consoleArrayInputFactory = null,
        BufferedOutput $consoleOutput = null
    ) {
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
        $this->consoleArrayInputFactory = $consoleArrayInputFactory ? $consoleArrayInputFactory
            : new ConsoleArrayInputFactory();
        $this->consoleOutput = $consoleOutput ? $consoleOutput : new BufferedOutput();
    }

    /**
     * Sets composer environment config
     *
     * @param string $pathToComposerHome
     * @param string $pathToComposerJson
     */
    public function setConfig($pathToComposerHome, $pathToComposerJson)
    {
        $this->composerJson = $pathToComposerJson;
        $this->composerHome = $pathToComposerHome;

        putenv('COMPOSER_HOME=' . $pathToComposerHome);

        $this->consoleApplication->setAutoExit(false);
        $this->configIsSet = true;

    }

    /**
     * Returns composer object
     *
     * @return \Composer\Composer
     * @throws \Exception
     */
    public function getComposer()
    {
        if (!$this->configIsSet) {
            throw new \Exception('Please call setConfig method to configure composer');
        }

        return ComposerFactory::create(new BufferIO(), $this->composerJson);

    }

    /**
     * Runs composer command
     *
     * @param array $commandParams
     * @return bool
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function runComposerCommand(array $commandParams)
    {
        if (!$this->configIsSet) {
            throw new \Exception('Please call setConfig method to configure composer');
        }

        $this->consoleApplication->resetComposer();

        $commandParams[self::COMPOSER_WORKING_DIR] = dirname($this->composerJson);

        $input = $this->consoleArrayInputFactory->create($commandParams);

        $exitCode = $this->consoleApplication->run($input, $this->consoleOutput);

        if ($exitCode) {
            throw new \RuntimeException(
                sprintf('Command "%s" failed: %s', $commandParams['command'], $this->consoleOutput->fetch())
            );
        }

        //TODO: parse output based on command

        return $this->consoleOutput->fetch();
    }
}
