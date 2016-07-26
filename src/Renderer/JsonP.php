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

use Cawa\App\HttpFactory;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class JsonP extends JsonEncapsulated
{
    use HttpFactory;

    /**
     * {@inheritdoc}
     */
    public function getContentType() : string
    {
        return 'application/javascript; charset=utf-8';
    }
    /**
     * {@inheritdoc}
     */
    public function getErrorContentType() : string
    {
        return $this->getContentType();
    }

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
        $callback = self::request()->getUri()->getQuery('callback');
        if (!$callback) {
            throw new ResponseCode('Missing callback for jsonp renderer', 422);
        }

        $json = parent::render($statusCode, $headers, $data);

        return $callback . '(' . $json . ');';
    }
}
