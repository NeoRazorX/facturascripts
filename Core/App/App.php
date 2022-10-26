<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\TelemetryManager;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Session;
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
     * Database access manager.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Plugin manager.
     *
     * @var PluginManager
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
     * Requested Uri
     *
     * @var string
     */
    protected $uri;

    abstract protected function die(int $status, string $message = '');

    /**
     * Initializes the app.
     *
     * @param string $uri
     */
    public function __construct(string $uri = '/')
    {
        $this->request = Request::createFromGlobals();
        if ($this->request->cookies->get('fsLang')) {
            ToolBox::i18n()->setDefaultLang($this->request->cookies->get('fsLang'));
        }

        $this->dataBase = new DataBase();
        $this->pluginManager = new PluginManager();
        $this->response = new Response();
        $this->uri = $uri;

        // timezone
        date_default_timezone_set(FS_TIMEZONE);

        // add security headers
        $this->response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $this->response->headers->set('X-XSS-Protection', '1; mode=block');
        $this->response->headers->set('X-Content-Type-Options', 'nosniff');
        $this->response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        ToolBox::log()->debug('URI: ' . $this->uri);
        ToolBox::log()::setContext('uri', $this->uri);
    }

    /**
     * Connects to the database and loads the configuration.
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->dataBase->connect()) {
            ToolBox::appSettings()->load();
            $this->loadPlugins();
            return true;
        }

        return false;
    }

    /**
     * Save log and disconnects from the database.
     */
    public function close()
    {
        // send telemetry (if configured)
        $telemetry = new TelemetryManager();
        $telemetry->update();

        // save log
        MiniLog::save();

        $this->dataBase->close();
    }

    /**
     * Returns the data into the standard output.
     */
    public function render()
    {
        $this->response->send();
    }

    /**
     * Runs the application core.
     *
     * @return bool
     */
    public function run(): bool
    {
        if (false === $this->dataBase->connected()) {
            ToolBox::i18nLog()->critical('cant-connect-database');
            $this->die(Response::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        } elseif ($this->isIPBanned()) {
            ToolBox::i18nLog()->critical('ip-banned');
            $this->die(Response::HTTP_TOO_MANY_REQUESTS);
            return false;
        }

        return true;
    }

    /**
     * Returns param number $num in uri.
     *
     * @param string $num
     *
     * @return string
     */
    protected function getUriParam(string $num): string
    {
        $params = explode('/', substr($this->uri, 1));
        return $params[$num] ?? '';
    }

    /**
     * Add or increase the attempt counter of the current client IP address.
     */
    protected function ipWarning()
    {
        $ipFilter = ToolBox::ipFilter();
        $ipFilter->setAttempt(Session::getClientIp());
    }

    /**
     * Returns true if the client IP has been banned.
     *
     * @return bool
     */
    protected function isIPBanned(): bool
    {
        $ipFilter = ToolBox::ipFilter();
        return $ipFilter->isBanned(Session::getClientIp());
    }

    /**
     * Initialize plugins.
     */
    private function loadPlugins()
    {
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $initClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Init';
            if (class_exists($initClass)) {
                $initObject = new $initClass();
                $initObject->init();
            }
        }
    }
}
