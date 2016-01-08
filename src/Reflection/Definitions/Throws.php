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

class Throws extends Comment implements Definition
{
    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @return string
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    /**
     * @param array $explode
     */
    public function __construct(array $explode)
    {
        $this->type = array_shift($explode);

        $statusCode = array_shift($explode);

        if (is_numeric($statusCode) && $statusCode >= 100 && $statusCode < 700) {
            $this->statusCode = (int) $statusCode;
        } else {
            $this->statusCode = 500;
            array_unshift($explode, $statusCode);
        }

        parent::__construct(implode(' ', $explode));
    }
}
