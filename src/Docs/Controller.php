<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Cawa\SwaggerServer\Docs;

use Cawa\Controller\AbstractController;
use Cawa\Intl\TranslatorFactory;
use Cawa\Renderer\HtmlElement;
use Cawa\SwaggerServer\Reflection\Definitions\Definition;
use Cawa\SwaggerServer\ToolsTrait;

class Controller extends AbstractController
{
    use TranslatorFactory;
    use ToolsTrait;

    /**
     * @var MasterPage
     */
    private $masterpage;

    /**
     * @param string|null $namespace
     * @param string|null $service
     * @param string|null $method
     * @param int|null $version
     */
    public function init(string $namespace = null, string $service = null, string $method = null, int $version = null)
    {
        $this->translator()->addFile(__DIR__ . '/../lang/global', 'swaggerserver');

        $this->masterpage = new MasterPage($namespace, $service, $method, $version);
        if (!is_numeric($version) && $version) {
            $version = (int) str_replace('/v', '', $version);
        }

        if ($namespace) {
            $route = $method ? 'Method' : ($service ? 'Service' : 'Namespace');
            $swagger = new HtmlElement('<a>', '{···}');
            $swagger->addAttribute('href', $this->route('swagger.swagger' . $route, [
                'namespace' => $namespace,
                'version' => $version ? $version : $this->maxVersion($namespace),
                'service' => $service,
                'method' => $method,
            ]))->addClass(['btn', 'btn-success']);
            $this->masterpage->addTitleBadge($swagger);
        }
    }

    /**
     * @param string|null $namespace
     * @param string|null $service
     * @param string|null $method
     * @param int|null $version
     *
     * @return string
     */
    public function get(string $namespace = null, string $service = null, string $method = null, int $version = null)
    {
        return $this->masterpage->render();
    }

    /**+
     * @param string $namespace
     * @param string $service
     * @param string $method
     * @param int|null $version
     *
     * @return string
     */
    public function method(string $namespace, string $service, string $method, int $version = null)
    {
        $serviceObject = $this->listServices($namespace, $version, $service)[0];

        $authName = $serviceObject->getReflectionMethod($method)->getDefinition(Definition::AUTH)->getAuth();
        $auth = new HtmlElement('<span>', $this->translator()->trans('swaggerserver.auth', [$authName]));
        $auth->addClass(['btn', 'btn-warning']);
        $this->masterpage->addTitleBadge($auth);

        $this->masterpage->addMain(new Method\Parameters($serviceObject, $method, $version));
        $this->masterpage->addMain(new Method\Responses($serviceObject, $method, $version));
        $this->masterpage->addMain(new Method\Models($serviceObject, $method, $version));

        $comment = $serviceObject->getReflectionMethod($method)->getDefinition(Definition::COMMENT)->getComment();
        if ($serviceObject->getReflectionMethod($method)->getDefinition(Definition::COMMENT_LONG)) {
            $comment .= '<br /><br />' .
                $serviceObject->getReflectionMethod($method)->getDefinition(Definition::COMMENT_LONG)->getComment();
        }
        $this->masterpage->setSubtitle($comment);

        return $this->masterpage->render();
    }
}
