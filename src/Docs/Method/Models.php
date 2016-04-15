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

namespace Cawa\SwaggerServer\Docs\Method;

use Cawa\Intl\TranslatorFactory;
use Cawa\Renderer\PhtmlTrait;
use Cawa\Controller\ViewController;
use Cawa\Controller\ViewDataTrait;
use Cawa\SwaggerServer\AbstractService;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\Response;
use Cawa\SwaggerServer\ToolsTrait;

class Models extends ViewController
{
    use ToolsTrait;
    use TranslatorFactory;
    use ViewDataTrait;
    use PhtmlTrait;

    /**
     * Models constructor.
     *
     * @param AbstractService $service
     * @param string $method
     */
    public function __construct(AbstractService $service, string $method)
    {
        $reflection = $service->getReflectionMethod($method);

        if ($reflection->getDefinition(Definition::RESPONSE)) {
            /* @var $response Response */
            foreach ($reflection->getDefinition(Definition::RESPONSE) as $response) {
                if (!$response->getType()) {
                    continue;
                }

                if (!$this->isPrimitive($response->getType())) {
                    foreach ($response->getType() as $type) {
                        $this->fetchModel($type);
                    }
                }
            }
        }

        foreach ($this->models as $name => $model) {
            $modelData = [
                'properties' => []
            ];

            foreach ($model->getDefinitions() as $property => $definition) {
                $propertyData = [
                    'subtype' => $definition->getSubType(),
                    'nullable' => $definition->isNullable(),
                    'comment' => $definition->getComment()
                ];

                foreach ($definition->getType() as $type) {
                    if (!$this->isPrimitive($type)) {
                        $type = $this->normalizeType($type);
                    }
                    $propertyData['types'][] = $type;
                }
                $modelData['properties'][$property] = $propertyData;
            }

            $this->data['models'][$this->normalizeType($name)] = $modelData;
        }
    }
}
