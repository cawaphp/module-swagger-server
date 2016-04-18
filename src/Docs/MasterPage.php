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

use Cawa\Controller\ViewController;
use Cawa\Controller\ViewDataTrait;
use Cawa\Intl\TranslatorFactory;
use Cawa\Renderer\Container;
use Cawa\Renderer\HtmlPage;
use Cawa\Renderer\PhtmlTrait;
use Cawa\Router\RouterFactory;
use Cawa\SwaggerServer\ToolsTrait;

class MasterPage extends HtmlPage
{
    use ViewDataTrait;
    use RouterFactory;
    use TranslatorFactory;
    use ToolsTrait;
    use PhtmlTrait {
        PhtmlTrait::render as private phtmlRender;
    }

    /**
     * MasterPage constructor.
     *
     * @param string|null $namespace
     * @param string|null $service
     * @param string|null $method
     * @param int|null $version
     */
    public function __construct(
        string $namespace = null,
        string $service = null,
        string $method = null,
        int $version = null
    ) {
        parent::__construct();

        $this->data['apiName'] = 'Swagger Api';
        $this->data['breadcrumb'] = [];
        $this->data['namespace'] = $namespace;
        $this->data['service'] = $service;
        $this->data['method'] = $method;

        if ($namespace) {
            $this->data['breadcrumb'][] = $namespace;
        }
        if ($service) {
            $this->data['breadcrumb'][] = $service;
        }
        if ($method) {
            $this->data['breadcrumb'][] = $method;
        }

        if ($this->data['breadcrumb']) {
            $title = implode(' > ', array_reverse($this->data['breadcrumb']));
            $title .= ' : ' . $this->data['apiName'];
            $this->setHeadTitle($title);
        } else {
            $this->setHeadTitle($this->data['apiName']);
        }

        $this->data['version'] = $version;

        $this->addCss('//fonts.googleapis.com/css?family=Roboto+Condensed:300,400');
        $this->addCss('//fonts.googleapis.com/css?family=Lato:300,400,700,900');
        $this->addCss('modules/swagger-server/swaggerdocs.css');
        $this->addJs('modules/swagger-server/swaggerdocs.js');
        $this->getBody()->addClass('flat-blue');

        $this->main = new Container();
        $this->titleBadge = new Container();
    }

    /**
     * @var Container
     */
    private $main;

    /**
     * @param ViewController $element
     *
     * @return $this
     */
    public function addMain(ViewController $element) : self
    {
        $this->main->add($element);

        return $this;
    }

    /**
     * @var Container
     */
    private $titleBadge;

    /**
     * @param ViewController $element
     *
     * @return $this
     */
    public function addTitleBadge(ViewController $element) : self
    {
        $this->titleBadge->add($element);

        return $this;
    }

    /**
     * @param string $subtitle
     *
     * @return $this
     */
    public function setSubtitle(string $subtitle) : self
    {
        $this->data['subtitle'] = $subtitle;

        return $this;
    }

    /**
     * @return string
     */
    public function render() : string
    {
        foreach ($this->module()->namespaces as $namespace) {
            $namespaceName = $namespace->getName();
            $version = $this->data['version'] ? $this->data['version'] : max($namespace->getVersions());

            $servicesList = [];
            foreach ($this->listServices($namespaceName, $version) as $serviceName => $currentService) {
                $link = $this->router()->getUri('swagger.docService' . ($this->data['version'] ? 'Version' : ''), [
                    'namespace' => $namespaceName,
                    'service' => $serviceName,
                    'version' => $version,
                ]);

                $methodsList = [
                    'name' => $serviceName,
                    'link' => $link,
                    'active' => $namespaceName == $this->data['namespace'] &&
                        $serviceName == $this->data['service'],
                    'methods' => []
                ];

                if ($serviceName == $this->data['service']) {
                    foreach ($currentService->getMethods() as $methodName) {
                        $link = $this->router()->getUri('swagger.docMethod' . ($this->data['version'] ? 'Version' : ''), [
                            'namespace' => $namespaceName,
                            'service' => $serviceName,
                            'method' => $methodName,
                            'version' => $version
                        ]);

                        $methodsList['methods'][] = [
                            'link' => $link,
                            'active' => $namespaceName == $this->data['namespace'] &&
                                $serviceName == $this->data['service'] &&
                                $methodName == $this->data['method'] ,
                            'name' => $methodName
                        ];
                    }
                }

                $servicesList[$serviceName] = $methodsList;
            }

            $link = $this->router()->getUri('swagger.docNamespace' . ($this->data['version'] ? 'Version' : ''), [
                'namespace' => $namespaceName,
                'version' => $version,
            ]);

            $this->data['namespaces'][] = [
                'name' => $namespaceName,
                'link' => $link,
                'active' => $namespaceName == $this->data['namespace'],
                'versions' => $namespace->getVersions(),
                'services' => $servicesList,
            ];
        }

        if ($this->data['namespace']) {
            foreach ($this->module()->namespaces[$this->data['namespace']]->getVersions() as $version) {
                $route = str_replace('VersionVersion', 'Version', self::router()->current() . 'Version');
                $this->data['versions'][$version] = $this->router()->getUri($route, [
                    'namespace' => $this->data['namespace'],
                    'service' => $this->data['service'],
                    'method' => $this->data['method'],
                    'version' => $this->maxVersion($this->data['namespace']) != $version ? $version : ''
                ]);
            }
            $this->data['maxVersion'] = $this->maxVersion($this->data['namespace']);
        }

        $this->data['main'] = $this->main->render();
        $this->data['titleBadge'] = $this->titleBadge->render();

        $this->getBody()->setContent($this->phtmlRender());

        return parent::render();
    }
}
