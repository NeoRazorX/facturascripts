<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\PluginManager;

/**
 * Description of ProductionErrorHandler
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductionErrorHandler
{

    public function __construct()
    {
        ob_start();
        register_shutdown_function(array($this, 'handle'));
    }

    public function handle()
    {
        $error = error_get_last();
        if (isset($error) && in_array($error["type"], [1, 64])) {
            ob_clean();
            die($this->render($error));
        }
    }

    /**
     *
     * @param array $error
     *
     * @return string
     */
    private function cleanMessage($error)
    {
        $parts = explode(' in ' . $error['file'], $error['message']);
        return $parts[0];
    }

    /**
     *
     * @param array $error
     *
     * @return string
     */
    private function render($error)
    {
        $title = "FATAL ERROR #" . $error["type"];
        $file = str_replace(FS_FOLDER, '', $error['file']);
        return "<html>"
            . "<head>"
            . "<title>" . $title . "</title>"
            . "<style>"
            . "body {background-color: silver;}"
            . '.container{display:flex;justify-content:center;align-items:center;height:100%}'
            . ".message-container {padding: 20px 20px 40px 20px; max-width: 900px; background-color: snow;box-shadow: 1px 1px 1px 1px #00000070}"
            . ".text-center {text-align: center;}"
            . ".btn {padding: 10px; border-radius: 5px; background-color: orange; color: white; text-decoration: none; font-weight: bold;}"
            . 'ul{list-style:none;}li{margin-bottom:5px}'
            . "</style>"
            . "</head>"
            . "<body>"
            . "<div class='container'>"
            . '<div class="message-container">'
            . "<h1 class='text-center'>" . $title . "</h1>"
            . "<ul>"
            . "<li><b>File:</b> " . $file . " (<b>Line " . $error["line"] . "</b>)</li>"
            . "<li><b>Message:</b> " . $this->cleanMessage($error) . "</li>"
            . "<li><b>FacturaScripts:</b> " . PluginManager::CORE_VERSION . "</li>"
            . "<li><b>PHP:</b> " . PHP_VERSION . "</li>"
            . "</ul>"
            . "<div class='text-center'>"
            . "<a href='https://facturascripts.com/contacto' target='_blank' class='btn'>REPORT / INFORMAR</a>"
            . "</div>"
            . "</div>"
            . '</div>'
            . "</body>"
            . "</html>";
    }
}
