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

namespace Cawa\SwaggerServer\Auth;

use Cawa\SwaggerServer\Module;

abstract class AbstractAuth
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * @param string $service
     *
     * @return bool
     */
    abstract public function isAllowed(string $service) : bool;

    /**
     * @return bool
     */
    abstract public function promptAuth() : bool;
}
