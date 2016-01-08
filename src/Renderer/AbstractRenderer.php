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

namespace Cawa\SwaggerServer\Renderer;

use Cawa\App\App;
use Cawa\Error\Handler;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

abstract class AbstractRenderer
{

    /**
     * @param int $statusCode
     * @param array $headers
     * @param $data
     *
     * @return string
     */
    abstract public function render(int $statusCode, array $headers, $data) : string;

    /**
     * @return string
     */
    abstract public function getContentType() : string;

    /**
     * @return string
     */
    abstract public function getErrorContentType() : string;

    /**
     * @return bool
     */
    abstract public function sendHeader() : bool;

    /**
     *
     */
    public function registerExceptionHandler()
    {
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * @param \Throwable $exception
     *
     * @return bool
     */
    public function exceptionHandler(\Throwable $exception)
    {
        if ($exception instanceof ResponseCode) {
            try {
                $out = $this->render($exception->getCode(), [], $exception->display());
            } catch (\Throwable $exception) {
                return $this->exceptionHandler($exception);
            }

            // debug on dev / display trace
            if (!(App::env() == App::DEV && ob_get_length() > 0)) {
                App::response()->addHeader('Content-Type', $this->getErrorContentType());
            }

            App::response()->setStatus($exception->getCode());
            App::response()->setBody($out);
            App::end();
        } else {
            Handler::log($exception);

            if (App::env() == App::DEV) {
                Handler::exceptionHandler($exception);
            } else {
                $throw = new ResponseCode($exception->getMessage(), 500, $exception);
                $this->exceptionHandler($throw);
            }
        }

        return true;
    }
}
