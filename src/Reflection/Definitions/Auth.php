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

namespace Cawa\SwaggerServer\Reflection\Definitions;

class Auth implements Definition
{
    /**
     * @var string
     */
    private $auth;

    /**
     * @return string
     */
    public function getAuth() : string
    {
        return $this->auth;
    }

    /**
     * @param string $auth
     */
    public function __construct(string $auth)
    {
        $this->auth = $auth;
    }
}
