<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Controlador que despliega los archivos dinámicos (carpeta Dinamic) regenerando los plugins.
 */
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
    }

    protected function deployAction(): void
    {
        // si ya existe la carpeta Dinamic, no hacemos deploy
        if (is_dir(Tools::folder('Dinamic'))) {
            echo $this->render('Deploy not needed. Dinamic folder already exists. Delete it if you want to deploy again.', 'warning');
            return;
        }

        Plugins::deploy();

        echo $this->render('Deploy finished.');
    }

    protected function disablePluginsAction(): void
    {
        // comprobamos que no se ha desactivado
        if (Tools::config('disable_deploy_actions', false)) {
            echo $this->render('Deploy actions already disabled.', 'warning');
            return;
        }

        // comprobamos el token
        if (false === CrashReport::validateToken($_GET['token'] ?? '')) {
            echo $this->render('Invalid token.', 'danger');
            return;
        }

        // desactivamos todos los plugins
        foreach (Plugins::enabled() as $name) {
            Plugins::disable($name, false);
        }

        echo $this->render('Plugins disabled.');
    }

    protected function rebuildAction(): void
    {
        // comprobamos que no se ha desactivado
        if (Tools::config('disable_deploy_actions', false)) {
            echo $this->render('Deploy actions already disabled.', 'warning');
            return;
        }

        // comprobamos el token
        if (false === CrashReport::validateToken($_GET['token'] ?? '')) {
            echo $this->render('Invalid token.', 'danger');
            return;
        }

        Plugins::deploy();

        echo $this->render('Rebuild finished.');
    }

    private function render($message, $type = 'success'): string
    {
        return '
        <!doctype html>
        <html lang="es">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>' . $message . '</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
          </head>
          <body class="min-vh-100 d-flex align-items-center justify-content-center bg-secondary-subtle">
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                  <div class="card text-center text-bg-' . $type . '">
                    <div class="card-body">
                      <p class="card-text">' . $message . '</p>
                      <a href="' . Tools::config('route') . '/" class="btn btn-secondary">Reload</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </body>
        </html>
        ';
    }
}
