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

namespace Cawa\SwaggerServer\Renderer;

class JsonEncapsulated extends Json
{
    /**
     * {@inheritdoc}
     */
    public function sendHeader() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(int $statusCode, array $headers, $data) : string
    {
        $json = parent::render($statusCode, $headers, $data);

        if (is_string($json)) {
            $json = json_encode($json);
        }

        $out = '{"code":' . $statusCode . ',"data":' . $json . ',"headers":' . json_encode($headers) . '}';

        return $out;
    }
}
