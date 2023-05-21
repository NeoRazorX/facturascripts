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

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Template\ErrorController;
use FacturaScripts\Core\Tools;

class DatabaseError extends ErrorController
{
    public function run(): void
    {
        ob_clean();
        http_response_code(500);

        $info = Kernel::getErrorInfo(
            $this->exception->getCode(),
            $this->exception->getMessage(),
            $this->exception->getFile(),
            $this->exception->getLine()
        );

        $title = Tools::lang()->trans('database-error');
        $body = '<div class="container">'
            . '<div class="row justify-content-center">'
            . '<div class="col-sm-6">'
            . '<div class="card shadow mt-5 mb-5">'
            . '<div class="card-body">'
            . '<img src="' . $info['report_qr'] . '" class="float-end" alt="QR" />'
            . '<h1>' . $title . '</h1>'
            . '<p class="mb-0">' . $this->exception->getMessage() . '</p>'
            . '</div>'
            . '<div class="card-footer">'
            . '<form method="post" action="' . $info['report_url'] . '" target="_blank">'
            . '<input type="hidden" name="error_code" value="' . $info['code'] . '">'
            . '<input type="hidden" name="error_message" value="' . $info['message'] . '">'
            . '<input type="hidden" name="error_file" value="' . $info['file'] . '">'
            . '<input type="hidden" name="error_line" value="' . $info['line'] . '">'
            . '<input type="hidden" name="error_hash" value="' . $info['hash'] . '">'
            . '<input type="hidden" name="error_url" value="' . $info['url'] . '">'
            . '<input type="hidden" name="error_core_version" value="' . $info['core_version'] . '">'
            . '<input type="hidden" name="error_plugin_list" value="' . $info['plugin_list'] . '">'
            . '<input type="hidden" name="error_php_version" value="' . $info['php_version'] . '">'
            . '<input type="hidden" name="error_os" value="' . $info['os'] . '">'
            . '<button type="submit" class="btn btn-secondary">Read more / Leer más</button>'
            . '</form>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        echo $this->html($title, $body, 'bg-danger');
    }
}
