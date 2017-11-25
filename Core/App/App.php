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
 * App class is used for encapsulate common parts of code for the normal App execution.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class App
{

    /**
     * Stored defaut configuration with the default application settings.
     * @var AppSettings
     */
    protected $appSettings;

    /**
     * Cache access manager.
     *
     * @var Base\Cache
     */
    protected $cache;

    /**
     * Database access manager.
     *
     * @var Base\DataBase
     */
    protected $dataBase;

    /**
     * Translation engine.
     *
     * @var Base\Translator
     */
    protected $i18n;

    /**
     * IP filter.
     *
     * @var Base\IPFilter
     */
    protected $ipFilter;

    /**
     * App log manager.
     *
     * @var Base\MiniLog
     */
    protected $miniLog;

    /**
     * Plugin manager.
     *
     * @var Base\PluginManager
     */
    protected $pluginManager;

    /**
     * Gives us access to the HTTP request parameters.
     *
     * @var Request
     */
    protected $request;

    /**
     * HTTP response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Stored defaut configuration with the application settings.
     *
     * @var AppSettings
     */
    protected $settings;

    /**
     * Initializes the app.
     *
     * @param string $folder FacturaScripts working directory
     */
    public function __construct($folder = '')
    {
        /// Having the directory in a constas lets us access it more easily
        if (!defined('FS_FOLDER')) {
            define('FS_FOLDER', $folder);
        }

        $this->request = Request::createFromGlobals();

        if ($this->request->cookies->get('fsLang')) {
            $this->i18n = new Base\Translator($this->request->cookies->get('fsLang'));
        } else {
            $this->i18n = new Base\Translator();
        }

        $this->cache = new Base\Cache();
        $this->dataBase = new Base\DataBase();
        $this->ipFilter = new Base\IPFilter();
        $this->miniLog = new Base\MiniLog();
        $this->pluginManager = new Base\PluginManager();
        $this->response = new Response();
        $this->settings = new AppSettings();
    }

    /**
     * Connects to the database and loads the configuration.
     *
     * @return bool
     */
    public function connect()
    {
        if ($this->dataBase->connect()) {
            $this->settings->load();
            return true;
        }

        return false;
    }

    /**
     * Disconnects from the database.
     */
    public function close()
    {
        $this->dataBase->close();
    }

    /**
     * Selects and runs the corresponding controller.
     *
     * @return bool
     */
    abstract public function run();

    /**
     * Returns the data into the standard output.
     */
    public function render()
    {
        $this->response->send();
    }

    /**
     * Returns true if the client IP has been banned.
     *
     * @return bool
     */
    protected function isIPBanned()
    {
        return $this->ipFilter->isBanned($this->request->getClientIp());
    }
}
