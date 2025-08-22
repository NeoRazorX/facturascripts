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

        $title = '⚠️ ' . Tools::trans('database-error');
        $content = '<div class="card shadow mb-4">'
            . '<div class="card-body">'
            . '<h1 class="h3">' . $title . '</h1>'
            . '<p>' . $this->exception->getMessage() . '</p>'
            . '<p>' . Tools::trans('database-error-pc') . '</p>'
            . '<p class="mb-0">' . Tools::trans('database-error-server') . '</p>'
            . '</div>'
            . '<div class="card-footer">'
            . '<a href="https://facturascripts.com/publicaciones/error-al-conectar-a-la-base-de-datos" class="btn btn-secondary" target="_blank" rel="nofollow">'
            . Tools::trans('read-more')
            . '</a>'
            . '</div>'
            . '</div>';

        echo $this->html(
            $title,
            $this->htmlContainer($content),
        );
    }
}
