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
     * @param string $value
     *
     * @throws ResponseCode
     * @throws \Exception
     *
     * @return $this|self
     */
    public static function createFromInput($value)
    {
        $timestamp = strtotime($value);

        try {
            // trim java ms
            $value = preg_replace('`(\\.[0-9]{3})\\+`', '+', $value);

            $datetime = self::createFromFormat(self::DATE_FORMAT, $value);

            if ($timestamp != $datetime->getTimestamp()) {
                throw new ResponseCode("Invalid datetime with value '$value'", 422);
            }
        } catch (\Exception $exception) {
            throw new ResponseCode("Invalid datetime format with value '$value'", 422);
        }

        return $datetime;
    }

    /**
     * Parse a string into a new DateTime object according to the specified format.
     *
     * @param string $format format accepted by date()
     * @param string $time string representing the time
     * @param \DateTimeZone $timezone a DateTimeZone object representing the desired time zone
     *
     * @throws \Exception
     *
     * @return $this|self
     *
     * @see http://php.net/manual/en/datetime.createfromformat.php
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
