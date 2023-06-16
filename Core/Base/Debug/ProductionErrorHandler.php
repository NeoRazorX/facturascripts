<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\Debug;

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;

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
     * @param array $error
     *
     * @return string
     */
    private function cleanMessage($error): string
    {
        $parts = explode(' in ' . $error['file'], $error['message']);
        return $parts[0];
    }

    private function getPluginName($error): string
    {
        // obtenemos la ruta del archivo sin el directorio de la instalación
        $file = substr($error["file"], strlen(FS_FOLDER) + 1);

        // partimos la ruta del archivo en partes
        $parts = explode('/', $file);

        // si la primera parte es Plugins, devolvemos el segundo
        if ($parts[0] == 'Plugins') {
            return $parts[1];
        }

        return '';
    }

    private function getPlugins(): string
    {
        return implode(',', Plugins::enabled());
    }

    /**
     * @param array $error
     *
     * @return string
     */
    private function render($error): string
    {
        $pluginName = $this->getPluginName($error);
        $title = empty($pluginName) ? "FATAL ERROR #" . $error["type"] : 'Plugin ' . $pluginName . ': FATAL ERROR #' . $error["type"];

        // calculamos un hash para el error, de forma que en la web podamos dar respuesta automáticamente
        $code = $error["type"] . substr($error["file"], strlen(FS_FOLDER)) . $error["line"] . $this->cleanMessage($error);
        $hash = sha1($code);

        $btn2 = empty($pluginName) ? '' :
            ' <a class="btn2" href="AdminPlugins?action=disable&plugin=' . $pluginName . '">DISABLE / DESACTIVAR PLUGIN</a>';

        return "<html>"
            . "<head>"
            . "<title>" . $title . "</title>"
            . "<style>"
            . "body {background-color: silver;}"
            . ".container {padding: 20px 20px 40px 20px; max-width: 900px; margin-left: auto; margin-right: auto; border-radius: 10px; background-color: snow;}"
            . ".text-center {text-align: center;}"
            . ".btn1 {padding: 10px; border-radius: 5px; background-color: orange; color: white; text-decoration: none; font-weight: bold;}"
            . ".btn2 {padding: 10px; border-radius: 5px; background-color: silver; color: white; text-decoration: none; font-weight: bold;}"
            . "</style>"
            . "</head>"
            . "<body>"
            . "<div class='container'>"
            . "<h1 class='text-center'>" . $title . "</h1>"
            . "<ul>"
            . "<li><b>File:</b> " . $error["file"] . " (<b>Line " . $error["line"] . "</b>)</li>"
            . "<li><b>Message:</b> " . $this->cleanMessage($error) . "</li>"
            . "<li><b>FacturaScripts:</b> " . Kernel::version() . "</li>"
            . "<li><b>PHP:</b> " . PHP_VERSION . "</li>"
            . "</ul>"
            . "<div class='text-center'>"
            . "<form action='https://facturascripts.com/contacto' method='post' target='_blank'>"
            . "<input type='hidden' name='errhash' value='" . $hash . "' />"
            . "<input type='hidden' name='errtype' value='" . $error["type"] . "' />"
            . "<input type='hidden' name='errfile' value='" . $error["file"] . "' />"
            . "<input type='hidden' name='errline' value='" . $error["line"] . "' />"
            . "<input type='hidden' name='errversion' value='" . Kernel::version() . "' />"
            . "<input type='hidden' name='errplugins' value='" . $this->getPlugins() . "' />"
            . "<input type='hidden' name='errphp' value='" . PHP_VERSION . "' />"
            . "<button type='submit' class='btn1'>REPORT / INFORMAR</button>"
            . $btn2
            . "</form>"
            . "</div>"
            . "</div>"
            . "</body>"
            . "</html>";
    }
}
