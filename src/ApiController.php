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
use Cawa\App\Controller\AbstractController;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class ApiController extends AbstractController
{
    use Tools;
    use SwaggerGenerator;

    /**
     * @return void
     */
    public function init()
    {
        if (App::request()->getMethod() == 'OPTIONS') {
            App::response()->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            App::response()->addHeader('Access-Control-Max-Age', '604800');
            App::response()->addHeader(
                'Access-Control-Request-Headers',
                'Origin, Content-Type, Accept, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control'
            );
            App::end();
        }

        // Enable CORS
        if (App::request()->getHeader('Origin')) {
            App::response()->addHeader('Access-Control-Allow-Origin', App::request()->getHeader('Origin'));
            App::response()->addHeader('Access-Control-Allow-Credentials', 'true');
            App::response()->addHeader(
                'Access-Control-Allow-Headers',
                'X-Requested-With, Content-Type, Accept, X-Apikey'
            );
        }
    }

    /**
     * @param string $renderer
     * @param string $namespace
     * @param int $version
     * @param string $service
     * @param string $method
     *
     * @return string
     */
    public function handle(string $renderer, string $namespace, int $version, string $service, string $method)
    {
        $service = $this->initService($renderer, $namespace, $version, $service, $method);

        return $service->call($method);
    }

    /**
     * @param string $namespace
     * @param int $version
     * @param string|null $service
     * @param string|null $method
     *
     * @throws ResponseCode
     *
     * @return array
     */
    public function swagger(string $namespace, int $version, string $service = null, string $method = null) : array
    {
        $this->controlNamespace($namespace, $version);

        return $this->generateSwagger($namespace, $version, $service, $method);
    }
}
