<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Debug\DebugBar;

/**
 * Description of AppDebugController
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class AppDebugController extends AppController
{

    /**
     * 
     * @param string $uri
     * @param string $pageName
     */
    public function __construct(string $uri = '/', string $pageName = '')
    {
        DebugBar::start();
        DebugBar::start('__construct');
        parent::__construct($uri, $pageName);
        DebugBar::end('__construct');
    }

    /**
     * 
     * @return bool
     */
    public function connect(): bool
    {
        DebugBar::start('connect');
        $return = parent::connect();
        DebugBar::end('connect');
        return $return;
    }

    /**
     * 
     * @return DebugBar
     */
    public function debugBar()
    {
        return new DebugBar();
    }

    /**
     * 
     * @param string $pageName
     */
    protected function loadController(string $pageName)
    {
        DebugBar::start($pageName);
        parent::loadController($pageName);
    }

    /**
     * 
     * @param string $template
     * @param string $controllerName
     */
    protected function renderHtml(string $template, string $controllerName = '')
    {
        $parts = \explode('\\', $controllerName);
        DebugBar::end(\end($parts));
        DebugBar::start($template);
        parent::renderHtml($template, $controllerName);
    }
}
