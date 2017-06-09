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

namespace Cawa\SwaggerServer\Reflection;

use Cawa\SwaggerServer\Reflection\Definitions\Auth;
use Cawa\SwaggerServer\Reflection\Definitions\Comment;
use Cawa\SwaggerServer\Reflection\Definitions\CommentLong;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\Reflection\Definitions\Example;
use Cawa\SwaggerServer\Reflection\Definitions\Header;
use Cawa\SwaggerServer\Reflection\Definitions\HttpMethod;
use Cawa\SwaggerServer\Reflection\Definitions\Param;
use Cawa\SwaggerServer\Reflection\Definitions\Response;
use Cawa\SwaggerServer\Reflection\Definitions\ReturnType;
use Cawa\SwaggerServer\Reflection\Definitions\Throws;

trait PhpDocParserTrait
{
    /**
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * @param string $type
     *
     * @return Definition
     */
    public function getDefinition(string $type)
    {
        if (isset($this->definitions[$type])) {
            return $this->definitions[$type];
        }

        return null;
    }

    /**
     * @return Definition[]
     */
    public function getDefinitions() : array
    {
        return $this->definitions;
    }

    /**
     * @param Definition $definition
     * @param string $paramType
     * @param bool $multiple
     */
    private function add(Definition $definition, string $paramType, bool $multiple = false)
    {
        if ($multiple) {
            $this->definitions[$paramType][] = $definition;
        } else {
            $this->definitions[$paramType] = $definition;
        }
    }

    /**
     * @param string $paramType
     *
     * @return Definition|Definition[]|null
     */
    public function getParam(string $paramType)
    {
        if (isset($this->definitions[$paramType])) {
            return $this->definitions[$paramType];
        }

        return null;
    }

    /**
     * @param string $phpDoc
     * @param string $class
     * @param string $name
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function parsePhpDoc(string $phpDoc, string $class, string $name = null)
    {
        $phpDoc = preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ ]{0,1}(.*)?#', '$1', $phpDoc);
        $phpDoc = ltrim($phpDoc, "\r\n");

        $split = preg_split("/(\n@)/isU", $phpDoc, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (!isset($split[0][0])) {
            throw new \Exception(sprintf("Empty PhpDoc for '%s::%s'", $class, $name));
        }

        if ($split[0][0] != '@') {
            $comments = explode("\n", array_shift($split));

            $this->add(new Comment(isset($comments[0]) ? array_shift($comments) : null), Definition::COMMENT);

            $commentLong = isset($comments[0]) ? trim(implode($comments, "\n"), "\n") : null;
            if ($commentLong) {
                $this->add(new CommentLong($commentLong), Definition::COMMENT_LONG);
            }
        } else {
            $split[0] = substr($split[0], 1);
        }

        foreach ($split as $content) {
            if ($content == '@') {
                continue;
            }

            // clean up variables
            $tempString = str_replace('  ', '-', $content);
            while ($content != $tempString) {
                $tempString = $content;
                $content = str_replace('  ', ' ', $content);
            }

            $explode = explode(' ', trim(str_replace("\t", '  ', $content)));

            $paramType = array_shift($explode);
            if ($paramType == '@') {
                continue;
            }

            switch ($paramType) {
                case Definition::PARAM:
                    $this->add(new Param($explode), $paramType, true);
                    break;

                case Definition::RETURNS:
                    $this->add(new ReturnType($explode), $paramType);
                    break;

                case Definition::THROWS:
                    $this->add(new Throws($explode), $paramType, true);
                    break;

                case Definition::RESPONSE:
                    $this->add(new Response($explode), $paramType, true);
                    break;

                case Definition::EXAMPLE:
                    $this->add(new Example($explode), $paramType, true);
                    break;

                case Definition::HEADER:
                    $this->add(new Header($explode), $paramType, true);
                    break;

                case Definition::AUTH:
                    $this->add(new Auth(implode(' ', $explode)), $paramType);
                    break;

                case Definition::HTTP_METHOD:
                    $this->add(new HttpMethod(implode(' ', $explode)), $paramType);
                    break;

                default:
                    throw new \InvalidArgumentException(
                        sprintf("Invalid param type '%s' on '%s::%s'", $paramType, $class, $name)
                    );
            }
        }

        if (is_null($this->getDefinition(Definition::RESPONSE)) &&
            !is_null($this->getDefinition(Definition::RETURNS))
        ) {
            $response = new Response();
            /* @noinspection PhpParamsInspection */
            $response->fromReturn($this->getDefinition(Definition::RETURNS));

            $this->add($response, Definition::RESPONSE, true);
        }
    }
}
