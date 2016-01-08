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

namespace Cawa\SwaggerServer\Reflection;

class ReflectionMethod extends \ReflectionMethod
{
    use PhpDocParser;

    /**
     * @param mixed $class
     * @param string $name
     *
     * @throws \LogicException
     */
    public function __construct($class, $name)
    {
        parent::__construct($class, $name);

        if (!$this->getDocComment()) {
            throw new \LogicException(
                sprintf("Missing phpdoc on method '%s::%s()'", $this->getDeclaringClass()->getName(), $this->getName())
            );
        }

        $this->parsePhpDoc($this->getDocComment(), $this->getDeclaringClass()->getName(), $this->getName());
    }
}
