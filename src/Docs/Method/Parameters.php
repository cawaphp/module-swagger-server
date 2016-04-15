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
use Cawa\SwaggerServer\Reflection\Definitions\Param;

class Parameters extends ViewController
{
    use PhtmlTrait;
    use ViewDataTrait;
    use TranslatorFactory;

    public function __construct(AbstractService $service, string $method)
    {
        $reflection = $service->getReflectionMethod($method);

        $this->data['uri'] = $service->getUri($method);
        $this->data['method'] = $reflection->getDefinition(Definition::HTTP_METHOD)->getHttpMethod();

        if ($reflection->getDefinition(Definition::PARAM)) {
            /* @var $parameter Param */
            foreach ($reflection->getDefinition(Definition::PARAM) as $i => $parameter) {
                /* @var $reflectionParameter \ReflectionParameter */
                $reflectionParameter = $reflection->getParameters()[$i];

                $type = $parameter->getType();
                switch ($parameter->getType()) {
                    case 'int':
                    case 'int32':
                    case 'integer':
                        $inputType = 'number';
                        break;
                    case 'float':
                    case 'double':
                        $inputType = 'number';
                        $type = 'float';
                        break;
                    case 'bool':
                    case 'boolean':
                        $inputType = 'checkbox';
                        break;
                    case '\\Cawa\\SwaggerServer\\DateTime':
                        // $inputType = "datetime-local";
                        $inputType = 'text';
                        break;
                    default:
                        $inputType = 'text';
                }

                $parameterRow = [
                    'name' => $parameter->getVar(),
                    'comment' => $parameter->getComment(),
                    'type' => $type,
                    'inputType' => $inputType,
                    'subtype' => $parameter->getSubType(),
                    'validation' => $parameter->getValidation(),
                    'required' => !$reflectionParameter->isOptional(),
                ];

                if ($reflectionParameter->isOptional() && $reflectionParameter->getDefaultValue()) {
                    $parameterRow['default'] = $reflectionParameter->getDefaultValue();
                }

                $this->data['parameters'][] = $parameterRow;
            }
        }
    }
}
