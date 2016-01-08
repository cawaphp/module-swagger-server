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

class ReturnType extends Comment implements Definition
{
    /**
     * @var array
     */
    private $type;

    /**
     * @return array
     */
    public function getType() : array
    {
        return $this->type;
    }

    /**
     * @param array $explode
     */
    public function __construct(array $explode)
    {
        $this->type = explode('|', trim(array_shift($explode)));

        parent::__construct(implode(' ', $explode));
    }
}
