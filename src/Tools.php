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
use Cawa\SwaggerServer\Exceptions\ResponseCode;
use Cawa\SwaggerServer\Reflection\Definitions\Auth;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\HttpMethod;
use Cawa\SwaggerServer\Reflection\Model;
use Cawa\SwaggerServer\Renderer\AbstractRenderer;

trait Tools
{
    /**
     * @var \Cawa\SwaggerServer\Module
     */
    public $module;

    /**
     * @return \Cawa\App\Module|Module
     */
    public function module()
    {
        if (!$this->module) {
            $this->module = App::instance()->getModule('Cawa\\SwaggerServer\\Module');
        }

        return $this->module;
    }

    /**
     * @var AbstractService[]
     */
    protected static $servicesList = [];

    /**
     * @param string $namespace
     *
     * @return int
     */
    private function maxVersion(string $namespace) : int
    {
        return max($this->module()->namespaces[$namespace]->getVersions());
    }

    /**
     * @param string $namespace
     * @param int $version
     * @param string|null $service
     * @param string|null $method
     *
     * @return AbstractService[]
     */
    protected function listServices(
        string $namespace,
        int $version = null,
        string $service = null,
        string $method = null
    ) : array {
        $version = $version ?? $this->maxVersion($namespace);

        if ($service) {
            $service = $this->controlService($namespace, $version, $service, $method);

            $serviceName = substr(
                get_class($service),
                strlen($this->module()->namespaces[$namespace]->getClassNamespace())
            );

            self::$servicesList[ltrim($serviceName, '\\')] = $service;

            return [$service];
        }

        if (self::$servicesList) {
            return self::$servicesList;
        }

        // get composer classloader
        /* @var $classLoader \Composer\Autoload\ClassLoader */
        $classLoader = null;
        foreach (get_included_files() as $include) {
            if (stripos($include, 'vendor/autoload.php') !== false) {
                /* @noinspection PhpIncludeInspection */
                $classLoader = require $include;
                break;
            }
        }

        $class = $this->module()->namespaces[$namespace]->getClassNamespace();

        // find path for classname
        $path = null;
        foreach ($classLoader->getPrefixesPsr4() as $classPrefix => $prefix) {
            if (stripos($class, $classPrefix) === 0) {
                $path = $prefix[0] . '/' .
                    str_replace('\\', '/', substr($class, strlen($classPrefix)));

                break;
            }
        }

        // list all service of this path
        $directoryIterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);

        /* @var $file \SplFileInfo */
        foreach ($iterator as $file) {
            $serviceName = str_replace('/', '\\', substr($file->getPath(), strlen($path)) . '\\' .
                $file->getBasename('.' . $file->getExtension()));

            $serviceClass = $class . $serviceName;
            self::$servicesList[ltrim($serviceName, '\\')] = new $serviceClass();

            if ($version != $this->maxVersion($namespace)) {
                $serviceClass = $class . $serviceName . '_v' . $version;
                self::$servicesList[ltrim($serviceName, '\\')] = new $serviceClass();
            }
        }

        return self::$servicesList;
    }

    /**
     * @param string $namespace
     * @param int $version
     * @param string $serviceName
     * @param $method
     *
     * @throws ResponseCode
     *
     * @return AbstractService
     */
    protected function controlService(
        string $namespace,
        int $version,
        string $serviceName,
        string $method = null
    ) : AbstractService {
        // service validation
        $serviceClass = '\\' . $this->module()->namespaces[$namespace]->getClassNamespace() .
            '\\' . str_replace('/', '\\', $serviceName);

        if (intval(max($this->module()->namespaces[$namespace]->getVersions())) != $version) {

            // force loading on main class
            if (!class_exists($serviceClass)) {
                throw new ResponseCode(sprintf("Unknown service '%s'", $serviceClass), 404);
            }

            $serviceClass .= '_v' . $version;
        }

        if (!class_exists($serviceClass)) {
            throw new ResponseCode(sprintf("Unknown service '%s'", $serviceClass), 404);
        }

        /* @var $service AbstractService */
        $service = new $serviceClass();
        $service->setNamespace($namespace)
            ->setVersion($version)
            ->setName($serviceName);

        if ($method) {
            if (!method_exists($service, $method)) {
                throw new ResponseCode(sprintf("Unknown method '%s'", $method), 404);
            }

            $reflectionMethod = $service->getReflectionMethod($method);

            /* @var $httpMethod HttpMethod */
            if (!($httpMethod = $reflectionMethod->getParam(Definition::HTTP_METHOD))) {
                throw new \LogicException(sprintf("Undefined HttpMethod on '%s::%s'", $serviceClass, $method));
            }

            if ($httpMethod->getHttpMethod() != App::request()->getMethod()) {
                throw new ResponseCode(
                    sprintf("Invalid httpmethod %s on '%s::%s'", App::request()->getMethod(), $serviceClass, $method),
                    405
                );
            }
        }

        return $service;
    }

    /**
     * @param string $namespace
     * @param int $version
     *
     * @throws ResponseCode
     */
    protected function controlNamespace(string $namespace, int $version)
    {
        // namespace validation
        if (!isset($this->module()->namespaces[$namespace])) {
            throw new ResponseCode(sprintf("Invalid namespace '%s'", $namespace), 404);
        }

        if ($version <= 0) {
            throw new ResponseCode(sprintf("Invalid version '%s'", $version), 422);
        }

        if (!in_array($version, $this->module()->namespaces[$namespace]->getVersions())) {
            throw new ResponseCode(sprintf("Unknown version '%s'", $version), 404);
        }
    }

    /**
     * @param string $rendererName
     * @param string $namespace
     * @param int $version
     * @param string $serviceName
     * @param string $method
     *
     * @throws ResponseCode
     *
     * @return AbstractService
     */
    protected function initService(
        string $rendererName,
        string $namespace,
        int $version,
        string $serviceName,
        string $method
    ) {
        // renderer
        $rendererClass = '\\Cawa\\SwaggerServer\\Renderer\\' . $rendererName;

        if (!class_exists($rendererClass)) {
            throw new ResponseCode(sprintf("Unknown renderer '%s'", $rendererClass), 404);
        }

        /* @var $renderer AbstractRenderer */
        $renderer = new $rendererClass();
        $renderer->registerExceptionHandler();

        $this->controlNamespace($namespace, $version);
        $service = $this->controlService($namespace, $version, $serviceName, $method);
        $reflectionMethod = $service->getReflectionMethod($method);
        $serviceClass = get_class($service);

        // auth validation
        /* @var $authName Auth */
        if (!($authName = $reflectionMethod->getParam(Definition::AUTH))) {
            throw new \LogicException(sprintf("Undefined Auth    on '%s::%s'", $serviceClass, $method));
        }

        /* @var $auth \Cawa\SwaggerServer\Auth\AbstractAuth */
        $authClass = '\\Cawa\\SwaggerServer\\Auth\\' . $authName->getAuth();
        $auth = new $authClass($this->module());

        if (!$auth->isAllowed($serviceClass)) {
            if (!$auth->promptAuth()) {
                throw new ResponseCode(sprintf("Unauthorized service '%s::%s'", $serviceClass, $method), 403);
            } else {
                App::end();
            }
        }

        $service->setAuth($auth);
        $service->setRenderer($renderer);

        return $service;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function normalizeType(string $type) : string
    {
        $type = ltrim($type, '\\');
        foreach (self::module()->namespaces as $name => $namespace) {
            if (strpos($type, $namespace->getClassNamespace()) !== false) {
                return $name . substr($type, strlen($namespace->getClassNamespace()) + 1);
            }
        }

        backtrace();

        return null;
    }

    /**
     * @param string|array
     *
     * @return bool
     */
    public function isPrimitive($types) : bool
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        $primitive = false;
        $primitiveType = [
            'int',
            'int32',
            'integer',
            'float',
            'double',
            'string',
            'bool',
            'boolean',
            'array',
            '\\Cawa\\SwaggerServer\\DateTime',
        ];

        foreach ($types as $type) {
            if (in_array($type, $primitiveType)) {
                $primitive = true;
            }
        }

        return $primitive;
    }

    /**
     * @var Model[]
     */
    protected $models = [];

    /**
     * @param string $type
     *
     * @return Model|null
     */
    protected function fetchModel(string $type)
    {
        if ($type == '\\Cawa\\SwaggerServer\\DateTime') {
            return null;
        }

        if (array_key_exists($type, $this->models)) {
            return $this->models[$type];
        }

        $this->models[$type] = new Model($type);

        foreach ($this->models[$type]->getDefinitions() as $property => $definition) {
            if (!$this->isPrimitive($definition->getType())) {
                foreach ($definition->getType() as $currentType) {
                    if (substr($currentType, -2) == '[]') {
                        $currentType = substr($currentType, 0, -2);
                    }

                    $this->fetchModel($currentType);
                }
            }
        }

        return $this->models[$type];
    }
}
