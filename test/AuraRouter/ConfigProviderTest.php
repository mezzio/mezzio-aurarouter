<?php

declare(strict_types=1);

namespace MezzioTest\Router\AuraRouter;

use Mezzio\Router\AuraRouter;
use Mezzio\Router\AuraRouter\ConfigProvider;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray(): array
    {
        $config = ($this->provider)();
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config): void
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);

        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['aliases']);
        $this->assertArrayHasKey(RouterInterface::class, $config['dependencies']['aliases']);

        $this->assertArrayHasKey('invokables', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['invokables']);
        $this->assertArrayHasKey(AuraRouter::class, $config['dependencies']['invokables']);
    }
}
