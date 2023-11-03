<?php

declare(strict_types=1);

namespace Mezzio\Router\AuraRouter;

use Mezzio\Router\AuraRouter;
use Mezzio\Router\RouterInterface;

class ConfigProvider
{
    /**
     * @return array{
     *      dependencies: array{
     *          aliases: array<class-string, class-string>,
     *          invokables: array<class-string, class-string>,
     *      }
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array{
     *      aliases: array<class-string, class-string>,
     *      invokables: array<class-string, class-string>,
     * }
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                RouterInterface::class => AuraRouter::class,

                // Legacy Zend Framework aliases
                'Zend\Expressive\Router\RouterInterface' => RouterInterface::class,
                'Zend\Expressive\Router\AuraRouter'      => AuraRouter::class,
            ],
            'invokables' => [
                AuraRouter::class => AuraRouter::class,
            ],
        ];
    }
}
