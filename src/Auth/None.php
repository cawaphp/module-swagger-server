<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Cawa\SwaggerServer\Auth;

class None extends AbstractAuth
{
    /**
     * @param string $service
     *
     * @return bool
     */
    public function isAllowed(string $service) : bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function promptAuth() : bool
    {
        return false;
    }
}
