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

namespace Cawa\SwaggerServer\ExamplesService;

use Cawa\SwaggerServer\AbstractService;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class Response extends AbstractService
{
    /**
     * Return a simple random string on http header
     * can return an exception if $success = false
     *
     * @httpmethod POST
     * @auth Basic
     *
     * @param bool $success
     *
     * @throws ResponseCode 422 exception with a the custom header
     *
     * @return void
     *
     * @response 204 successful 204 response
     *
     *
     * @header 204 string generatedHeader The generated string
     * @header 422 string generatedHeader The generated string
     */
    public function emptyResponseWithHeader(bool $success = true)
    {
        $type = new Type();
        $return = $type->string(rand(5, 10));

        $this->addHeader('generatedHeader', $return);

        if ($success) {
            $this->setStatusCode(204);

            return ;
        } else {
            throw new ResponseCode('Exception with header', 422);
        }
    }
}

class Response_v2 extends Response
{
}
