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

use FacturaScripts\Core\Template\ErrorController;
use FacturaScripts\Core\Tools;

class AlreadyInstalled extends ErrorController
{
    public function run(): void
    {
        http_response_code(403);

        $title = '✅ ' . Tools::trans('already-installed');
        $content = '<h1 class="h3">' . $title . '</h1>'
            . '<p>' . $this->exception->getMessage() . '</p>';

        echo $this->html(
            $title,
            $this->htmlContainer(
                $this->htmlErrorCard($content)
            )
        );
    }
}
