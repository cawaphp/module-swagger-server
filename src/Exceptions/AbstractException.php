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

namespace Cawa\SwaggerServer\Exceptions;

use Cawa\App\AbstractApp;
use Cawa\Net\Ip;

abstract class AbstractException extends \Exception
{
    /**
     * @var string
     */
    public $detail;

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @return array
     */
    public function display()
    {
        $hide = true;
        if (AbstractApp::instance()->isAdmin() || Ip::isLocal()) {
            $hide = false;
        }

        if ($this->code == 500 && $hide) {
            return [
                'status' => $this->code,
                'message' => 'Internal server error',
            ];
        }

        $return = [
            'status' => $this->code,
            'message' => $this->message,
        ];

        if ($this->getPrevious()) {
            if (isset($return['message'])) {
                $message = $return['message'];
            }

            $return = array_merge($return, $this->export($this->getPrevious()));

            if (isset($message) && $message) {
                $return['message'] = $message;
            }

            if ($this->getPrevious()->getPrevious()) {
                $return['previous'] = $this->export($this->getPrevious()->getPrevious());
            }
        }

        return $return;
    }

    /**
     * @param \Throwable $exception
     *
     * @return array
     */
    private function export(\Throwable $exception)
    {
        $return = [];
        $return['type'] = get_class($exception);
        $return['stack_trace'] = explode("\n", $exception->getTraceAsString());
        $return['code'] = $exception->getCode();
        $return['message'] = $exception->getMessage();

        foreach (get_object_vars($exception) as $key => $value) {
            if ($key != 'message' && $key != 'code') {
                $return['detail'][$key] = (string) $exception->$key;
            }
        }

        if (isset($return['detail'])) {
            $return['detail'] = json_encode($return['detail']);
        }

        return $return;
    }
}
