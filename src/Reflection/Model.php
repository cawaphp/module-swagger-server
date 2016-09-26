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

namespace Cawa\SwaggerServer\Reflection;

use Cawa\SwaggerServer\Reflection\Definitions\Property;

class Model extends \ReflectionClass
{
    /**
     * @var Property[]
     */
    protected $definitions = [];

    /**
     * @return Property[]
     */
    public function getDefinitions() : array
    {
        return $this->definitions;
    }

    /**
     * @param mixed $class
     *
     * @throws \LogicException
     */
    public function __construct($class)
    {
        parent::__construct($class);

        foreach ($this->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $this->definitions[$reflectionProperty->getName()] = new Property(
                $class,
                $reflectionProperty->getDocComment()
            );
        }
    }
}
