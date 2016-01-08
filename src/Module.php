<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Cawa\SwaggerServer;

use Cawa\App\App;
use Cawa\Router\Route;

class Module extends \Cawa\App\Module
{
    /**
     * @var ServiceNamespace[]
     */
    public $namespaces = [];

    /**
     * @var array
     */
    public $users;

    /**
     * @param array $namespaces
     * @param array $users
     */
    public function __construct(array $namespaces, $users)
    {
        /* @var $namespace ServiceNamespace */
        foreach ($namespaces as $namespace) {
            if (!$namespace instanceof ServiceNamespace) {
                throw new \InvalidArgumentException(
                    sprintf("Invalid namespace with class '%s'", get_class($namespace))
                );
            }
            $this->namespaces[$namespace->getName()] = $namespace;
        }

        $this->users = $users;
    }

    /**
     * @return bool
     */
    public function init() : bool
    {
        $renderer = '{{C:<renderer>(Json|JsonP|JsonEncapsulated)}}';
        $namespace = '{{C:<namespace>[A-Za-z0-9]+}}';
        $version = 'v{{C:<version>[0-9]+}}';
        $service = '{{C:<service>[A-Za-z0-9]+}}';
        $method = '{{C:<method>[A-Za-z0-9]+}}';

        // main api end point
        App::router()->addRoutes([
            Route::create()->setName('swagger.request')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch('/' . implode('/', [
                    $renderer,
                    $namespace,
                    $version,
                    $service,
                    $method
                ]))
                ->setController('Cawa\\SwaggerServer\\ApiController::handle'),
        ]);

        // swagger generation
        App::router()->addRoutes([
            Route::create()->setName('swagger.swaggerMethod')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch('/' . implode('/', [
                    'Swagger',
                    $namespace,
                    $version,
                    $service,
                    $method
                ]) . '.json')
                ->setController('Cawa\\SwaggerServer\\ApiController::swagger'),
        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.swaggerService')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch('/' . implode('/', [
                    'Swagger',
                    $namespace,
                    $version,
                    $service
                ]) . '.json')
                ->setController('Cawa\\SwaggerServer\\ApiController::swagger'),
        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.swaggerNamespace')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch('/' . implode('/', [
                    'Swagger',
                    $namespace,
                    $version,
                ]) . '.json')
                ->setController('Cawa\\SwaggerServer\\ApiController::swagger'),
        ]);

        // docs with version
        App::router()->addRoutes([
            Route::create()->setName('swagger.docMethodVersion')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace,
                    $version,
                    $service,
                    $method
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller::method')

        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.docServiceVersion')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace,
                    $version,
                    $service
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller')
        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.docNamespaceVersion')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace,
                    $version
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller')
        ]);

        // docs without version
        App::router()->addRoutes([
            Route::create()->setName('swagger.docMethod')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace,
                    $service,
                    $method
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller::method')

        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.docService')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace,
                    $service
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller')
        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.docNamespace')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs',
                    $namespace
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller')
        ]);

        App::router()->addRoutes([
            Route::create()->setName('swagger.docHome')
                ->setOption(Route::OPTIONS_URLIZE, false)
                ->setMatch(implode('/', [
                    '/{{L}}/Docs'
                ]))
                ->setController('Cawa\\SwaggerServer\\Docs\\Controller')
        ]);

        return true;
    }
}
