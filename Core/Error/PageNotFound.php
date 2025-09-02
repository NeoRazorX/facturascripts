<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

class PageNotFound extends ErrorController
{
    public function run(): void
    {
        http_response_code(404);

        $title = '🔍 ' . Tools::trans('page-not-found');
        $card = '<div class="card shadow mt-5 mb-5">'
            . '<div class="card-body text-center">'
            . '<div class="display-1 text-info">404</div>'
            . '<h1 class="card-title">' . $title . '</h1>'
            . '<p class="mb-0">' . Tools::trans('page-not-found-p') . '</p>'
            . '</div>'
            . '<div class="card-footer">'
            . '<div class="row">'
            . '<div class="col">'
            . '<a href="' . Tools::config('route') . '/" class="btn btn-secondary">'
            . Tools::trans('homepage') . '</a>'
            . '</div>';

        if ($this->canShowDeployButtons()) {
            $card .= '<div class="col-auto">'
                . '<a href="' . Tools::config('route') . '/AdminPlugins" class="btn btn-warning">' . Tools::trans('plugins') . '</a>'
                . '</div>';
        }

        $card .= '</div>'
            . '</div>'
            . '</div>';

        echo $this->html(
            $title,
            $this->htmlContainer($card),
        );
    }
}
