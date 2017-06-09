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

class Property extends Comment implements Definition
{
    /**
     * @var array
     */
    private $type;

    /**
     * @return array
     */
    public function getType() : array
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
     * @var bool
     */
    private $nullable = false;

    /**
     * @return array
     */
    public function isNullable() : bool
    {
        return $this->nullable;
    }

    /**
     * @param string $class
     * @param string $phpDoc
     */
    public function __construct(string $class, string $phpDoc)
    {
        $phpDoc = trim(preg_replace('`[ \t]*(?:\/\*\*|\*\/|\*)?[ \t]*(.*)?`', '$1', $phpDoc));
        $explode = explode(' ', str_replace(["\n", "\r"], ["\n ", ''], $phpDoc));

        $type = array_pop($explode);
        $subType = null;

        if (stripos($type, 'map[') === 0) {
            $subType = substr($type, 4, -1);
            $type = array_pop($explode);
        }

        if ($type == '$this') {
            $type = $class;
        } elseif ($type == '$this') {
            $type = $class . '[]';
        }

        $this->type = explode('|', $type);
        $this->subType = $subType;

        // remove @var
        array_pop($explode);

        if (array_search("@nullable\n", $explode) !== false) {
            $this->nullable = true;
            unset($explode[array_search("@nullable\n", $explode)]);
        }

        $comment = trim(implode(' ', $explode));

        parent::__construct($comment);
    }
}
