<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\UploadedFile;

class About extends Controller
{
    /** @var array */
    public $data = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'about';
        $data['icon'] = 'fa-solid fa-circle-info';
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->data = $this->getData();
    }

    private function getData(): array
    {
        // Obtener la versión de FacturaScripts
        $core_version = Kernel::version();

        // Obtener la versión de PHP
        $php_version = phpversion();

        // Obtener las extensiones de PHP instaladas
        $extensions = get_loaded_extensions();

        // Obtener el tamaño maxim de subida de archivo
        $max_filesize = UploadedFile::getMaxFilesize();

        // Información del servidor web
        $server_software = $_SERVER['SERVER_SOFTWARE'];

        // Información del sistema operativo
        $os_info = php_uname();

        // Obtener la versión de la Base de Datos
        $database_version = $this->dataBase->version();

        // Obtener la lista de plugins
        $plugins = Plugins::list();

        return compact(
            'core_version',
            'php_version',
            'extensions',
            'server_software',
            'os_info',
            'database_version',
            'max_filesize',
            'plugins'
        );
    }
}
