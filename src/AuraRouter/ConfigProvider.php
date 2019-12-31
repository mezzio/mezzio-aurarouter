<?php

/**
 * @see       https://github.com/mezzio/mezzio-aurarouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-aurarouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-aurarouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router\AuraRouter;

use Mezzio\Router\AuraRouter;
use Mezzio\Router\RouterInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                RouterInterface::class => AuraRouter::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Router\RouterInterface::class => RouterInterface::class,
                \Zend\Expressive\Router\AuraRouter::class => AuraRouter::class,
            ],
            'invokables' => [
                AuraRouter::class => AuraRouter::class,
            ],
        ];
    }
}
