<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Model;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clase de la que deben heredar todos los controladores de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Controller
{

    /**
     * Contiene la lista de archivos extra a cargar: javascript, css, etc.
     * @var array 
     */
    public $assets;

    /**
     * Gestor de acceso a cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Nombre de la clase del controlador (aunque se herede de esta clase, el nombre
     * de la clase final lo tendremos aquí).
     *
     * @var string __CLASS__
     */
    private $className;

    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Gestor de eventos.
     *
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Herramientas para trabajar con divisas.
     * @var DivisaTools 
     */
    public $divisaTools;

    /**
     * Empresa seleccionada.
     *
     * @var Model\Empresa|false
     */
    public $empresa;

    /**
     * Motor de traducción.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Gestor de log de la app.
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Herramientas para trabajar con números.
     * @var NumberTools 
     */
    public $numberTools;

    /**
     * Request sobre la que podemos hacer consultas.
     *
     * @var Request
     */
    public $request;

    /**
     * Objeto respuesta HTTP.
     *
     * @var Response
     */
    protected $response;

    /**
     * Nombre del archivo html para el motor de plantillas.
     *
     * @var string|false nombre_archivo.html
     */
    private $template;

    /**
     * Título de la página.
     *
     * @var string título de la página.
     */
    public $title;

    /**
     * Usuario que ha iniciado sesión.
     *
     * @var Model\User|null
     */
    public $user;

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Cache      $cache
     * @param Translator $i18n
     * @param MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        $this->assets = AssetManager::getAssetsForPage($className);
        $this->cache = $cache;
        $this->className = $className;
        $this->dataBase = new DataBase();
        $this->dispatcher = new EventDispatcher();
        $this->divisaTools = new DivisaTools();
        $this->i18n = $i18n;
        $this->miniLog = $miniLog;
        $this->numberTools = new NumberTools();
        $this->request = Request::createFromGlobals();
        $this->template = $this->className . '.html';

        $this->title = $this->className;
        $pageData = $this->getPageData();
        if (!empty($pageData)) {
            $this->title = $pageData['title'];
        }
    }

    /**
     * Devuelve el nombre del controlador
     *
     * @return string
     */
    protected function getClassName()
    {
        return $this->className;
    }

    /**
     * Devuelve el template HTML a utilizar para este controlador.
     *
     * @return string|false
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Establece el template HTML a utilizar para este controlador.
     *
     * @param string|false $template
     *
     * @return bool
     */
    public function setTemplate($template)
    {
        if ($template === false) {
            $this->template = false;
            return true;
        }

        $this->template = $template . '.html';
        return true;
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        return [
            'name' => $this->className,
            'title' => $this->className,
            'icon' => 'fa-circle-o',
            'menu' => 'new',
            'submenu' => null,
            'showonmenu' => true,
            'orden' => 100,
        ];
    }

    /**
     * Devuelve la url del controlador actual.
     *
     * @return string
     */
    public function url()
    {
        return 'index.php?page=' . $this->className;
    }

    /**
     * Ejecuta la lógica pública del controlador.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        $this->response = $response;
        $this->template = 'Login/Login.html';
        $this->dispatcher->dispatch('pre-publicCore');
    }

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param Response        $response
     * @param Model\User|null $user
     */
    public function privateCore(&$response, $user)
    {
        $this->response = $response;
        $this->user = $user;

        /// seleccionamos la empresa predeterminada del usuario
        $empresaModel = new Model\Empresa();
        $this->empresa = $empresaModel->get($user->idempresa);

        /// ¿Ha marcado el usuario la página como página de inicio?
        $defaultPage = $this->request->query->get('defaultPage', '');
        if ($defaultPage === 'TRUE') {
            $this->user->homepage = $this->className;
            $this->response->headers->setCookie(new Cookie('fsHomepage', $this->user->homepage, time() + FS_COOKIES_EXPIRE));
            $this->user->save();
        } elseif ($defaultPage === 'FALSE') {
            $this->user->homepage = null;
            $this->response->headers->setCookie(new Cookie('fsHomepage', $this->user->homepage, time() - FS_COOKIES_EXPIRE));
            $this->user->save();
        }

        $this->dispatcher->dispatch('pre-privateCore');
    }
}
