<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Dinamic\Model\User as DinUser;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Empresa;

class Controller implements ControllerInterface
{
    /** @var string */
    private $className;

    /** @var DataBase */
    protected $dataBase;

    /** @var Empresa */
    public $empresa;

    /** @var Request */
    public $request;

    /** @var Response */
    private $response;

    /** @var string */
    private $template;

    /** @var string */
    public $title;

    /** @var string */
    public $url;

    /** @var ?DinUser */
    public $user;

    public function __construct(string $className, string $url = '')
    {
        Session::set('controllerName', $className);
        Session::set('pageName', $className);
        Session::set('uri', $url);

        $this->className = $className;
        $this->dataBase = new DataBase();
        $this->empresa = new Empresa();
        $this->template = $className . '.html.twig';
        $this->url = $url;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $className : Tools::lang()->trans($pageData['title']);
    }

    /** @param mixed $extension */
    public static function addExtension($extension): void
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPageData(): array
    {
        return [
            'name' => $this->className,
            'title' => $this->className,
            'icon' => 'fa-solid fa-circle',
            'menu' => 'new',
            'submenu' => null,
            'showonmenu' => true,
            'ordernum' => 100
        ];
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function pipe(string $name, ...$arguments)
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function pipeFalse(string $name, ...$arguments): bool
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
        return true;
    }

    public function run(): void
    {
        AssetManager::clear();
        AssetManager::setAssetsForPage($this->className);

        $this->checkPhpVersion(7.4);
    }

    public function url(): string
    {
        return $this->className;
    }

    protected function checkPhpVersion(float $min): void
    {
        $current = (float)substr(phpversion(), 0, 3);
        if ($current < $min) {
            Tools::log()->warning('php-support-end', ['%current%' => $current, '%min%' => $min]);
        }
    }

    protected function response(): Response
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }

    public function setTemplate($template): void
    {
        $this->template = empty($template) ? '' : $template . '.html.twig';
    }

    protected function validateFormToken(): bool
    {
        return true;
    }
}
