<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\LogMessage;

/**
 * Description of Migrations
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Migrations
{
    private static $database;

    public static function run()
    {
        self::unlockNullProducts();
        self::updateInvoiceStatus();
        self::fixInvoiceLines();
        self::fixAccountingEntries();
        self::fixContacts();
        self::fixAgents();
        self::fixClients();
        self::fixSuppliers();
        self::clearLogs();
    }

    private static function clearLogs()
    {
        $logModel = new LogMessage();
        $where = [new DataBaseWhere('channel', 'master')];
        if ($logModel->count($where) < 20000) {
            return;
        }

        // cuando hay miles de registros en el canal master, eliminamos los antiguos para evitar problemas de rendimiento
        $date = date("Y-m-d H:i:s", strtotime("-1 month"));
        $sql = "DELETE FROM logs WHERE channel = 'master' AND time < '" . $date . "';";
        self::db()->exec($sql);
    }

    private static function db(): DataBase
    {
        if (self::$database === null) {
            self::$database = new DataBase();
        }

        return self::$database;
    }

    private static function fixAccountingEntries()
    {
        // version 2022.09, fecha 05-06-2022
        // si no existe la tabla 'partidas', terminamos
        if (false === self::db()->tableExists('partidas')) {
            return;
        }

        // si no está la columna debeme, terminamos
        $columns = self::db()->getColumns('partidas');
        if (!isset($columns['debeme'])) {
            return;
        }

        // marcamos como null las columnas 'debeme' y 'haberme'
        foreach (['debeme', 'haberme'] as $column) {
            $sql = strtolower(FS_DB_TYPE) === 'mysql' ?
                "ALTER TABLE partidas MODIFY " . $column . " double NULL DEFAULT NULL;" :
                "ALTER TABLE partidas ALTER COLUMN " . $column . " DROP NOT NULL;";
            self::db()->exec($sql);
        }
    }

    private static function fixAgents()
    {
        // version 2022.09, fecha 05-06-2022
        $table = 'agentes';
        if (self::db()->tableExists($table)) {
            $sqlUpdate = "UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;";
            self::db()->exec($sqlUpdate);
        }
    }

    private static function fixClients()
    {
        // version 2022.09, fecha 05-06-2022
        $table = 'clientes';
        if (self::db()->tableExists($table)) {
            $sqlUpdate = "UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;"
                . " UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;";
            self::db()->exec($sqlUpdate);
        }
    }

    private static function fixContacts()
    {
        // version 2022.09, fecha 05-06-2022
        $table = 'contactos';
        if (self::db()->tableExists($table)) {
            $sqlUpdate = "UPDATE " . $table . " SET aceptaprivacidad = false WHERE aceptaprivacidad IS NULL;"
                . " UPDATE " . $table . " SET admitemarketing = false WHERE admitemarketing IS NULL;"
                . " UPDATE " . $table . " SET habilitado = true WHERE habilitado IS NULL;"
                . " UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;"
                . " UPDATE " . $table . " SET verificado = false WHERE verificado IS NULL;";
            self::db()->exec($sqlUpdate);
        }
    }

    private static function fixInvoiceLines()
    {
        // version 2022.09, fecha 05-06-2022
        $tables = ['lineasfacturascli', 'lineasfacturasprov'];
        foreach ($tables as $table) {
            if (self::db()->tableExists($table)) {
                $sql = "UPDATE " . $table . " SET irpf = '0' WHERE irpf IS NULL;";
                self::db()->exec($sql);
            }
        }
    }

    private static function fixSuppliers()
    {
        // version 2022.09, fecha 05-06-2022
        $table = 'proveedores';
        if (self::db()->tableExists($table)) {
            $sqlUpdate = "UPDATE " . $table . " SET acreedor = false WHERE acreedor IS NULL;"
                . " UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;"
                . " UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;";
            self::db()->exec($sqlUpdate);
        }
    }

    private static function unlockNullProducts()
    {
        // version 2022.06, fecha 05-05-2022
        if (self::db()->tableExists('productos')) {
            $sql = 'UPDATE productos SET bloqueado = false WHERE bloqueado IS NULL;';
            self::db()->exec($sql);
        }
    }

    private static function updateInvoiceStatus()
    {
        // version 2021.81, fecha 01-02-2022
        $status = new EstadoDocumento();
        if ($status->loadFromCode('10') && $status->nombre === 'Nueva') {
            // unlock
            $status->bloquear = false;
            $status->save();
            // update
            $status->bloquear = true;
            $status->editable = true;
            $status->nombre = 'Boceto';
            $status->predeterminado = true;
            $status->save();
        }

        if ($status->loadFromCode('11') && $status->nombre === 'Completada') {
            // unlock
            $status->bloquear = false;
            $status->save();
            // update
            $status->bloquear = true;
            $status->editable = false;
            $status->nombre = 'Emitida';
            $status->save();
        }

        if ($status->loadFromCode('21') && $status->nombre === 'Nueva') {
            // unlock
            $status->bloquear = false;
            $status->save();
            // update
            $status->bloquear = true;
            $status->editable = true;
            $status->nombre = 'Boceto';
            $status->predeterminado = true;
            $status->save();
        }

        if ($status->loadFromCode('22') && $status->nombre === 'Completada') {
            // unlock
            $status->bloquear = false;
            $status->save();
            // update
            $status->bloquear = true;
            $status->editable = false;
            $status->nombre = 'Recibida';
            $status->save();
        }
    }
}
