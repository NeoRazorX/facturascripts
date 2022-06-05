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
use FacturaScripts\Dinamic\Model\FormatoDocumento;
use FacturaScripts\Dinamic\Model\LogMessage;

/**
 * Description of Migrations
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Migrations
{

    public static function run()
    {
        self::initModels();
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
        $dataBase = new DataBase();
        $date = date("Y-m-d H:i:s", strtotime("-1 month"));
        $sql = "DELETE FROM logs WHERE channel = 'master' AND time < '" . $date . "';";
        $dataBase->exec($sql);
    }

    private static function fixAccountingEntries()
    {
        // si no existe la tabla 'partidas', terminamos
        $dataBase = new DataBase();
        if (!$dataBase->tableExists('partidas')) {
            return;
        }

        // si no está la columna debeme, terminamos
        $columns = $dataBase->getColumns('partidas');
        if (!isset($columns['debeme'])) {
            return;
        }

        // marcamos como null las columnas 'debeme' y 'haberme'
        foreach (['debeme', 'haberme'] as $column) {
            $sql = strtolower(FS_DB_TYPE) === 'mysql' ?
                "ALTER TABLE partidas MODIFY " . $column . " double NULL DEFAULT NULL;" :
                "ALTER TABLE partidas ALTER COLUMN " . $column . " DROP NOT NULL;";
            $dataBase->exec($sql);
        }
    }

    private static function fixAgents()
    {
        $dataBase = new DataBase();
        $table = 'agentes';
        if ($dataBase->tableExists($table)) {
            $sqlUpdate1 = "UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;";
            $dataBase->exec($sqlUpdate1);
        }
    }

    private static function fixClients()
    {
        $dataBase = new DataBase();
        $table = 'clientes';
        if ($dataBase->tableExists($table)) {
            $sqlUpdate1 = "UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;";
            $dataBase->exec($sqlUpdate1);
            $sqlUpdate2 = "UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;";
            $dataBase->exec($sqlUpdate2);
        }
    }

    private static function fixContacts()
    {
        $dataBase = new DataBase();
        $table = 'contactos';
        if ($dataBase->tableExists($table)) {
            $sqlUpdate1 = "UPDATE " . $table . " SET aceptaprivacidad = false WHERE aceptaprivacidad IS NULL;";
            $dataBase->exec($sqlUpdate1);
            $sqlUpdate2 = "UPDATE " . $table . " SET admitemarketing = false WHERE admitemarketing IS NULL;";
            $dataBase->exec($sqlUpdate2);
            $sqlUpdate3 = "UPDATE " . $table . " SET habilitado = true WHERE habilitado IS NULL;";
            $dataBase->exec($sqlUpdate3);
            $sqlUpdate4 = "UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;";
            $dataBase->exec($sqlUpdate4);
            $sqlUpdate5 = "UPDATE " . $table . " SET verificado = false WHERE verificado IS NULL;";
            $dataBase->exec($sqlUpdate5);
        }
    }

    private static function fixInvoiceLines()
    {
        $dataBase = new DataBase();
        $tables = ['lineasfacturascli', 'lineasfacturasprov'];
        foreach ($tables as $table) {
            if ($dataBase->tableExists($table)) {
                $sql = "UPDATE " . $table . " SET irpf = '0' WHERE irpf IS NULL;";
                $dataBase->exec($sql);
            }
        }
    }

    private static function fixSuppliers()
    {
        $dataBase = new DataBase();
        $table = 'proveedores';
        if ($dataBase->tableExists($table)) {
            $sqlUpdate1 = "UPDATE " . $table . " SET acreedor = false WHERE acreedor IS NULL;";
            $dataBase->exec($sqlUpdate1);
            $sqlUpdate2 = "UPDATE " . $table . " SET debaja = false WHERE debaja IS NULL;";
            $dataBase->exec($sqlUpdate2);
            $sqlUpdate3 = "UPDATE " . $table . " SET personafisica = true WHERE personafisica IS NULL;";
            $dataBase->exec($sqlUpdate3);
        }
    }

    private static function initModels()
    {
        new FormatoDocumento();
    }

    private static function unlockNullProducts()
    {
        $dataBase = new DataBase();
        if ($dataBase->tableExists('productos')) {
            $sql = 'UPDATE productos SET bloqueado = false WHERE bloqueado IS NULL;';
            $dataBase->exec($sql);
        }
    }

    private static function updateInvoiceStatus()
    {
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
