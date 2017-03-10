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
     * @return $this|self
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
     * @return $this|self
     */
    public function addTitleBadge(ViewController $element) : self
    {
        $this->titleBadge->add($element);

        return $this;
    }

    /**
     * @param string $subtitle
     *
     * @return $this|self
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
                $link = self::uri(
                    'swagger/doc/' . ($this->data['version'] ? 'version/' : '') . 'service',
                    [
                        'namespace' => $namespaceName,
                        'service' => $serviceName,
                        'version' => $version,
                    ]
                );

                $methodsList = [
                    'name' => $serviceName,
                    'link' => $link,
                    'active' => $namespaceName == $this->data['namespace'] &&
                        $serviceName == $this->data['service'],
                    'methods' => []
                ];

                if ($serviceName == $this->data['service']) {
                    foreach ($currentService->getMethods() as $methodName) {
                        $link = self::uri(
                            'swagger/doc/' . ($this->data['version'] ? 'version/' : '') . 'method',
                            [
                                'namespace' => $namespaceName,
                                'service' => $serviceName,
                                'method' => $methodName,
                                'version' => $version
                            ]
                        );

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

            $link = self::uri(
                'swagger/doc/' . ($this->data['version'] ? 'version/' : '') . 'namespace',
                [
                    'namespace' => $namespaceName,
                    'version' => $version,
                ]
            );

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
                $route = self::router()->current()->getName();
                if ($this->maxVersion($this->data['namespace']) == $version) {
                    $route = str_replace('version/', '', self::router()->current()->getName());
                }

                $this->data['versions'][$version] = (string) self::uri($route, [
                    'namespace' => $this->data['namespace'],
                    'service' => $this->data['service'],
                    'method' => $this->data['method'],
                    'version' => $this->maxVersion($this->data['namespace']) != $version ? $version : null
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
