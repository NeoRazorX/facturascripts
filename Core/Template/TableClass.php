<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Estructura de una tabla definida en PHP, alternativa al XML de Table/ que evita typos.
 *
 * Cómo usarla:
 *  1. Crea Table/<NombreTabla>.php (en el core o en un plugin). El nombre de la clase es el de
 *     la tabla en StudlyCase: agenciastrans -> Agenciastrans, attached_files -> AttachedFiles.
 *  2. Extiende TableClass e implementa define() con el Blueprint.
 *  3. Si existe esta clase, DbUpdater la usa y IGNORA el XML (y las extensiones XML de plugins)
 *     de esa tabla. Si no existe, se sigue usando el XML. Borra el XML al migrar una tabla.
 *  4. Tras crearla ejecuta el deploy (Plugins::deploy) para generar Dinamic/Table/<Clase>.php.
 *     Un plugin puede sobrescribir la clase del core con una del mismo nombre en su Table/.
 *
 * Ejemplo (Plugins/MiPlugin/Table/MiTabla.php):
 *
 *     namespace FacturaScripts\Plugins\MiPlugin\Table;
 *
 *     use FacturaScripts\Core\Template\Blueprint;
 *     use FacturaScripts\Core\Template\TableClass;
 *
 *     class MiTabla extends TableClass
 *     {
 *         public function define(Blueprint $table): void
 *         {
 *             $table->id('idmitabla');
 *             $table->string('codigo', 10)->unique();
 *             $table->string('nombre', 100);
 *             $table->string('nick', 50)->nullable()->references('users', 'nick')->onDelete('SET NULL');
 *             $table->boolean('activo')->default(true);
 *         }
 *     }
 */
abstract class TableClass
{
    /** Declara las columnas, claves e índices de la tabla usando $table. */
    abstract public function define(Blueprint $table): void;

    /**
     * Ejecuta define() y devuelve la estructura en el formato de DbUpdater::readTableXml().
     * $table_name se usa para nombrar las constraints e índices (ej: {tabla}_pkey).
     */
    final public function structure(string $table_name): array
    {
        $table = new Blueprint($table_name);
        $this->define($table);
        return $table->toArray();
    }
}
