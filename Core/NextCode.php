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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Base\DataBase;

final class NextCode
{
    public static function clearOld(): void
    {
        // eliminamos archivos .lock de hace más de 1 hora
        $folder = Tools::folder('MyFiles', 'Tmp');
        foreach (Tools::folderScan($folder) as $file) {
            if (false === strpos($file, '.lock')) {
                continue;
            }

            $file_path = $folder . DIRECTORY_SEPARATOR . $file;
            if (filemtime($file_path) < time() - 3600) {
                unlink($file_path);
            }
        }
    }

    public static function get(string $table, string $column, string $type = 'int'): ?int
    {
        $db = new DataBase();
        $where = [];

        if (false === in_array($type, ['integer', 'int', 'serial'])) {
            // en el caso de que no sea un campo numérico, buscamos un número y hacemos cast
            $where[] = Where::regexp($column, '^-?[0-9]+$');
            $column = $db->getEngine()->getSQL()->sql2Int($column);
        }

        // buscamos el máximo valor de la columna
        $sql = 'SELECT MAX(' . $column . ') as cod FROM ' . $table . Where::multiSql($where) . ';';
        $data = $db->select($sql);
        $value = empty($data) ? 1 : 1 + (int)$data[0]['cod'];

        return self::lock($table, $column, $value);
    }

    private static function lock(string $table, string $column, int $value): ?int
    {
        for ($i = 0; $i < 9; ++$i) {
            // intentamos crear el archivo de bloqueo
            $file_name = $table . '_' . $column . '_' . $value . '.lock';
            $file_path = Tools::folder('MyFiles', 'Tmp', $file_name);
            $file = fopen($file_path, 'x');
            if (false === $file) {
                // si no se ha podido crear el archivo, intentamos con otro valor
                $value++;
                continue;
            }

            return $value;
        }

        Tools::log()->error('cant-lock-next-code', [
            '%table%' => $table,
            '%column%' => $column,
            '%value%' => $value
        ]);

        return null;
    }
}
