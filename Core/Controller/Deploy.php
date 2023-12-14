<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;

class Deploy implements ControllerInterface
{
    public function __construct(string $className, string $url = '')
    {
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        switch ($_GET['action'] ?? '') {
            case 'disable-plugins':
                $this->disablePluginsAction();
                break;

            case 'rebuild':
                $this->rebuildAction();
                break;

            default:
                $this->deployAction();
                break;
        }

        echo '<a href="' . Tools::config('route') . '/">Reload</a>';
    }

    protected function deployAction(): void
    {
        // si ya existe la carpeta Dinamic, no hacemos deploy
        if (is_dir(Tools::folder('Dinamic'))) {
            echo '<p>Deploy not needed. Dinamic folder already exists. Delete it if you want to deploy again.</p>';
            return;
        }

        Plugins::deploy();

        echo '<p>Deploy finished.</p>';
    }

    protected function disablePluginsAction(): void
    {
        // comprobamos que no se ha desactivado
        if (Tools::config('disable_deploy_actions', false)) {
            echo '<p>Deploy actions already disabled.</p>';
            return;
        }

        // comprobamos el token
        if (false === CrashReport::validateToken($_GET['token'] ?? '')) {
            echo '<p>Invalid token.</p>';
            return;
        }

        // desactivamos todos los plugins
        foreach (Plugins::enabled() as $name) {
            Plugins::disable($name);
        }

        echo '<p>Plugins disabled.</p>';
    }

    protected function rebuildAction(): void
    {
        // comprobamos que no se ha desactivado
        if (Tools::config('disable_deploy_actions', false)) {
            echo '<p>Deploy actions already disabled.</p>';
            return;
        }

        // comprobamos el token
        if (false === CrashReport::validateToken($_GET['token'] ?? '')) {
            echo '<p>Invalid token.</p>';
            return;
        }

        Plugins::deploy();

        echo '<p>Rebuild finished.</p>';
    }
}
