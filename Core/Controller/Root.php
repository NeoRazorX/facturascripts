<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Template\Controller;

class Root extends Controller
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = Empresas::default()->nombrecorto ?? 'FacturaScripts';
        $data['icon'] = 'fa-solid fa-home';
        $data['showonmenu'] = false;
        return $data;
    }

    public function run(): void
    {
        parent::run();

        // si el usuario tiene homepage y es distinta de Root, redirigimos
        if (!empty($this->user->homepage) && $this->user->homepage !== 'Root') {
            $this->response()->redirect($this->user->homepage)->send();
            return;
        }

        // si no tiene homepage, redireccionamos al Dashboard
        $this->response()->redirect('Dashboard')->send();
    }
}
