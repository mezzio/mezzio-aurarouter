<?php

declare(strict_types=1);

namespace Mezzio\Router\AuraRouter;

use Mezzio\Router\AuraRouter;
use Mezzio\Router\RouterInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                RouterInterface::class => AuraRouter::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Router\RouterInterface::class => RouterInterface::class,
                \Zend\Expressive\Router\AuraRouter::class      => AuraRouter::class,
            ],
            'invokables' => [
                AuraRouter::class => AuraRouter::class,
            ],
        ];
    }
}
