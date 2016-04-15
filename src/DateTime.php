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

use Cawa\SwaggerServer\Exceptions\ResponseCode;

class DateTime extends \DateTime implements \JsonSerializable
{
    const DATE_FORMAT = 'Y-m-d\\TH:i:sP';
    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->format(self::DATE_FORMAT);
    }

    /**
     * @param string $mValue
     *
     * @throws ResponseCode
     * @throws \Exception
     *
     * @return DateTime
     */
    public static function createFromInput($mValue)
    {
        $timestamp = strtotime($mValue);

        try {
            // trim java ms
            $mValue = preg_replace('`(\\.[0-9]{3})\\+`', '+', $mValue);

            $datetime = DateTime::createFromFormat(self::DATE_FORMAT, $mValue);

            if ($timestamp != $datetime->getTimestamp()) {
                throw new ResponseCode("Invalid datetime with value '$mValue'", 422);
            }
        } catch (\Exception $exception) {
            throw new ResponseCode("Invalid datetime format with value '$mValue'", 422);
        }

        return $datetime;
    }

    /**
     * Parse a string into a new DateTime object according to the specified format
     *
     * @param string $format Format accepted by date().
     * @param string $time String representing the time.
     * @param \DateTimeZone $timezone A DateTimeZone object representing the desired time zone.
     *
     * @throws \Exception
     *
     * @return DateTime
     *
     * @link http://php.net/manual/en/datetime.createfromformat.php
     */
    public static function createFromFormat($format, $time, $timezone = null)
    {
        if ($timezone) {
            $datetime = date_create_from_format($format, $time, $timezone);
        } else {
            $datetime = date_create_from_format($format, $time, new \DateTimeZone(date_default_timezone_get()));
        }

        if (!$datetime) {
            throw new \Exception(sprintf("Invalid datetime object created from '%s' with format '%s'", $format, $time));
        }

        $return = new self();
        $return->setTimestamp($datetime->getTimestamp());
        $return->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $return;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->format(self::DATE_FORMAT);
    }
}