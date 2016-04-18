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

use Cawa\App\HttpFactory;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\Header;
use Cawa\SwaggerServer\Reflection\Definitions\Param;
use Cawa\SwaggerServer\Reflection\Definitions\Response;
use Cawa\SwaggerServer\Reflection\Definitions\Throws;

/**
 * @mixin ApiController
 */
trait SwaggerGeneratorTrait
{
    use HttpFactory;

    /**
     * @param string $namespace
     * @param int $version
     *
     * @return array
     */
    private function getContainer(string $namespace, int $version) : array
    {
        return [
             'swagger' => '2.0',
             'info' => [
               'title' => $namespace,
               'version' => $version . '.0'
             ],
             'host' => $this->request()->getUri()->getHost(),
             'basePath' => '/Json/' . $namespace . '/v' . $version,
             'schemes' => [
                 $this->request()->getUri()->getScheme()
             ],
             'consumes' => [
                 'application/json',
             ],
             'produces' => [
               'application/json',
               'application/problem+json',
             ],
             'paths' => [],
             'securityDefinitions' => [
               'AuthAnkamaApiKey' => [
                   'type' => 'apiKey',
                   'name' => 'APIKEY',
                   'in' => 'header'
               ],
               'AuthPassword' => [
                   'type' => 'basic',
               ],

             ],
             'definitions' => ['ApiException' => [
                   'required' => [
                     'status',
                     'message'
                   ],
                   'properties' => [
                     'status' =>[
                       'type' => 'integer'
                     ],
                     'message' =>[
                       'type' => 'string'
                     ],
                     'type' =>[
                       'type' => 'string'
                     ],
                     'stack_trace' => [
                       'type' => 'array', 'items' => [
                         'type' => 'string'
                       ]
                     ],
                     'code' =>[
                       'type' => 'integer'
                     ],
                     'detail' =>[
                       'type' => 'string'
                     ],
                   ]
                 ]
             ]
           ];
    }

    /**
     * @param string $serviceName
     * @param AbstractService $service
     * @param string $method
     *
     * @throws \Exception
     *
     * @return array
     */
    private function generateSwaggerForService(
        string $serviceName,
        AbstractService $service,
        string $method = null
    ) : array {
        $path = $definition = [];

        foreach ($service->getMethods() as $currentMethod) {
            if (!is_null($method) && $currentMethod != $method) {
                continue;
            }

            $reflection = $service->getReflectionMethod($currentMethod);

            $currentPath = [
                'operationId' => $currentMethod,
                'tags' => [str_replace('\\', ' ', $serviceName)],
                'parameters' => [],
                'responses' => [],
            ];

            $plainTextResponse = true;

            $comment = $reflection->getDefinition(Definition::COMMENT) ?
                $reflection->getDefinition(Definition::COMMENT)->getComment() : null;

            $comment .= $reflection->getDefinition(Definition::COMMENT_LONG) ?
                "\n\n" . $reflection->getDefinition(Definition::COMMENT_LONG)->getComment() : '';

            if ($comment) {
                $currentPath = ['description' => $comment] + $currentPath;
            }

            if ($reflection->getDefinition(Definition::AUTH)->getAuth() != 'None') {
                $currentPath['security'] = ['Auth' . $reflection->getDefinition(Definition::AUTH)->getAuth() => []];
            }

            $httpMethod = $reflection->getDefinition(Definition::HTTP_METHOD)->getHttpMethod();

            if ($httpMethod == 'POST') {
                $currentPath['consumes'] = ['application/x-www-form-urlencoded'];
            }

            // parameters
            if ($reflection->getDefinition(Definition::PARAM)) {
                /* @var $parameter Param */
                foreach ($reflection->getDefinition(Definition::PARAM) as $i => $parameter) {
                    /* @var $reflectionParameter \ReflectionParameter */
                    $reflectionParameter = $reflection->getParameters()[$i];

                    $parameterRow = [
                        'name' => $parameter->getVar(),
                        'in' => $httpMethod == 'POST' ? 'formData' : 'query',
                        'required' => !$reflectionParameter->isOptional(),
                        // "minimum" => 1,
                        // "maximum" => 10000
                    ];

                    if ($reflectionParameter->isOptional() && $reflectionParameter->getDefaultValue()) {
                        $parameterRow['default'] = $reflectionParameter->getDefaultValue();
                    }

                    if ($parameter->getComment()) {
                        $parameterRow['description'] = $parameter->getComment();
                    }

                    $parameterRow = array_merge($parameterRow, $this->getSwaggertype(
                        $parameter->getType(),
                        $parameter->getSubType()
                    ));

                    if ($parameterRow['type'] == 'array') {
                        $parameterRow['collectionFormat'] = 'multi';
                    }

                    $currentPath['parameters'][] = $parameterRow;
                }
            } else {
                unset($currentPath['parameters']);
            }

            // response
            if ($reflection->getDefinition(Definition::RESPONSE)) {
                /* @var $response Response */
                foreach ($reflection->getDefinition(Definition::RESPONSE) as $i => $response) {
                    $swaggerResponse = [
                        'headers' => ['X-Duration' => ['description' => 'Api Server ResponseTime', 'type' => 'number']]
                    ];

                    if ($response->getComment()) {
                        $swaggerResponse['description'] = $response->getComment();
                    }

                    if (sizeof($response->getType()) > 1) {
                        throw new \LogicException('Polymorphism is not yet supported');
                    }

                    if ($response->getType()[0] !== null) {
                        $return = $this->getSwaggertype($response->getType()[0]);
                        $swaggerResponse['schema'] = $return;
                    }

                    if (!$this->isPrimitive($response->getType())) {
                        $plainTextResponse = false;
                    }

                    $currentPath['responses'][$response->getStatusCode()] = $swaggerResponse;
                }
            }

            // headers
            if ($reflection->getDefinition(Definition::HEADER)) {
                /* @var $header Header */
                foreach ($reflection->getDefinition(Definition::HEADER) as $i => $header) {
                    $name = 'X-' . ucfirst($header->getHeaderName());
                    $value = $this->getSwaggertype($header->getType());

                    if ($header->getComment()) {
                        $value['description'] = $header->getComment();
                    }

                    $currentPath['responses'][$header->getStatusCode()]['headers'][$name] = $value;
                }
            }

            // exceptions
            if ($reflection->getDefinition(Definition::THROWS)) {
                /* @var $throw Throws */
                foreach ($reflection->getDefinition(Definition::THROWS) as $i => $throw) {
                    //@TODO: handle > 600 statusCode
                }
            }

            if ($plainTextResponse) {
                $currentPath['produces'] = [
                    'application/json',
                    'application/problem+json',
                    'text/plain',
                ];
            }

            $path['/' . str_replace('\\', '/', $serviceName) . '/' . $currentMethod][strtolower($httpMethod)] =
            $currentPath;
        }

        foreach ($this->models as $name => $model) {
            $swaggerDefinition = [
                'required' => [],
                'properties' => []
            ];

            foreach ($model->getDefinitions() as $property => $currentDefinition) {
                if (!$currentDefinition->isNullable()) {
                    $swaggerDefinition['required'][] = $property;
                }

                if (sizeof($currentDefinition->getType()) > 1) {
                    throw new \LogicException('Polymorphism is not yet supported');
                }

                $swaggerProperty = $this->getSwaggertype(
                    $currentDefinition->getType()[0],
                    $currentDefinition->getSubType()
                );

                if ($currentDefinition->getComment()) {
                    $swaggerProperty = ['description' => $currentDefinition->getComment()] + $swaggerProperty;
                }

                $swaggerDefinition['properties'][$property] = $swaggerProperty;
            }

            $definition[$this->normalizeType($name)] = $swaggerDefinition;
        }

        return [$path, $definition];
    }

    /**
     * @param string $type
     * @param string $subType
     *
     * @throws \Exception
     *
     * @return array [<type>, <format>, <items>]
     */
    private function getSwaggertype($type, $subType = null)
    {
        switch ($type) {
            case 'int32':
                return ['type' => 'integer', 'format' => 'int32'];

            case 'int':
            case 'integer':
                return ['type' => 'integer', 'format' => 'int64'];

            case 'float':
                return ['type' => 'number', 'format' => 'float'];

            case 'double':
                return ['type' => 'number', 'format' => 'double'];

            case 'string':
                return ['type' => 'string'];

            case 'bool':
            case 'boolean':
                return ['type' => 'boolean'];

            case '\\Cawa\\SwaggerServer\\DateTime':
            case '\\DateTime':
                return ['type' => 'string', 'format' => 'date-time'];

            case 'array':
                if (!$subType) {
                    throw new \Exception('array is not supported without detail');
                }

                if ($subType == 'json') {
                    return ['type' => 'string'];
                }

                if (is_null($subType)) {
                    throw new \Exception('array is not supported without map detail');
                }

                if ($subType == 'string') {
                    $additionalProperties['type'] = 'string';
                }

                if ($subType == 'int') {
                    $additionalProperties['type'] = 'integer';
                    $additionalProperties['format'] = 'int64';
                }

                if (!isset($additionalProperties)) {
                    throw new \Exception('array is not supported without map[string] or map[int]');
                }

                return ['type' => 'object', 'additionalProperties' => $additionalProperties];

            default:
                if (substr($type, -2) == '[]') {
                    $collectionType = $this->getSwaggertype(substr($type, 0, -2));

                    return ['type' => 'array', 'items' => $collectionType];
                }

                $this->fetchModel($type);

                return ['type' => 'object', '$ref' => '#/definitions/' . $this->normalizeType($type)];
        }
    }

    /**
     * @param string $namespace
     * @param int $version
     * @param string|null $service
     * @param string|null $method
     *
     * @return array
     */
    protected function generateSwagger(
        string $namespace,
        int $version,
        string $service = null,
        string $method = null
    ) : array {
        $this->listServices($namespace, $version, $service);
        $return = $this->getContainer($namespace, $version);

        foreach (self::$servicesList as $serviceName => $service) {
            list($path, $defintions) = $this->generateSwaggerForService($serviceName, $service, $method);
            $return['paths'] = array_merge($return['paths'], $path);
            $return['definitions'] = array_merge($return['definitions'], $defintions);
        }

        return $return;
    }
}
