<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Debug\DumbBar;

/**
 * Description of AppDebugController
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class AppDebugController extends AppController
{
    public function __construct(string $uri = '/', string $pageName = '')
    {
        DebugBar::start();
        DebugBar::start('__construct');
        parent::__construct($uri, $pageName);
        DebugBar::end('__construct');
    }

    public function connect(): bool
    {
        DebugBar::start('connect');
        $return = parent::connect();
        DebugBar::end('connect');
        return $return;
    }

    public function debugBar(): DumbBar
    {
        return new DebugBar();
    }

    protected function loadController(string $pageName): void
    {
        DebugBar::start($pageName);
        parent::loadController($pageName);
    }

    protected function renderHtml(string $template, string $controllerName = ''): void
    {
        $parts = explode('\\', $controllerName);
        DebugBar::end(end($parts));
        DebugBar::start($template);
        parent::renderHtml($template, $controllerName);
    }
}
