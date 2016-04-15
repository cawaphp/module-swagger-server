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
use Cawa\Renderer\Phtml;
use Cawa\Controller\ViewController;
use Cawa\Controller\ViewData;
use Cawa\SwaggerServer\AbstractService;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\Header;
use Cawa\SwaggerServer\Reflection\Definitions\Response;
use Cawa\SwaggerServer\Reflection\Definitions\Throws;
use Cawa\SwaggerServer\Tools;

class Responses extends ViewController
{
    use Phtml;
    use ViewData;
    use Tools;
    use TranslatorFactory;

    public function __construct(AbstractService $service, string $method)
    {
        $reflection = $service->getReflectionMethod($method);

        if ($reflection->getDefinition(Definition::RESPONSE)) {
            /* @var $response Response */
            foreach ($reflection->getDefinition(Definition::RESPONSE) as $response) {
                $types = $response->getType();
                if ($types) {
                    foreach ($types as $i => $type) {
                        if (!$this->isPrimitive($type)) {
                            $type = $this->normalizeType($type);
                        }
                        $types[$i] = $type;
                    }
                }

                $this->data['responses'][$response->getStatusCode()] = [
                    'type' => $types,
                    'comment' => $response->getComment(),
                ];
            }
        }

        if ($reflection->getDefinition(Definition::THROWS)) {
            /* @var $throw Throws */
            foreach ($reflection->getDefinition(Definition::THROWS) as $throw) {
                $this->data['responses'][$throw->getStatusCode()] = [
                    'type' => [$throw->getType()],
                    'comment' => $throw->getComment(),
                ];
            }
        }

        if ($reflection->getDefinition(Definition::HEADER)) {
            /* @var $header Header */
            foreach ($reflection->getDefinition(Definition::HEADER) as $header) {
                $this->data['responses'][$header->getStatusCode()]['headers'][] = [
                    'name' => $header->getHeaderName(),
                    'type' => $header->getType(),
                    'comment' => $header->getComment(),
                ];
            }
        }
    }
}
