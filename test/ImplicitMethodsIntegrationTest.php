<?php

/**
 * @see       https://github.com/mezzio/mezzio-aurarouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-aurarouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-aurarouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use Generator;
use Mezzio\Router\AuraRouter;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Test\ImplicitMethodsIntegrationTest as RouterIntegrationTest;
use Prophecy\PhpUnit\ProphecyTrait;

class ImplicitMethodsIntegrationTest extends RouterIntegrationTest
{
    use ProphecyTrait;

    public function getRouter() : RouterInterface
    {
        return new AuraRouter();
    }

    public function implicitRoutesAndRequests() : Generator
    {
        $options = [
            'tokens' => [
                'version' => '\d+',
            ],
        ];

        // @codingStandardsIgnoreStart
        //                  route                 route options, request       params
        yield 'static'  => ['/api/v1/me',         $options,      '/api/v1/me', []];
        yield 'dynamic' => ['/api/v{version}/me', $options,      '/api/v3/me', ['version' => '3']];
        // @codingStandardsIgnoreEnd
    }
}
