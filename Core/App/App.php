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

namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of App
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class App
{
    /**
     * Gestor de acceso a cache.
     *
     * @var Base\Cache
     */
    protected $cache;

    /**
     * Gestor de acceso a la base de datos.
     *
     * @var Base\DataBase
     */
    protected $dataBase;

    /**
     * Motor de traducción.
     *
     * @var Base\Translator
     */
    protected $i18n;

    /**
     * Filtro de IPs.
     *
     * @var Base\IPFilter
     */
    protected $ipFilter;

    /**
     * Gestor del log de la app.
     *
     * @var Base\MiniLog
     */
    protected $miniLog;

    /**
     * Gestor de plugins.
     *
     * @var Base\PluginManager
     */
    protected $pluginManager;

    /**
     * Permite acceder a los datos de la petición HTTP.
     *
     * @var Request
     */
    protected $request;

    /**
     * Objeto respuesta HTTP.
     *
     * @var Response
     */
    protected $response;

    /**
     * Inicializa la app.
     *
     * @param string $folder Carpeta de trabajo de FacturaScripts
     */
    public function __construct($folder = '')
    {
        /// al tener la carpeta en una constante la podemos usar más fácilmente
        if (!defined('FS_FOLDER')) {
            define('FS_FOLDER', $folder);
        }

        $this->cache = new Base\Cache();
        $this->dataBase = new Base\DataBase();
        $this->i18n = new Base\Translator();
        $this->ipFilter = new Base\IPFilter();
        $this->miniLog = new Base\MiniLog();
        $this->pluginManager = new Base\PluginManager();
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
    }

    /**
     * Conecta a la base de datos.
     *
     * @return bool
     */
    public function connect()
    {
        return $this->dataBase->connect();
    }

    /**
     * Cierra la conexión a la base de datos.
     */
    public function close()
    {
        $this->dataBase->close();
    }

    /**
     * Selecciona y ejecuta el controlador pertinente.
     *
     * @return bool
     */
    abstract public function run();

    /**
     * Vuelca los datos en la salida estándar.
     */
    public function render()
    {
        $this->response->send();
    }

    /**
     * Devuelve True si la IP del cliente ha sido baneada.
     *
     * @return bool
     */
    protected function isIPBanned()
    {
        return $this->ipFilter->isBanned($this->request->getClientIp());
    }
}
