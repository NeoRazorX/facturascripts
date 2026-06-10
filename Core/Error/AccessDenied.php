<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\MenuManager;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ErrorController;

class AccessDenied extends ErrorController
{
    public function run(): void
    {
        // creamos la respuesta
        $this->response()
            ->setHttpCode(Response::HTTP_FORBIDDEN)
            ->view('Error/AccessDenied.html.twig', [
                'controllerName' => 'AccessDenied',
                'debugBarRender' => false,
                'fsc' => $this,
                'menuManager' => MenuManager::init(),
                'template' => 'Error/AccessDenied.html.twig'
            ]);
    }
}
