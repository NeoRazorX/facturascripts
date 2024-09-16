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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;

class ApiRoot extends ApiController
{
    /** @var array */
    private static $custom_resources = ['crearFacturaCliente', 'exportarFacturaCliente'];

    public static function addCustomResource(string $name): void
    {
        self::$custom_resources[] = $name;
    }

    protected function exposeResources(array &$map): void
    {
        $json = ['resources' => self::$custom_resources];
        foreach (array_keys($map) as $key) {
            $json['resources'][] = $key;
        }

        // ordenamos
        sort($json['resources']);

        $this->response->setContent(json_encode($json));
    }

    public static function getCustomResources(): array
    {
        return self::$custom_resources;
    }

    protected function getResourcesMap(): array
    {
        $resources = [];

        // recorremos todas las clases en /Dinamic/Lib/API
        $folder = Tools::folder('Dinamic', 'Lib', 'API');
        foreach (Tools::folderScan($folder, false) as $resource) {
            if (substr($resource, -4) !== '.php') {
                continue;
            }

            // The name of the class will be the same as that of the file without the php extension.
            // Classes will be descendants of Base/APIResourceClass.
            $class = substr('\\FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
            $APIClass = new $class($this->response, $this->request, []);
            if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                // getResources obtains an associative array of arrays generated
                // with setResource ('name'). These arrays keep the name of the class
                // and the resource so that they can be invoked later.
                //
                // This allows using different API extensions, and not just the
                // usual Lib/API/APIModel.
                $resources[] = $APIClass->getResources();
            }
        }

        return array_merge(...$resources);
    }

    protected function runResource(): void
    {
        $map = $this->getResourcesMap();

        $resourceName = $this->getUriParam(2);
        if ($resourceName === '') {
            // If no command, expose resources and exit
            $this->exposeResources($map);
            return;
        }

        if (!isset($map[$resourceName]['API'])) {
            throw new KernelException('InvalidApiResource', Tools::lang()->trans('api-resource-invalid'));
        }

        // get params
        $param = 3;
        $params = [];
        while (($item = $this->getUriParam($param)) !== '') {
            $params[] = $item;
            $param++;
        }

        $ApiClass = new $map[$resourceName]['API']($this->response, $this->request, $params);
        $ApiClass->processResource($map[$resourceName]['Name']);
    }
}
