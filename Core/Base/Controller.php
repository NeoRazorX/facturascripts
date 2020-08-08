<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class from which all FacturaScripts controllers must inherit.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Controller
{

    /**
     * Name of the class of the controller (although its in inheritance from this class,
     * the name of the final class we will have here)
     *
     * @var string __CLASS__
     */
    private $className;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Selected company.
     *
     * @var Empresa
     */
    public $empresa;

    /**
     *
     * @var MultiRequestProtection
     */
    public $multiRequestProtection;

    /**
     * User permissions on this controller.
     *
     * @var ControllerPermissions
     */
    public $permissions;

    /**
     * Request on which we can get data.
     *
     * @var Request
     */
    public $request;

    /**
     * HTTP Response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Name of the file for the template.
     *
     * @var string|false nombre_archivo.html.twig
     */
    private $template;

    /**
     * Title of the page.
     *
     * @var string título de la página.
     */
    public $title;

    /**
     * Given uri, default is empty.
     *
     * @var string
     */
    public $uri;

    /**
     * User logged in.
     *
     * @var User|false
     */
    public $user = false;

    /**
     * Initialize all objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        $this->className = $className;
        $this->dataBase = new DataBase();
        $this->empresa = new Empresa();
        $this->multiRequestProtection = new MultiRequestProtection();
        $this->request = Request::createFromGlobals();
        $this->template = $this->className . '.html.twig';
        $this->uri = $uri;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $this->className : $this->toolBox()->i18n()->trans($pageData['title']);

        AssetManager::clear();
        AssetManager::setAssetsForPage($className);

        $this->checkPHPversion(7.1);
    }

    /**
     * 
     * @param mixed $extension
     */
    public static function addExtension($extension)
    {
        static::toolBox()->i18nLog()->error('no-extension-support', ['%className%' => static::class]);
    }

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        return [
            'name' => $this->className,
            'title' => $this->className,
            'icon' => 'fas fa-circle',
            'menu' => 'new',
            'submenu' => null,
            'showonmenu' => true,
            'ordernum' => 100
        ];
    }

    /**
     * Return the template to use for this controller.
     *
     * @return string|false
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * 
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function pipe($name, ...$arguments)
    {
        $this->toolBox()->i18nLog()->error('no-extension-support', ['%className%' => static::class]);
        return null;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        $this->permissions = $permissions;
        $this->response = &$response;
        $this->user = $user;

        /// Select the default company for the user
        $this->empresa->loadFromCode($this->user->idempresa);

        /// This user have default page setted?
        $defaultPage = $this->request->query->get('defaultPage', '');
        if ($defaultPage === 'TRUE') {
            $this->user->homepage = $this->className;
            $this->response->headers->setCookie(new Cookie('fsHomepage', $this->user->homepage, time() + \FS_COOKIES_EXPIRE));
            $this->user->save();
        } elseif ($defaultPage === 'FALSE') {
            $this->user->homepage = null;
            $this->response->headers->setCookie(new Cookie('fsHomepage', $this->user->homepage, time() - \FS_COOKIES_EXPIRE));
            $this->user->save();
        }
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        $this->permissions = new ControllerPermissions();
        $this->response = &$response;
        $this->template = 'Login/Login.html.twig';

        $idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $this->empresa->loadFromCode($idempresa);
    }

    /**
     * Redirect to an url or controller.
     * 
     * @param string $url
     * @param int    $delay
     */
    public function redirect($url, $delay = 0)
    {
        $this->response->headers->set('Refresh', $delay . '; ' . $url);
        if ($delay === 0) {
            $this->setTemplate(false);
        }
    }

    /**
     * Set the template to use for this controller.
     *
     * @param string|false $template
     *
     * @return bool
     */
    public function setTemplate($template)
    {
        $this->template = ($template === false) ? false : $template . '.html.twig';
        return true;
    }

    /**
     * 
     * @return ToolBox
     */
    public static function toolBox()
    {
        return new ToolBox();
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url()
    {
        return $this->className;
    }

    /**
     * 
     * @param float $min
     */
    private function checkPHPversion(float $min)
    {
        $current = (float) \substr(\phpversion(), 0, 3);
        if ($current < $min) {
            $this->toolBox()->i18nLog()->warning('php-support-end', ['%current%' => $current, '%min%' => $min]);
        }
    }

    /**
     * Return the name of the controller.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->className;
    }
}
