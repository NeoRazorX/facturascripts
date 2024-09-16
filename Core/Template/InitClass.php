<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;

/**
 * Description of InitClass
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class InitClass
{
    /**
     * Code to load every time FacturaScripts starts.
     */
    abstract public function init(): void;

    /**
     * Code that is executed when uninstalling a plugin.
     */
    abstract public function uninstall(): void;

    /**
     * Code to load every time the plugin is enabled or updated.
     */
    abstract public function update(): void;

    protected function getNamespace(): string
    {
        return substr(static::class, 0, -5);
    }

    /**
     * @param mixed $extension
     *
     * @return bool
     */
    protected function loadExtension($extension): bool
    {
        $namespace = get_class($extension);
        $findNamespace = $this->getNamespace() . '\\Extension\\';
        if (strpos($namespace, $findNamespace) !== 0) {
            Tools::log()->error('Target object not found for: ' . $namespace);
            return false;
        }

        $className = substr($namespace, strlen($findNamespace));
        switch ($className) {
            case 'Model\\Base\\BusinessDocument':
                return $this->loadBusinessDocumentExtension($extension, [
                    'AlbaranCliente', 'AlbaranProveedor', 'FacturaCliente', 'FacturaProveedor',
                    'PedidoCliente', 'PedidoProveedor', 'PresupuestoCliente', 'PresupuestoProveedor'
                ]);

            case 'Model\\Base\\BusinessDocumentLine':
                return $this->loadBusinessDocumentExtension($extension, [
                    'LineaAlbaranCliente', 'LineaAlbaranProveedor', 'LineaFacturaCliente',
                    'LineaFacturaProveedor', 'LineaPedidoCliente', 'LineaPedidoProveedor',
                    'LineaPresupuestoCliente', 'LineaPresupuestoProveedor'
                ]);

            case 'Model\\Base\\PurchaseDocument':
                return $this->loadBusinessDocumentExtension($extension, [
                    'AlbaranProveedor', 'FacturaProveedor', 'PedidoProveedor', 'PresupuestoProveedor'
                ]);

            case 'Model\\Base\\PurchaseDocumentLine':
                return $this->loadBusinessDocumentExtension($extension, [
                    'LineaAlbaranProveedor', 'LineaFacturaProveedor', 'LineaPedidoProveedor', 'LineaPresupuestoProveedor'
                ]);

            case 'Model\\Base\\SalesDocument':
                return $this->loadBusinessDocumentExtension($extension, [
                    'AlbaranCliente', 'FacturaCliente', 'PedidoCliente', 'PresupuestoCliente'
                ]);

            case 'Model\\Base\\SalesDocumentLine':
                return $this->loadBusinessDocumentExtension($extension, [
                    'LineaAlbaranCliente', 'LineaFacturaCliente', 'LineaPedidoCliente', 'LineaPresupuestoCliente'
                ]);

            case 'Controller\\EditController':
                // recorremos todos los controlados que empiezan por Edit
                $controllers = Tools::folderScan(FS_FOLDER . '/Dinamic/Controller/');
                foreach ($controllers as $file) {
                    $controller = '\\FacturaScripts\\Dinamic\\Controller\\' . substr($file, 0, -4);

                    if (str_starts_with($file, 'Edit') && str_ends_with($file, '.php') && class_exists($controller)) {
                        $controller::addExtension($extension);
                    }
                }
                return true;

            case 'Controller\\ListController':
                // recorremos todos los controlados que empiezan por List
                $controllers = Tools::folderScan(FS_FOLDER . '/Dinamic/Controller/');
                foreach ($controllers as $file) {
                    $controller = '\\FacturaScripts\\Dinamic\\Controller\\' . substr($file, 0, -4);

                    if (str_starts_with($file, 'List') && str_ends_with($file, '.php') && class_exists($controller)) {
                        $controller::addExtension($extension);
                    }
                }
                return true;
        }

        $targetClass = '\\FacturaScripts\\Dinamic\\' . $className;
        if (class_exists($targetClass)) {
            $targetClass::addExtension($extension);
            return true;
        }

        return false;
    }

    /**
     * @param mixed $extension
     * @param array $models
     *
     * @return bool
     */
    private function loadBusinessDocumentExtension($extension, $models): bool
    {
        foreach ($models as $model) {
            $targetClass = '\\FacturaScripts\\Dinamic\\Model\\' . $model;
            $targetClass::addExtension($extension);
        }

        return true;
    }

    protected function updateTableData(string $tableName): void
    {
        $sql = CSVImport::updateTableSQL($tableName);
        if ($sql) {
            $dataBase = new DataBase();
            $dataBase->exec($sql);
        }
    }
}
