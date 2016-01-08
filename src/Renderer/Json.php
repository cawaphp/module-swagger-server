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

use Cawa\SwaggerServer\Exceptions\ResponseCode;

class Json extends AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getContentType() : string
    {
        return 'application/json; charset=utf-8';
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorContentType() : string
    {
        return 'application/problem+json; charset=utf-8';
    }

    /**
     * {@inheritdoc}
     */
    public function sendHeader() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function render(int $statusCode, array $headers, $data) : string
    {
        if (is_null($data)) {
            $out = '';
        } elseif (is_bool($data)) {
            $out = $data ? 'true' : 'false';
        } elseif (is_string($data)) {
            $out = '"' . $data . '"';
        } elseif (!is_array($data) && !is_object($data)) {
            $out = (string) $data;
        } else {
            $out = json_encode($data);

            if ($out === false) {
                throw new ResponseCode(json_last_error_msg(), 500);
            }
        }

        return $out;
    }
}
