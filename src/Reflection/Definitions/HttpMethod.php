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

namespace Cawa\SwaggerServer\Reflection\Definitions;

class HttpMethod implements Definition
{
    /**
     * @var string
     */
    private $httpMethod;

    /**
     * @return string
     */
    public function getHttpMethod() : string
    {
        return $this->httpMethod;
    }

    /**
     * @param string $httpMethod
     */
    public function __construct(string $httpMethod)
    {
        $this->httpMethod = $httpMethod;
    }
}
