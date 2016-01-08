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
                $sMessage = $return['message'];
            }

            $return = array_merge($return, $this->export($this->getPrevious()));

            if (isset($sMessage) && $sMessage) {
                $return['message'] = $sMessage;
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
        $aReturn = [];
        $aReturn['type'] = get_class($oException);
        $aReturn['stack_trace'] = explode("\n", $oException->getTraceAsString());
        $aReturn['code'] = $oException->getCode();
        $aReturn['message'] = $oException->getMessage();

        foreach (get_object_vars($oException) as $sKey => $mValue) {
            if ($sKey != 'message' && $sKey != 'code') {
                $aReturn['detail'][$sKey] = (string) $oException->$sKey;
            }
        }

        if (isset($aReturn['detail'])) {
            $aReturn['detail'] = json_encode($aReturn['detail']);
        }

        return $aReturn;
    }
}
