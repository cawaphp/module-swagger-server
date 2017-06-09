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

interface Definition
{
    const COMMENT = 'comment';
    const COMMENT_LONG = 'commentLong';
    const PARAM = 'param';
    const RETURNS = 'return';
    const THROWS = 'throws';
    const RESPONSE = 'response';
    const EXAMPLE = 'example';
    const HEADER = 'header';
    const AUTH = 'auth';
    const HTTP_METHOD = 'httpmethod';
}
