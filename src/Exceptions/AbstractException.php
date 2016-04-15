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

namespace Cawa\SwaggerServer\Exceptions;

use Cawa\Net\Ip;

abstract class AbstractException extends \Exception
{
    /**
     * @var string
     */
    public $detail;

    /**
     * @return array
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
        if (Ip::isAdmin() || Ip::isLocal()) {
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
     * @param \Throwable $oException
     *
     * @return array
     */
    private function export(\Throwable $oException)
    {
        $return = [];
        $return['type'] = get_class($oException);
        $return['stack_trace'] = explode("\n", $oException->getTraceAsString());
        $return['code'] = $oException->getCode();
        $return['message'] = $oException->getMessage();

        foreach (get_object_vars($oException) as $sKey => $mValue) {
            if ($sKey != 'message' && $sKey != 'code') {
                $return['detail'][$sKey] = (string) $oException->$sKey;
            }
        }

        if (isset($return['detail'])) {
            $return['detail'] = json_encode($return['detail']);
        }

        return $return;
    }
}
