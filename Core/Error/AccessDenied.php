<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Error;

use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ErrorController;

class AccessDenied extends ErrorController
{
    public function run(): void
    {
        // creamos la respuesta
        $response = new Response();
        $response->setHttpCode(Response::HTTP_FORBIDDEN);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        // carga el menú
        $menu = new MenuManager();
        $menu->setUser(Session::user());
        $menu->selectPage([]);

        // renderizamos la plantilla
        $response->setContent(Html::render('Error/AccessDenied.html.twig', [
            'controllerName' => 'AccessDenied',
            'debugBarRender' => false,
            'fsc' => $this,
            'menuManager' => $menu,
            'template' => 'Error/AccessDenied.html.twig'
        ]));
        $response->send();
    }
}
