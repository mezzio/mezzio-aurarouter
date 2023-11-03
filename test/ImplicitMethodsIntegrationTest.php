<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Generator;
use Mezzio\Router\AuraRouter;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Test\ImplicitMethodsIntegrationTest as RouterIntegrationTest;

class ImplicitMethodsIntegrationTest extends RouterIntegrationTest
{
    public function getRouter(): RouterInterface
    {
        return new AuraRouter();
    }

    public static function implicitRoutesAndRequests(): Generator
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
