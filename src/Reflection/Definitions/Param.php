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

namespace Cawa\SwaggerServer\Reflection\Definitions;

use Cawa\Net\Ip;
use Cawa\SwaggerServer\Exceptions\ResponseCode;

class Param extends Comment implements Definition
{
    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @var string
     */
    private $subType;

    /**
     * @return string
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @var array
     */
    private $validations = [];

    /**
     * @return array
     */
    public function getValidation() : array
    {
        return $this->validations;
    }

    /**
     * @var string
     */
    private $var;

    /**
     * @return string
     */
    public function getVar() : string
    {
        return $this->var;
    }

    /**
     * @param array $explode
     */
    public function __construct(array $explode)
    {
        if (sizeof($explode) < 2) {
            throw new \Exception('Missing param for Phpdoc comment');
        }

        $this->type = array_shift($explode);
        $this->var = trim(array_shift($explode), '$');

        $comment = implode(' ', $explode);

        if (preg_match('`@detail\\((.+)\\)`', $comment, $matches)) {
            $this->subType = trim($matches[1]);
            $comment = trim(str_replace($matches[0], '', $comment));
        }

        if ($this->type == 'array') {
            $this->subType = 'json';
        }

        if (preg_match('`@validation\\((.+)\\)`', $comment, $matches)) {
            foreach (explode('|', $matches[1]) as $validation) {
                $condition = explode(':', trim($validation));
                if (strpos($condition[1], ';') !== false) {
                    $condition[1] = explode(';', $condition[1]);
                }

                $this->validations[] = $condition;
            }

            $comment = trim(str_replace($matches[0], '', $comment));
        }

        parent::__construct($comment);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @throws ResponseCode
     * @throws \Exception
     *
     * @return array|bool|float|string
     */
    public function validateCondition(string $name, $value)
    {
        foreach ($this->validations as $validation) {
            $success = true;
            list($condition, $conditionValue) = $validation;
            $sConditionValue = is_array($conditionValue) ? implode(';', $conditionValue) : $conditionValue;

            switch ($condition) {
                case 'gte':
                    if ($value < $conditionValue) {
                        $success = false;
                    }
                    break;

                case 'gt':
                    if ($value <= $conditionValue) {
                        $success = false;
                    }
                    break;

                case 'lte':
                    if ($value > $conditionValue) {
                        $success = false;
                    }
                    break;

                case 'lt':
                    if ($value >= $conditionValue) {
                        $success = false;
                    }
                    break;

                case 'in':
                    $allValues = strpos($this->getType(), '[]') !== false ? $value : [$value];

                    $conditionValue = is_array($conditionValue) ? $conditionValue : [$conditionValue];

                    foreach ($allValues as $currentValue) {
                        if (!in_array($currentValue, $conditionValue)) {
                            $success = false;
                        }
                    }

                    break;

                case 'isip':
                    $isValid = Ip::isValid();
                    $success = ($isValid && $conditionValue == 'true') || (!$isValid && $conditionValue == 'false');
                    break;

                default:
                    throw new \Exception(sprintf(
                        "Invalid condition parameters '%s: %s' for parameter '%s' with value '%s'",
                        $condition,
                        $sConditionValue,
                        $name,
                        $value
                    ));
                    break;
            }

            if (!$success) {
                $value = is_array($value) ? json_encode($value) : $value;
                throw new ResponseCode(sprintf(
                    "Invalid conditions for parameter '%s' with value '%s' for condition '%s : %s'",
                    $name,
                    $value,
                    $condition,
                    is_array($conditionValue) ? json_encode($conditionValue) : $conditionValue
                ), 422);
            }
        }

        return $value;
    }
}
