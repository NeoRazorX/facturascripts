<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Dinamic\Model\Serie;

final class Migrations
{
    const FILE_NAME = 'migrations.json';

    /** @var DataBase */
    private static $database;

    public static function run(): void
    {
        self::runMigration('clearLogs', [self::class, 'clearLogs']);
        self::runMigration('fixSeries', [self::class, 'fixSeries']);
        self::runMigration('fixAgentes', [self::class, 'fixAgentes']);
        self::runMigration('fixApiKeysUsers', [self::class, 'fixApiKeysUsers']);
        self::runMigration('fixAgenciasTransporte', [self::class, 'fixAgenciasTransporte']);
        self::runMigration('fixFormasPago', [self::class, 'fixFormasPago']);
        self::runMigration('fixRectifiedInvoices', [self::class, 'fixRectifiedInvoices']);
    }

    /**
     * Execute a plugin migration
     *
     * @param MigrationClass $migration The migration instance
     */
    public static function runPluginMigration(MigrationClass $migration): void
    {
        $migrationName = $migration->getFullMigrationName();

        if (self::isMigrationExecuted($migrationName)) {
            return;
        }

        $migration->run();
        self::markMigrationAsExecuted($migrationName);
    }

    /**
     * Execute multiple plugin migrations
     *
     * @param array<MigrationClass> $migrations Array of migration instances
     */
    public static function runPluginMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            self::runPluginMigration($migration);
        }
    }

    private static function clearLogs(): void
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
            self::$database->connect();
        }

        return self::$database;
    }

    // versión 2025.01, fecha 02-12-2025
    private static function fixAgentes(): void
    {
        // forzamos la comprobación de la tabla agentes
        new Agente();

        // desvinculamos los agentes que no existan
        $tables = [
            'albaranescli', 'clientes', 'contactos', 'facturascli', 'pedidoscli', 'presupuestoscli'
        ];
        foreach ($tables as $table) {
            if (false === self::db()->tableExists($table)) {
                continue;
            }

            $sql = "UPDATE " . $table . " SET codagente = NULL WHERE codagente IS NOT NULL"
                . " AND codagente NOT IN (SELECT codagente FROM agentes);";

            self::db()->exec($sql);
        }
    }

    // versión 2025.01, fecha 02-12-2025
    private static function fixApiKeysUsers(): void
    {
        // verificamos que existan ambas tablas
        if (false === self::db()->tableExists('api_keys') || false === self::db()->tableExists('users')) {
            return;
        }

        // desvinculamos las api_keys de usuarios que no existan
        $sql = "UPDATE api_keys SET nick = NULL WHERE nick IS NOT NULL"
            . " AND nick NOT IN (SELECT nick FROM users);";

        self::db()->exec($sql);
    }

    private static function fixAgenciasTransporte(): void
    {
        // forzamos la comprobación de la tabla agenciastransporte
        new AgenciaTransporte();

        // desvinculamos las agencias de transporte que no existan
        foreach (['albaranescli', 'facturascli', 'pedidoscli', 'presupuestoscli'] as $table) {
            if (false === self::db()->tableExists($table)) {
                continue;
            }

            $sql = "UPDATE " . $table . " SET codtrans = NULL WHERE codtrans IS NOT NULL"
                . " AND codtrans NOT IN (SELECT codtrans FROM agenciastrans);";

            self::db()->exec($sql);
        }
    }

    // versión 2024.5, fecha 15-04-2024
    private static function fixFormasPago(): void
    {
        // forzamos la comprobación de la tabla formas_pago
        new FormaPago();

        // recorremos las tablas de documentos de compra o venta
        $tables = [
            'albaranescli', 'albaranesprov', 'facturascli', 'facturasprov', 'pedidoscli', 'pedidosprov',
            'presupuestoscli', 'presupuestosprov'
        ];
        foreach ($tables as $table) {
            if (false === self::db()->tableExists($table)) {
                continue;
            }

            // buscamos aquellos códigos de pago que no estén en la tabla formaspago
            $sql = "SELECT DISTINCT codpago FROM " . $table . " WHERE codpago NOT IN (SELECT codpago FROM formaspago);";
            foreach (self::db()->select($sql) as $row) {
                $formaPago = new FormaPago();
                $formaPago->activa = false;
                $formaPago->codpago = $row['codpago'];
                $formaPago->descripcion = Tools::lang()->trans('deleted');
                if ($formaPago->save()) {
                    continue;
                }

                // no hemos podido guardar, la añadimos por sql
                $sql = "INSERT INTO " . FormaPago::tableName() . " (codpago, descripcion) VALUES ("
                    . self::db()->var2str($formaPago->codpago) . ", "
                    . self::db()->var2str($formaPago->descripcion) . ");";
                self::db()->exec($sql);
            }
        }
    }

    // versión 2024.5, fecha 16-04-2024
    private static function fixRectifiedInvoices(): void
    {
        // ponemos a null el idfacturarect de las facturas que rectifiquen a una factura que no existe
        foreach (['facturascli', 'facturasprov'] as $table) {
            if (false === self::db()->tableExists($table)) {
                continue;
            }

            $sql = "UPDATE " . $table . " SET idfacturarect = NULL"
                . " WHERE idfacturarect IS NOT NULL"
                . " AND idfacturarect NOT IN (SELECT idfactura FROM (SELECT idfactura FROM " . $table . ") AS subquery);";

            self::db()->exec($sql);
        }
    }

    // version 2023.06, fecha 07-10-2023
    private static function fixSeries(): void
    {
        // forzamos la comprobación de la tabla series
        new Serie();

        // actualizamos con el tipo R la serie marcada como rectificativa en el panel de control
        $serieRectifying = Tools::settings('default', 'codserierec', '');
        if (empty($serieRectifying)) {
            return;
        }

        $sqlUpdate = "UPDATE series SET tipo = 'R' WHERE codserie = " . self::db()->var2str($serieRectifying) . ";";
        self::db()->exec($sqlUpdate);
    }

    private static function getExecutedMigrations(): array
    {
        $file = Tools::folder('MyFiles', self::FILE_NAME);
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private static function isMigrationExecuted(string $migrationName): bool
    {
        $executed = self::getExecutedMigrations();
        return in_array($migrationName, $executed, true);
    }

    private static function markMigrationAsExecuted(string $migrationName): void
    {
        $executed = self::getExecutedMigrations();
        if (in_array($migrationName, $executed, true)) {
            return;
        }

        $executed[] = $migrationName;

        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));
        file_put_contents(
            Tools::folder('MyFiles', self::FILE_NAME),
            json_encode($executed, JSON_PRETTY_PRINT)
        );
    }

    private static function runMigration(string $migrationName, callable $callback): void
    {
        if (self::isMigrationExecuted($migrationName)) {
            return;
        }

        call_user_func($callback);
        self::markMigrationAsExecuted($migrationName);
    }
}
