<?php
/**
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace Magento\Composer;

/**
 * Class HomeEnvironmentChangerTest
 *
 * @package Magento\Composer
 * @covers Magento\Composer\HomeEnvironmentChanger
 */
class HomeEnvironmentChangerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Path to (temporary) test directory
     *
     * @var string
     */
    private $testDir;

    protected function setUp()
    {
        $tmpDir = sys_get_temp_dir();
        if (!$tmpDir) {
            throw new \RuntimeException('Unable to obtain temporary directory');
        }

        $testDir = $tmpDir . '/homeenv-test/new_home';
        if (!is_dir($testDir) && !mkdir($testDir, 0777, true)) {
            throw new \RuntimeException('Unable to create test directory');
        }

        $this->testDir = $testDir;
    }

    protected function tearDown()
    {
        rmdir($this->testDir) && rmdir(dirname($this->testDir));
    }

    /**
     * Test
     *
     * @test that composer home change operation does not change the path to the composer cache.
     *
     * this allows to keep all cache entries.
     *
     * @return void
     */
    public function changeRetainsCache()
    {
        $newHome = $this->testDir;

        $this->assertFalse(getenv('COMPOSER_CACHE'));
        $this->assertFalse(getenv('COMPOSER_HOME'));
        $this->assertNotFalse($homeDir = getenv('HOME'));

        $oldHome = rtrim($homeDir, '/') . '/.composer';

        // changing it to the current directory should do nothing
        $subject = HomeEnvironmentChanger::create($oldHome);
        $this->assertInstanceOf(__NAMESPACE__ . '\HomeEnvironmentChanger', $subject);
        $retval = $subject->change();
        $this->assertInstanceOf(HomeEnvironmentChanger::class, $retval);
        $this->assertSame($subject, $retval);
        $this->assertFalse(getenv('COMPOSER_HOME'));
        $this->assertFalse(getenv('COMPOSER_CACHE'));

        // change to a new setting should change it an create the cache entry
        $subject = HomeEnvironmentChanger::create($newHome);
        $subject->change();
        $cache = getenv('COMPOSER_CACHE');
        $this->assertNotFalse($cache);
        $home = getenv('COMPOSER_HOME');
        $this->assertNotFalse($home);
        $this->assertNotSame($newHome . '/cache', $cache);
        $this->assertNotSame(getenv('COMPOSER_HOME') . '/cache', $cache);
        $this->assertSame(rtrim($homeDir, '/') . '/.composer/cache', $cache);
    }

    /**
     * Test
     *
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Path for composer home is not a directory or not writeable
     * @return void
     */
    public function nonWriteableDirectoryThrowsExcetion()
    {
        HomeEnvironmentChanger::create("");
    }
}
