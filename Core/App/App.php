<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Exception\InvalidArgumentException as TranslationInvalidArgumentException;

/**
 * Description of App
 *
 * @author Carlos García Gómez
 */
abstract class App
{
    /**
     * Gestor de acceso a cache.
     * @var Base\Cache
     */
    protected $cache;

    /**
     * Gestor de acceso a la base de datos.
     * @var Base\DataBase
     */
    protected $dataBase;

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string
     */
    protected $folder;

    /**
     * Motor de traducción.
     * @var Base\Translator
     */
    protected $i18n;

    /**
     * Gestor del log de la app.
     * @var Base\MiniLog
     */
    protected $miniLog;

    /**
     * Gestor de plugins.
     * @var Base\PluginManager
     */
    protected $pluginManager;

    /**
     * Permite acceder a los datos de la petición HTTP.
     * @var Request
     */
    protected $request;

    /**
     * Objeto respuesta HTTP.
     * @var Response
     */
    protected $response;

    /**
     * Inicializa la app.
     * @param string $folder Carpeta de trabajo de FacturaScripts
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TranslationInvalidArgumentException
     */
    public function __construct($folder = '')
    {
        $this->cache = new Base\Cache($folder);
        $this->dataBase = new Base\DataBase();
        $this->folder = $folder;
        $this->i18n = new Base\Translator($folder, FS_LANG);
        $this->miniLog = new Base\MiniLog();
        $this->pluginManager = new Base\PluginManager($folder);
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
    }

    /**
     * Conecta a la base de datos.
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

    abstract public function run();

    /**
     * Vuelca los datos en la salida estándar.
     */
    public function render()
    {
        $this->response->send();
    }

    /**
     * Devuelve TRUE si la IP del cliente ha sido baneada.
     * @return bool
     */
    protected function isIPBanned()
    {
        $ipFilter = new Base\IPFilter($this->folder);
        return $ipFilter->isBanned($this->request->getClientIp());
    }
}
