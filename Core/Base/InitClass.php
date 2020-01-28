<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

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
    abstract public function init();

    /**
     * Code to load every time the plugin is enabled or updated.
     */
    abstract public function update();

    /**
     * 
     * @return string
     */
    protected function getNamespace()
    {
        return substr(static::class, 0, -5);
    }

    /**
     * 
     * @param mixed $extension
     *
     * @return bool
     */
    protected function loadExtension($extension)
    {
        $namespace = get_class($extension);
        $findNamespace = $this->getNamespace() . '\\Extension\\';
        if (strpos($namespace, $findNamespace) !== 0) {
            $this->toolBox()->log()->error('Target object not found for: ' . $namespace);
            return false;
        }

        $targetClass = '\\FacturaScripts\\Dinamic\\' . substr($namespace, strlen($findNamespace));
        $targetClass::addExtension($extension);
        return true;
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }

    /**
     * 
     * @param string $tableName
     */
    protected function updateTableData(string $tableName)
    {
        $sql = CSVImport::updateTableSQL($tableName);
        if (!empty($sql)) {
            $dataBase = new DataBase();
            $dataBase->exec($sql);
        }
    }
}
