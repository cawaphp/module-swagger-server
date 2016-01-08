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

class Response extends Comment implements Definition
{
    /**
     * @var array
     */
    private $type;

    /**
     * @return array
     */
    public function getType()
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
    public function __construct(array $explode = null)
    {
        if (!is_null($explode)) {
            $statusCode = array_shift($explode);

            if (is_numeric($statusCode) && $statusCode >= 100 && $statusCode < 700) {
                $this->statusCode = (int) $statusCode;
            } else {
                $this->statusCode = 200;
                array_unshift($explode, $statusCode);
            }

            if ($this->statusCode !== 204) {
                $this->type = explode('|', trim(array_shift($explode)));
            }

            parent::__construct(implode(' ', $explode));
        }
    }

    /**
     * @param ReturnType $return
     */
    public function fromReturn(ReturnType $return)
    {
        $this->statusCode = 200;
        $this->type = $return->getType();
        $this->comment = null;
    }
}
