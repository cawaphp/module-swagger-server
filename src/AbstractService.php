<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types = 1);

namespace Cawa\SwaggerServer;

use Cawa\App\App;
use Cawa\SwaggerServer\Auth\AbstractAuth;
use Cawa\SwaggerServer\Exceptions\ResponseCode;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\Param;
use Cawa\SwaggerServer\Reflection\ReflectionMethod;
use Cawa\SwaggerServer\Renderer\AbstractRenderer;
use Cawa\Uri\Uri;

abstract class AbstractService
{
    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @var string
     */
    private $namespace;

    /**
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function setNamespace(string $namespace) : self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @var int
     */
    private $version;

    /**
     * @return int
     */
    public function getVersion() : int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function setVersion(int $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return array
     */
    public function getMethods() : array
    {
        $return = [];
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() != 'Cawa\\SwaggerServer\\AbstractService') {
                $return[] = $method->getName();
            }
        }

        return $return;
    }

    /**
     * @var ReflectionMethod[]
     */
    private $reflectionMethod = [];

    /**
     * @param string $method
     *
     * @return ReflectionMethod
     */
    public function getReflectionMethod(string $method)
    {
        if (!isset($this->reflectionMethod[$method])) {
            $this->reflectionMethod[$method] = new ReflectionMethod($this, $method);
        }

        return $this->reflectionMethod[$method];
    }

    /**
     * @var AbstractAuth
     */
    private $auth;

    /**
     * @param AbstractAuth $auth
     */
    public function setAuth(AbstractAuth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @var AbstractRenderer
     */
    private $renderer;

    /**
     * @param AbstractRenderer $renderer
     */
    public function setRenderer(AbstractRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    protected function setStatusCode(int $statusCode) : self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    protected function addHeader(string $name, string $value) : self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return array
     */
    public function call(string $method)
    {
        $args = $this->getArgs($method);

        $return = call_user_func_array([$this, $method], $args);

        $out = $this->renderer->render($this->statusCode, $this->headers, $return);

        App::response()->addHeader('Content-Type', $this->renderer->getContentType());
        if ($this->renderer->sendHeader()) {
            foreach ($this->headers as $name => $value) {
                App::response()->addHeader('x-' . ucfirst($name), $value);
            }

            App::response()->setStatus($this->statusCode);
        }

        return $out;
    }

    /**
     * @param string $method
     *
     * @throws ResponseCode
     * @throws \Exception
     *
     * @return array
     */
    private function getArgs(string $method) : array
    {
        if (App::request()->getMethod() == 'POST') {
            $data = file_get_contents('php://input');

            // hack for multipart form
            if (!$data) {
                $data = http_build_query($_POST);
            }
        } else {
            $data = App::request()->getUri()->getQuerystring();
        }

        if (App::request()->getMethod() == 'POST' &&
            App::request()->getHeader('Content-Encoding') == 'gzip') {
            $data = gzdecode($data);
        }

        $requestData = $data ? $this->getRequestData($data) : [];

        $return = [];

        /* @var $parametersDefinition Param[] */
        $parametersDefinition = $this->reflectionMethod[$method]->getParam(Definition::PARAM);

        /* @var $parameter \ReflectionParameter */
        foreach ($this->reflectionMethod[$method]->getParameters() as $i => $parameter) {
            $parameterName = $parameter->getName();

            $value = isset($requestData[$parameterName]) ? $requestData[$parameterName] : null;

            if (is_null($value) && !$parameter->isOptional()) {
                throw new ResponseCode("Missing required parameters '$parameterName'", 422);
            }

            if (!isset($parametersDefinition[$i])) {
                throw new \Exception(sprintf(
                    "Missing phpdoc on param '%s' for method '%s:%s'",
                    $parameterName,
                    get_class($this),
                    $method
                ));
            }

            if ($parametersDefinition[$i]->getVar() !== $parameterName) {
                throw new \Exception(sprintf(
                    "Invalid phpdoc on param '%s' with name '%s' for method '%s:%s'",
                    $parameterName,
                    $parametersDefinition[$i]->getVar(),
                    get_class($this),
                    $method
                ));
            }

            $value = $this->getFinalValue(
                $parametersDefinition[$i]->getType(),
                $parametersDefinition[$i]->getVar(),
                $value,
                $parameter->isOptional()
            );

            if ($parametersDefinition[$i]->getValidation()) {
                $value = $parametersDefinition[$i]->validateCondition($parameterName, $value);
            }

            if ($parameter->isDefaultValueAvailable() && $value === null) {
                $value = $parameter->getDefaultValue();
            }

            $return[] = $value;
        }

        return $return;
    }

    /**
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @param bool $optionnal
     *
     * @throws ResponseCode
     * @throws \Exception
     *
     * @return array|bool|float|string
     */
    private function getFinalValue(string $type, string $name, $value, bool $optionnal)
    {
        if ($optionnal && ($value === null || $value === '')) {
            return null;
        }

        if (strpos($type, '[]') !== false) {
            $aReturn = [];
            foreach (is_array($value) ? $value : [$value] as $sKey => $currentValue) {
                $aReturn[$sKey] = $this->getFinalValue(str_replace('[]', '', $type), $name, $currentValue, $optionnal);
            }

            return $aReturn;
        }

        switch ($type) {
            case 'int':
                $value = filter_var($value, FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
                if (is_null($value)) {
                    throw new ResponseCode("Invalid integer parameters '$name' with value '$value'", 422);
                }

                break;

            case 'float':
                $value = filter_var($value, FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
                if (is_null($value)) {
                    throw new ResponseCode("Invalid float parameters '$name' with value '$value'", 422);
                }

                break;

            case 'string':
                if (!is_string($value) || !$value) {
                    throw new ResponseCode("Invalid string parameters '$name' with value '$value'", 422);
                }
                break;

            case 'bool':
                if ($value !== '1' &&
                    $value !== 1 &&
                    $value !== '0' &&
                    $value !== 0 &&
                    $value !== 'true' &&
                    $value !== 'false'
                ) {
                    throw new ResponseCode("Invalid bool parameters '$name' with value '$value'", 422);
                }

                if ($value == '1') {
                    $value = true;
                } elseif ($value === 'true') {
                    $value = true;
                } elseif ($value == '0') {
                    $value = false;
                } elseif ($value === 'false') {
                    $value = false;
                }

                if (!is_bool($value)) {
                    throw new ResponseCode("Invalid bool parameters '$name' with value '$value'", 422);
                }
                break;

            case '\\Cawa\\SwaggerServer\\DateTime':
                $value = DateTime::createFromInput($value);
                break;

            case 'array':
                if (!is_array($value)) {
                    $jsonValue = @json_decode($value, true);

                    if ($jsonValue === false || !is_array($jsonValue)) {
                        throw new ResponseCode("Invalid array parameters '$name' with value '$value'", 422);
                    }

                    $value = $jsonValue;
                }
                break;

            case 'mixed':
                if (is_numeric($value)) {
                    $value = 0 + $value;
                }

                break;

            default:
                throw new \Exception('Invalid ' . $type . " parameters '$name' with value '$value'");
                break;
        }

        return $value;
    }

    /**
     * @param string $data
     *
     * @return array
     */
    private function getRequestData(string $data) : array
    {
        if (preg_match_all('`\\&([^=]+)\\=`', '&' . $data, $matches)) {
            foreach (array_count_values($matches[1]) as $key => $count) {
                if ($count <= 1) {
                    continue;
                }
                if (stripos($key, '[') !== false) {
                    continue;
                }

                $data = str_replace('&' . $key . '=', '&' . $key . '[]=', $data);

                if (strpos($data, $key . '=') === 0) {
                    $data = $key . '[]' . substr($data, strpos($data, '='));
                }
            }
        }

        parse_str($data, $return);

        return is_array($return) ? $return : [];
    }

    /**
     * @param string $method
     *
     * @return string
     */
    public function getUri(string $method) : string
    {
        $serviceUri = App::router()->getUri('swagger.request', [
            'renderer' => 'Json',
            'version' => $this->getVersion(),
            'namespace' => $this->getNamespace(),
            'service' => $this->getName(),
            'method' => $method,
        ]);

        $uri = new Uri();
        $uri->setPath($serviceUri);
        $uri->setQuerystring();
        $uri->setFragment();

        return $uri->get(false);
    }
}
