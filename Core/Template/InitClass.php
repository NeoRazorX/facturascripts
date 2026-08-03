<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Clase base para el Init de los plugins. Cada plugin puede tener una clase Init
 * en su raíz que extienda de esta, para ejecutar código en cada arranque de
 * FacturaScripts o al activar, actualizar o desinstalar el plugin.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class InitClass
{
    /**
     * Se ejecuta en cada arranque de FacturaScripts (en cada petición).
     * Es el lugar para cargar extensiones y mods, o registrar workers.
     */
    abstract public function init(): void;

    /**
     * Se ejecuta al desinstalar el plugin.
     */
    abstract public function uninstall(): void;

    /**
     * Se ejecuta al activar o actualizar el plugin. Es el lugar para crear o
     * actualizar los datos que necesite el plugin.
     */
    abstract public function update(): void;

    /**
     * Devuelve el namespace del plugin, por ejemplo FacturaScripts\Plugins\MiPlugin.
     *
     * @return string
     */
    protected function getNamespace(): string
    {
        return substr(static::class, 0, -5);
    }

    /**
     * Carga una extensión sobre su clase objetivo, que se deduce del namespace:
     * la extensión MiPlugin\Extension\Model\Cliente se aplica sobre el modelo Cliente.
     * Las clases base de documentos (BusinessDocument, SalesDocument, etc.) aplican
     * la extensión a todos sus modelos, y EditController y ListController a todos
     * los controladores Edit* y List*.
     *
     * @param mixed $extension Instancia de la extensión a cargar.
     *
     * @return bool True si se ha encontrado la clase objetivo y se ha cargado la extensión.
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
                // recorremos todos los controladores que empiezan por Edit
                $controllers = Tools::folderScan(FS_FOLDER . '/Dinamic/Controller/');
                foreach ($controllers as $file) {
                    $controller = '\\FacturaScripts\\Dinamic\\Controller\\' . substr($file, 0, -4);

                    if (str_starts_with($file, 'Edit') && str_ends_with($file, '.php') && class_exists($controller)) {
                        $controller::addExtension($extension);
                    }
                }
                return true;

            case 'Controller\\ListController':
                // recorremos todos los controladores que empiezan por List
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
     * Aplica la extensión a cada uno de los modelos indicados.
     *
     * @param mixed $extension Instancia de la extensión a cargar.
     * @param array $models Nombres de los modelos sobre los que aplicarla.
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

    /**
     * Actualiza los datos de la tabla con el contenido de su CSV de datos iniciales.
     * Útil en el update() del plugin para restaurar datos esenciales.
     *
     * @param string $tableName Nombre de la tabla.
     */
    protected function updateTableData(string $tableName): void
    {
        $sql = CSVImport::updateTableSQL($tableName);
        if ($sql) {
            $dataBase = new DataBase();
            $dataBase->connect();
            $dataBase->exec($sql);
        }
    }
}
