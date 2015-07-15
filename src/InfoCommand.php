<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer;

/**
 * Class InfoCommand calls composer info command
 */
class InfoCommand
{

    /**
     * @var MagentoComposerApplication
     */
    protected $magentoComposerApplication;

    /**
     * Constructor
     *
     * @param MagentoComposerApplication $magentoComposerApplication
     */
    public function __construct(MagentoComposerApplication $magentoComposerApplication)
    {
        $this->magentoComposerApplication = $magentoComposerApplication;
    }

    /**
     * Runs InfoCommand command
     *
     * @param string $package
     * @param bool $installed
     * @param null $version
     * @return array|bool
     */
    public function run($package, $installed = false, $version = null)
    {
        $commandParameters = [
            'command' => 'info',
            'package' => $package,
            '-i' => $installed
        ];

        $result = [];

        try {
            $output = $this->magentoComposerApplication->runComposerCommand(
                $commandParameters
            );
        } catch (\RuntimeException $e) {
            return false;
        }

        $rawLines = explode(PHP_EOL, $output);

        foreach ($rawLines as $line) {
            $chunk = explode(':', $line);
            if (count($chunk) === 2) {
                $result[trim($chunk[0])] = trim($chunk[1]);
            }

        }

        $result = $this->extractVersions($result);

        return $result;
    }

    /**
     * Extracts package versions info
     *
     * @param array $packageInfo
     * @return array
     */
    private function extractVersions($packageInfo)
    {
        $versions = explode(', ', $packageInfo['versions']);

        if (count($versions) === 1) {
            $packageInfo['current_version'] = str_replace('* ', '', $packageInfo['versions']);
            $packageInfo['available_versions'] = [];
        } else {
            $currentVersion = array_values(preg_grep("/^\*.*/", $versions));
            $packageInfo['current_version'] = str_replace('* ', '', $currentVersion[0]);
            $packageInfo['available_versions'] = array_values(preg_grep("/^\*.*/", $versions, PREG_GREP_INVERT));
        }

        return $packageInfo;
    }
}
