<?php
/**
 * @author Tom Klingenberg <https://github.com/ktomk>
 *
 * Parts of this file were part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * @license MIT
 * @see \Composer\Factory::getHomeDir()
 */

namespace Magento\Composer;

use RuntimeException;

/**
 * Class ChangedHomeEnvironment
 *
 * Magento changes the Composer home envirnment. This class encapsulates such a change
 * and it's state.
 *
 * @package Magento\Composer
 */
class HomeEnvironmentChanger
{
    /**
     * Path
     *
     * @var string
     */
    private $newComposerHomePath;

    /**
     * ChangedHomeEnvironment static constructor.
     *
     * @param string $newComposerHomePath
     * @return HomeEnvironmentChanger
     */
    public static function create($newComposerHomePath)
    {
        if (!is_dir($newComposerHomePath) || !is_writeable($newComposerHomePath))
        {
            throw new RuntimeException(
                sprintf(
                    'Path for composer home is not a directory or not writeable: %s'
                    , json_encode($newComposerHomePath)
                )
            );
        }

        $changer = new static($newComposerHomePath);

        return $changer;
    }

    /**
     * ChangedHomeEnvironment private constructor.
     *
     * @param string $newComposerHomePath
     */
    private function __construct($newComposerHomePath)
    {
        $this->newComposerHomePath = $newComposerHomePath;
    }

    /**
     * Change composer home by preserving cache and auth.
     *
     * @return HomeEnvironmentChanger
     * f
     */
    public function change()
    {
        $current = rtrim($this->getComposerHomeDir(), '/');
        $target = rtrim($this->newComposerHomePath, '/');

        // nothing to change - exit early
        if ($current === $target) {
            return $this;
        }

        $environment = [];

        // new composer home
        $environment['COMPOSER_HOME'] = $target;

        // do not degrade cache
        $cache = getenv('COMPOSER_CACHE');
        if (!$cache) {
            $environment['COMPOSER_CACHE'] = $current . '/cache';
        }

        $this->putEnvironment($environment);

        return $this;
    }

    /**
     * Put zero or more variables into the environment
     *
     *
     * @param array $environment
     * @return void
     */
    private function putEnvironment(/* @codingStandardsIgnoreStart */
        array $environment
        /* @codingStandardsIgnoreEnd */
    )
    {
        foreach ($environment as $name => $value) {
            if (putenv("$name=$value")) {
                continue;
            }

            throw new RuntimeException(
                sprintf("Failed to set environment %s variable (via putenv()) to %s")
            );
        }
    }

    /**
     * Detect the Composer Home Directory as composer does it
     *
     * @see \Composer\Factory::getHomeDir()
     *
     * @return string
     */
    private function getComposerHomeDir()
    {
        $home = getenv('COMPOSER_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/Composer';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = rtrim(getenv('HOME'), '/') . '/.composer';
            }
        }

        return $home;
    }
}
