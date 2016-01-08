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

namespace Cawa\SwaggerServer\ExamplesService;

use Cawa\SwaggerServer\AbstractService;
use Cawa\SwaggerServer\DateTime;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class Type extends AbstractService
{
    /**
     * Return a simple random string
     *
     * taken from character list : 0123456789abcdefghijklmnopqrstuvwxyz
     *
     * @httpmethod GET
     * @auth None
     *
     * @param int $length the length of generated string
     *
     * @return string the generated random string
     */
    public function string(int $length = 8)
    {
        $list = 'abcdefghijklmnopqrstuvwxyz';
        $return = '';
        for ($i = 0; $i < $length; $i++) {
            $return .= $list[rand(0, strlen($list) - 1)];
        }

        return $return;
    }

    /**
     * Return a random integer
     *
     * taken between $min and $max value
     *
     * @httpmethod GET
     * @auth None
     *
     * @param int $min the min value @validation(gte:0|lte:20)
     * @param int $max the max value @validation(gte:0|lt:20)
     *
     * @throws ResponseCode 422 max must be greater than min
     *
     * @return int
     */
    public function integer(int $min, int $max)
    {
        if ($max < $min) {
            throw new ResponseCode('max must be greater than min', 422);
        }

        return rand($min, $max);
    }

    /**
     * Return a random float
     *
     * with 2 decimal taken between $min and $max value
     *
     * @httpmethod GET
     * @auth None
     *
     * @param float $min the min value @validation(gte:0|lte:20)
     * @param float $max the max value @validation(gte:0|lt:20)
     *
     * @throws ResponseCode 422 max must be greater than min
     *
     * @return float the generated value
     */
    public function float(float $min, float $max)
    {
        if ($max < $min) {
            throw new ResponseCode('max must be greater than min', 422);
        }

        return rand((int) $min * 100, (int) $max * 100) / 100;
    }

    /**
     * Return a random boolean
     *
     * @httpmethod GET
     * @auth None
     *
     * @return bool the generated value
     */
    public function boolean()
    {
        return (bool) rand(0, 1);
    }

    /**
     * Return a date + 1 day
     *
     * @httpmethod GET
     * @auth None
     *
     * @param \Cawa\SwaggerServer\DateTime $date the initial date
     *
     * @return \Cawa\SwaggerServer\DateTime the generated datetime
     */
    public function date(DateTime $date)
    {
        return ['value' => $date->add(new \DateInterval('P1D'))];
    }

    /**
     * Return a random object
     *
     * with all possible primitive type
     *
     * @httpmethod GET
     * @auth None
     *
     * @param float $min the min value @validation(gte:0|lte:20)
     * @param float $max the max value @validation(gte:0|lt:20)
     *
     * @throws ResponseCode 422 max must be greater than min
     *
     * @return \Cawa\SwaggerServer\ExamplesService\Object
     */
    public function object(float $min, float $max)
    {
        $populate = function (bool $extended = false) use ($min, $max) {
            $class = 'Cawa\\SwaggerServer\\ExamplesService\\' . ($extended ? 'ObjectExtended' : 'Object');
            $object = new $class();
            $object->string = $this->String((int) $max);
            if ($extended) {
                $object->extendString = $this->String((int) $max);
            }
            $object->integer = $this->Integer((int) $min, (int) $max);
            $object->float = $this->Float((int) $min, (int) $max);
            $object->boolean = $this->Boolean();
            $object->integerMap = [
                $this->String((int) $max) => $this->Integer((int) $min, (int) $max),
                $this->String((int) $max) => $this->Integer((int) $min, (int) $max),
                $this->String((int) $max) => $this->Integer((int) $min, (int) $max),
            ];

            $object->stringMap = [
                $this->String((int) $max) => $this->String((int) $max),
                $this->String((int) $max) => $this->String((int) $max),
                $this->String((int) $max) => $this->String((int) $max),
            ];
            $object->datetime = $this->Date(new DateTime());

            return $object;
        };

        $return = $populate();

        for ($i = 0; $i < 3; $i++) {
            $object = $populate(true);
            $return->objects[] = $object;
        }

        return $return;
    }
}

class Object
{
    /**
     * Random string
     *
     * @var string
     */
    public $string;

    /**
     * Random integer
     *
     * @var int
     */
    public $integer;

    /**
     * Random float
     *
     * @var int
     */
    public $float;

    /**
     * Random boolean
     *
     * @var bool
     */
    public $boolean;

    /**
     * Random map of string
     *
     * @var array map[int]
     */
    public $integerMap;

    /**
     * Random map of string
     *
     * @var array map[string]
     */
    public $stringMap;

    /**
     * the generated datetime
     *
     * @var \Cawa\SwaggerServer\DateTime
     */
    public $datetime;

    /**
     * @var \Cawa\SwaggerServer\ExamplesService\ObjectExtended[]
     */
    public $objects = [];

    /**
     * nullable properties
     * always null
     *
     * @nullable
     *
     * @var string
     */
    public $null = [];
}

class ObjectExtended extends Object
{
    /**
     * Random string
     *
     * @var string
     */
    public $extendString;
}

class Type_v2 extends Type
{
    /**
     * Return a simple random string (only available on v2)
     *
     * taken from character list : 0123456789abcdefghijklmnopqrstuvwxyz
     *
     * @httpmethod GET
     * @auth None
     *
     * @param int $length the length of generated string
     *
     * @return string the generated random string
     */
    public function removeString(int $length = 8)
    {
        $list = 'abcdefghijklmnopqrstuvwxyz';
        $return = '';
        for ($i = 0; $i < $length; $i++) {
            $return .= $list[rand(0, strlen($list) - 1)];
        }

        return $return;
    }
}
