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
 * Also provides method to get Composer instance so you can have access composer properties lie Locker
 */
class MagentoComposerApplication
{

    const COMPOSER_WORKING_DIR = '--working-dir';

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
     * @var Application
     */
    private $consoleApplication;

    /**
     * Constructs class
     *
     * @param string $pathToComposerHome
     * @param string $pathToComposerJson
     * @param Application $consoleApplication
     * @param ConsoleArrayInputFactory $consoleArrayInputFactory
     * @param BufferedOutput $consoleOutput
     */
    public function __construct(
        $pathToComposerHome,
        $pathToComposerJson,
        Application $consoleApplication = null,
        ConsoleArrayInputFactory $consoleArrayInputFactory = null,
        BufferedOutput $consoleOutput = null
    ) {
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
        $this->consoleArrayInputFactory = $consoleArrayInputFactory ? $consoleArrayInputFactory
            : new ConsoleArrayInputFactory();
        $this->consoleOutput = $consoleOutput ? $consoleOutput : new BufferedOutput();

        $this->composerJson = $pathToComposerJson;
        $this->composerHome = $pathToComposerHome;

        putenv('COMPOSER_HOME=' . $pathToComposerHome);

        $this->consoleApplication->setAutoExit(false);
    }

    /**
     * Creates composer object
     *
     * @return \Composer\Composer
     * @throws \Exception
     */
    public function createComposer()
    {
        return ComposerFactory::create(new BufferIO(), $this->composerJson);
    }

    /**
     * Runs composer command
     *
     * @param array $commandParams
     * @param string|null $workingDir
     * @return bool
     * @throws \RuntimeException
     */
    public function runComposerCommand(array $commandParams, $workingDir = null)
    {
        $this->consoleApplication->resetComposer();

        if ($workingDir) {
            $commandParams[self::COMPOSER_WORKING_DIR] = $workingDir;
        } else {
            $commandParams[self::COMPOSER_WORKING_DIR] = dirname($this->composerJson);
        }

        $input = $this->consoleArrayInputFactory->create($commandParams);

        $exitCode = $this->consoleApplication->run($input, $this->consoleOutput);

        if ($exitCode) {
            throw new \RuntimeException(
                sprintf('Command "%s" failed: %s', $commandParams['command'], $this->consoleOutput->fetch())
            );
        }

        return $this->consoleOutput->fetch();
    }

    /**
     * Runs composer update --dry-run command
     *
     * @param array $packages
     * @param string|null $workingDir
     * @return string
     * @throws \RuntimeException
     */
    public function runUpdateDryRun($packages, $workingDir = null)
    {
        try {
            // run require
            $this->composerApp->runComposerCommand(
                ['command' => 'require', 'packages' => $packages, '--no-update' => true],
                $workingDir
            );

            $output = $this->runComposerCommand(
                ['command' => 'update', '--dry-run' => true],
                $workingDir
            );
        } catch (\RuntimeException $e) {
            $errorMessage = $this->generateAdditionalErrorMessage($e->getMessage(), $packages, $workingDir);
            throw new \RuntimeException($errorMessage . PHP_EOL . $e->getMessage(), $e->getCode(), $e);
        }

        return $output;
    }

    /**
     * Generates additional explanation for error message
     *
     * @param array $message
     * @param array $inputPackages
     * @param string|null $workingDir
     * @return string
     */
    protected function generateAdditionalErrorMessage($message, $inputPackages, $workingDir = null) {

        $matches  = [];
        $errorMessage = '';
        $packages = [];
        $rawLines = explode(PHP_EOL, $message);

        foreach ($rawLines as $line) {
            if (preg_match('/- (.*) requires (.*) -> no matching package/', $line, $matches)) {
                $packages[] = $matches[1];
                $packages[] = $matches[2];
            }
        }

        if (!empty($packages)) {
            $packages = array_unique($packages);
            $packages = $this->explodePackagesAndVersions($packages);
            $inputPackages = $this->explodePackagesAndVersions($inputPackages);

            $update = [];
            $conflicts = [];

            foreach ($inputPackages as $package => $version) {
                if (isset($packages[$package])) {
                    $update[] = $package . ' to ' . $version;
                }
            }

            foreach (array_diff_key($packages, $inputPackages) as $package => $version) {
                $output = $this->runComposerCommand(
                    ['command' => 'show', 'package' => $package],
                    $workingDir
                );

                $versions = $this->getPackageVersions($output);

                $conflicts[] = ' - ' . $package . ' version ' . $version
                    . ' please try to upgrade it to one of the following package versions: ' . implode(', ', $versions);
            }

            $errorMessage = 'You are trying to update package(s) '
                . implode(', ', $update) . PHP_EOL
                . 'But looks like it conflicts with the following packages:' . PHP_EOL
                . implode(PHP_EOL, $conflicts)
                . PHP_EOL;
        }

        return $errorMessage;
    }

    /**
     * Returns array that contains package as key and version as value
     *
     * @param array $packages
     * @return array
     */
    protected function explodePackagesAndVersions($packages)
    {
        $packagesAndVersions = [];
        foreach ($packages as $package) {
            $package = explode(' ', $package);
            $packagesAndVersions[$package[0]] = $package[1];
        }

        return $packagesAndVersions;
    }

    /**
     * Returns package versions except currently installed based on composer show command output
     *
     * @param string $outputMessage
     * @return array
     */
    protected function getPackageVersions($outputMessage)
    {
        $versions = [];

        if (preg_match('/versions : (.*)/', $outputMessage, $matches)) {
            $versions = $matches[1];
            $versions = explode(', ', $versions);
            $versions = array_filter(
                $versions,
                function ($version) {
                    return strpos($version, '*') === false;
                }
            );
        }

        return $versions;
    }
}
