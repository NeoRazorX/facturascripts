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

use FacturaScripts\Core\Template\ErrorController;
use FacturaScripts\Core\Tools;

class DatabaseError extends ErrorController
{
    public function run(): void
    {
        ob_clean();
        http_response_code(500);

        $title = Tools::lang()->trans('database-error');
        $body = '<h1>' . $title . '</h1>'
            . '<p class="mb-0">' . $this->exception->getMessage() . '</p>';

        echo $this->htmlCard($title, $body, 'bg-danger');
    }
}
