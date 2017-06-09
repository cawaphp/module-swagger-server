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

namespace Cawa\SwaggerServer\ExamplesService;

use Cawa\SwaggerServer\AbstractService;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class Exception extends AbstractService
{
    /**
     * Return a internal server error.
     *
     * @httpmethod GET
     * @auth None
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function internalServerError()
    {
        throw new \Exception('This is a internal server error');
    }

    /**
     * Return a client error.
     *
     * @httpmethod GET
     *
     * @param int $status the type of exception @validation(in:300;301;302;303;304;305;306;307;400;401;402;403;404;405;406;407;408;409;410;411;412;413;414;415;416;417;422;423;424;425;426;449)
     *
     * @auth None
     *
     * @throws ResponseCode
     *
     * @return string
     */
    public static function exception($status)
    {
        throw new ResponseCode(sprintf("This is an exception with status '%s'", $status), $status);
    }
}

class Exception_v2 extends Exception
{
}
