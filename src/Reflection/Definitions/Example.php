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

class Example extends Comment implements Definition
{
    /**
     * @var string
     */
    private $type;

    /**
     * @return string
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
     * @var array
     */
    private $example;

    /**
     * @return string
     */
    public function getExample() : int
    {
        return $this->example;
    }

    /**
     * @param array $explode
     */
    public function __construct(array $explode)
    {
        $statusCode = array_shift($explode);

        if (is_numeric($statusCode) && $statusCode >= 100 && $statusCode < 700) {
            $this->statusCode = (int) $statusCode;
        } else {
            $this->statusCode = 200;
            array_unshift($explode, $statusCode);
        }

        $comments = [];

        while (substr($explode[0], 0, 1) !== '{') {
            $comments[] = array_shift($explode);
        }

        $this->example = json_decode(implode(' ', $explode), true);

        parent::__construct(implode(' ', $comments));
    }
}
