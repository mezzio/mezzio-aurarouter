<?php

/**
 * @see       https://github.com/mezzio/mezzio-aurarouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-aurarouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-aurarouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\AuraRouter;

use Mezzio\Router\AuraRouter\ConfigProvider;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider
     */
    private $provider;

    protected function setUp() : void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray() : array
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);

        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config) : void
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertInternalType('array', $config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
        $this->assertInternalType('array', $config['dependencies']['factories']);
        $this->assertArrayHasKey(RouterInterface::class, $config['dependencies']['factories']);
    }
}
