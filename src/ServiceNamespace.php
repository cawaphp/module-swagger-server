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

namespace Cawa\SwaggerServer;

class ServiceNamespace
{
    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @var string
     */
    private $classNamespace;

    /**
     * @return string
     */
    public function getClassNamespace() : string
    {
        return $this->classNamespace;
    }

    /**
     * @var array
     */
    private $versions;

    /**
     * @return string
     */
    public function getVersions() : array
    {
        return $this->versions;
    }

    /**
     * @param string $name
     * @param string $classNamespace
     * @param array $versions
     */
    public function __construct(string $name, string $classNamespace, array $versions)
    {
        $this->name = $name;
        $this->classNamespace = $classNamespace;
        $this->versions = $versions;
    }
}
