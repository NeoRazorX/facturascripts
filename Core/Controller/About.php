<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\User;

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

        // Información del servidor web
        $server_software = $_SERVER['SERVER_SOFTWARE'];

        // Información del sistema operativo
        $os_info = php_uname();

        // Obtener la información de la Base de Datos
        $database_type = $this->dataBase->type();
        $database_version = $this->dataBase->version();

        // Obtener la versión de OpenSSL
        $openssl_version = defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'No disponible';

        // Espacio de almacenamiento para archivos adjuntos
        $storage_limit = AttachedFile::getStorageLimit();
        $storage_used = AttachedFile::getStorageUsed();
        $storage_details = $this->getStorageDetails();

        // Obtener el tamaño maxim de subida de archivo
        $max_filesize = UploadedFile::getMaxFilesize();

        // Obtener la lista de plugins
        $plugins = Plugins::list();

        // Calcular los límites actuales
        $limits = $this->getLimits();

        $server_date = date('d-m-Y H:i:s');

        return compact(
            'core_version',
            'database_type',
            'database_version',
            'extensions',
            'limits',
            'max_filesize',
            'openssl_version',
            'os_info',
            'php_version',
            'plugins',
            'server_date',
            'server_software',
            'storage_details',
            'storage_limit',
            'storage_used',
        );
    }

    private function getLimits(): array
    {
        // Contar usuarios
        $users = User::count();

        // Contar productos
        $products = Producto::count();

        // Contar clientes
        $customers = Cliente::count();

        // Contar facturas de cliente
        $invoices = FacturaCliente::count();

        return [
            'customers' => $customers,
            'invoices' => $invoices,
            'products' => $products,
            'users' => $users,
        ];
    }

    private function getStorageDetails(): array
    {
        $storage = [];

        foreach (Tools::folderScan('MyFiles') as $item) {
            // obtener la ruta completa
            $path = Tools::folder('MyFiles', $item);

            // si no es una carpeta, agrupar por archivo
            if (!is_dir($path)) {
                // obtenemos el tamaño del archivo
                $size = filesize($path);

                // guardar el resultado
                if (!isset($storage['files'])) {
                    $storage['files'] = [
                        'name' => 'files',
                        'size' => 0,
                        'human_size' => '',
                    ];
                }

                $storage['files']['size'] += $size;
                $storage['files']['human_size'] = Tools::bytes($storage['files']['size']);
                continue;
            }

            // obtener el tamaño
            $size = Tools::folderSize($path);

            // guardar el resultado
            $storage[$item] = [
                'name' => $item,
                'size' => $size,
                'human_size' => Tools::bytes($size),
            ];
        }

        return $storage;
    }
}
