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
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\LineaAlbaranCliente;
use FacturaScripts\Dinamic\Model\LineaAlbaranProveedor;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor;
use FacturaScripts\Dinamic\Model\LineaPedidoCliente;
use FacturaScripts\Dinamic\Model\LineaPedidoProveedor;
use FacturaScripts\Dinamic\Model\LineaPresupuestoCliente;
use FacturaScripts\Dinamic\Model\LineaPresupuestoProveedor;
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Ejecutor de migraciones del núcleo y de los plugins.
 *
 * Cada migración es una pequeña tarea idempotente que arregla datos heredados o ajusta el esquema
 * tras un cambio incompatible (por ejemplo, desvincular registros huérfanos, normalizar valores
 * de excepciones de IVA o forzar la creación/comprobación de una tabla instanciando su modelo).
 *
 * Las migraciones se identifican por nombre y solo se ejecutan una vez: el listado de las ya
 * aplicadas se guarda en `MyFiles/migrations.json`. Si el JSON se borra, todas las migraciones
 * volverán a ejecutarse, por eso cada una está pensada para ser segura de re-ejecutar (comprueba
 * existencia de tablas/columnas, sólo actúa si hay datos a corregir, etc.).
 *
 * Las migraciones del núcleo se registran en `run()`. Los plugins pueden registrar las suyas
 * extendiendo `MigrationClass` y llamando a `runPluginMigration()` / `runPluginMigrations()`
 * desde su inicialización.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Migrations
{
    /** Nombre del fichero JSON, dentro de MyFiles, donde se persisten las migraciones ya ejecutadas. */
    const FILE_NAME = 'migrations.json';

    /** Conexión perezosa a la base de datos, compartida entre todas las migraciones de la ejecución. @var DataBase */
    private static $database;

    /**
     * Ejecuta todas las migraciones del núcleo en el orden definido.
     *
     * Cada migración se invoca a través de `runMigration()`, que se encarga de saltarla si ya
     * había sido aplicada anteriormente. El orden importa: hay migraciones que dependen de que
     * otras hayan creado/normalizado datos antes (por ejemplo, las que desvinculan registros
     * huérfanos asumen que las tablas referenciadas ya existen).
     */
    public static function run(): void
    {
        self::runMigration('clearLogs', [self::class, 'clearLogs']);
        self::runMigration('fixSeries', [self::class, 'fixSeries']);
        self::runMigration('fixAgentes', [self::class, 'fixAgentes']);
        self::runMigration('fixApiKeysUsers', [self::class, 'fixApiKeysUsers']);
        self::runMigration('fixAgenciasTransporte', [self::class, 'fixAgenciasTransporte']);
        self::runMigration('fixFormasPago', [self::class, 'fixFormasPago']);
        self::runMigration('fixRectifiedInvoices', [self::class, 'fixRectifiedInvoices']);
        self::runMigration('fixClientesOperationFromVatException', [self::class, 'fixClientesOperationFromVatException']);
        self::runMigration('fixTaxException', [self::class, 'fixTaxException']);
    }

    /**
     * Ejecuta una migración de plugin si no se había ejecutado previamente.
     *
     * El nombre completo (que se compara contra el registro persistido) lo proporciona la propia
     * migración mediante `getFullMigrationName()`, lo que permite a cada plugin elegir un esquema
     * de nombrado que evite colisiones con otros plugins o con el núcleo.
     *
     * @param MigrationClass $migration instancia ya construida de la migración a ejecutar
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
     * Ejecuta una lista de migraciones de plugin en el orden recibido.
     *
     * Es un simple atajo sobre `runPluginMigration()`; cada migración decide individualmente
     * si debe ejecutarse según el registro persistido.
     *
     * @param array<MigrationClass> $migrations migraciones a ejecutar, en orden
     */
    public static function runPluginMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            self::runPluginMigration($migration);
        }
    }

    /**
     * Purga logs antiguos del canal "master" cuando la tabla supera 20.000 filas.
     *
     * Si el canal master tiene menos de 20.000 registros no se hace nada. En caso contrario,
     * se borran las entradas con más de un mes de antigüedad para evitar que la tabla siga
     * creciendo y degrade el rendimiento de las consultas que la usan (página de logs, etc.).
     */
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

    /** Devuelve la conexión a base de datos, abriéndola la primera vez (singleton perezoso interno). */
    private static function db(): DataBase
    {
        if (self::$database === null) {
            self::$database = new DataBase();
            self::$database->connect();
        }

        return self::$database;
    }

    /**
     * Pone a NULL los `codagente` huérfanos en documentos y entidades relacionadas.
     *
     * Versión 2025.01, fecha 02-12-2025. Se instancia el modelo `Agente` para forzar la creación
     * o comprobación de la tabla `agentes` antes de la limpieza. A partir de ahí, en cada tabla
     * existente se anulan los códigos de agente que ya no aparecen en `agentes`, evitando claves
     * foráneas rotas que romperían validaciones posteriores.
     */
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

    /**
     * Desvincula las api_keys cuyo `nick` apunta a un usuario que ya no existe.
     *
     * Versión 2025.01, fecha 02-12-2025. Si alguna de las dos tablas (`api_keys` o `users`)
     * todavía no existe, la migración no hace nada y se da por aplicada en la siguiente vuelta.
     */
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

    /**
     * Rellena `clientes.operacion` a partir de `clientes.excepcioniva` cuando viene vacía.
     *
     * Versión 2026.01, fecha 06-03-2026. Con la nueva validación, ciertas excepciones de IVA
     * (exportaciones y operaciones intracomunitarias) requieren que el cliente tenga informada
     * la operación. Para no romper a clientes ya existentes, esta migración mapea las excepciones
     * conocidas a su operación correspondiente, pero sólo cuando la columna `operacion` está NULL
     * (no se sobrescriben valores ya configurados manualmente).
     */
    private static function fixClientesOperationFromVatException(): void
    {
        if (false === self::db()->tableExists('clientes')) {
            return;
        }

        $columns = self::db()->getColumns('clientes');
        if (!isset($columns['operacion']) || !isset($columns['excepcioniva'])) {
            return;
        }

        // compatibilidad: con la nueva validación, estos casos requieren operación informada
        $updates = [
            TaxExceptions::ES_TAX_EXCEPTION_21 => InvoiceOperation::EXPORT,
            TaxExceptions::ES_TAX_EXCEPTION_22 => InvoiceOperation::INTRA_COMMUNITY,
            TaxExceptions::ES_TAX_EXCEPTION_23_24 => InvoiceOperation::INTRA_COMMUNITY,
            TaxExceptions::ES_TAX_EXCEPTION_25 => InvoiceOperation::INTRA_COMMUNITY,
        ];

        foreach ($updates as $exception => $operation) {
            $sql = "UPDATE clientes SET operacion = " . self::db()->var2str($operation)
                . " WHERE operacion IS NULL AND excepcioniva = " . self::db()->var2str($exception) . ";";
            self::db()->exec($sql);
        }
    }

    /**
     * Pone a NULL los `codtrans` huérfanos en documentos de venta.
     *
     * Se instancia `AgenciaTransporte` para asegurar que la tabla `agenciastrans` existe y, a
     * continuación, se anulan los códigos de agencia que ya no se encuentran en ella.
     */
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

    /**
     * Recupera formas de pago referenciadas pero inexistentes recreándolas como inactivas.
     *
     * Versión 2024.5, fecha 15-04-2024. En lugar de anular los `codpago` huérfanos (lo que
     * perdería la trazabilidad histórica de los documentos), se reconstruyen los registros
     * faltantes en `formaspago` con `activa=false` y descripción "deleted". Si el modelo no
     * puede guardarlos (por ejemplo por validaciones), se recurre a un INSERT directo por SQL
     * para garantizar que la integridad referencial queda restaurada.
     */
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

    /**
     * Anula `idfacturarect` en facturas que rectifican a una factura inexistente.
     *
     * Versión 2024.5, fecha 16-04-2024. La subconsulta se envuelve en otro SELECT (alias
     * `subquery`) para sortear la limitación de MySQL que impide referenciar la misma tabla
     * que se está actualizando dentro de un IN/NOT IN; ese rodeo hace que MySQL la materialice
     * y la trate como una fuente independiente.
     */
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

    /**
     * Marca con tipo "R" la serie configurada como rectificativa en el panel de control.
     *
     * Versión 2023.06, fecha 07-10-2023. Antes la serie rectificativa se identificaba sólo por
     * el ajuste `default.codserierec`; ahora cada serie lleva además un campo `tipo`. Si no hay
     * serie configurada, la migración no hace nada.
     */
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

    /**
     * Renombra códigos antiguos de excepción de IVA a su nuevo identificador en todas las tablas afectadas.
     *
     * TODO: añadir versión y fecha de lanzamiento. Se instancian primero los modelos para forzar
     * la creación/comprobación de las tablas, y después se aplica un UPDATE con CASE que mapea los
     * códigos heredados (`ES_N1`, `ES_ART_7`, etc.) a los nuevos (`ES_7`, `ES_84`, ...). Las tablas
     * sin la columna `excepcioniva` se saltan, lo que permite que la migración sea segura aunque
     * algún plugin haya alterado el esquema.
     */
    private static function fixTaxException(): void
    {
        // forzamos la comprobación de las tablas
        new Empresa();
        new Cliente();
        new Proveedor();
        new LineaPresupuestoProveedor();
        new LineaPedidoProveedor();
        new LineaAlbaranProveedor();
        new LineaFacturaProveedor();
        new LineaPresupuestoCliente();
        new LineaPedidoCliente();
        new LineaAlbaranCliente();
        new LineaFacturaCliente();

        // recorremos todas las tablas que tienen el campo excepcioniva
        $tables = [
            'empresas', 'clientes', 'proveedores',
            'lineaspresupuestoprov', 'lineaspedidosprov', 'lineasalbaranesprov', 'lineasfacturasprov',
            'lineaspresupuestocli', 'lineaspedidoscli', 'lineasalbaranescli', 'lineasfacturascli'
        ];
        foreach ($tables as $table) {
            // si la tabla no existe, continuamos
            if (false === self::db()->tableExists($table)) {
                continue;
            }

            // si el campo excepcioniva no existe, continuamos
            $found = false;
            foreach (self::db()->getColumns($table) as $columnInfo) {
                if ($columnInfo['name'] === 'excepcioniva') {
                    $found = true;
                    break;
                }
            }
            if (false === $found) {
                continue;
            }

            // cambios
            // ES_N1 = ES_7
            // ES_N5 = ES_OTHER_NOT_SUBJECT
            // ES_ART_7 = ES_7
            // ES_ART_14 = ES_14
            // ES_LOCATION_RULES = ES_68_70
            // ES_PASSIVE_SUBJECT = ES_84
            self::db()->exec("UPDATE " . $table . " SET excepcioniva = CASE "
                . "WHEN excepcioniva = 'ES_N1' THEN 'ES_7' "
                . "WHEN excepcioniva = 'ES_N5' THEN 'ES_OTHER_NOT_SUBJECT' "
                . "WHEN excepcioniva = 'ES_ART_7' THEN 'ES_7' "
                . "WHEN excepcioniva = 'ES_ART_14' THEN 'ES_14' "
                . "WHEN excepcioniva = 'ES_LOCATION_RULES' THEN 'ES_68_70' "
                . "WHEN excepcioniva = 'ES_PASSIVE_SUBJECT' THEN 'ES_84' "
                . "ELSE excepcioniva END;");
        }
    }

    /**
     * Devuelve la lista de migraciones ya ejecutadas, leyéndola del fichero JSON.
     *
     * Cualquier problema (fichero ausente, lectura fallida o JSON corrupto) se trata como
     * "no hay migraciones ejecutadas", lo que provoca que se reintenten todas. Por eso cada
     * migración debe ser idempotente.
     */
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

    /** Indica si la migración con ese nombre ya consta como ejecutada en el JSON de control. */
    private static function isMigrationExecuted(string $migrationName): bool
    {
        $executed = self::getExecutedMigrations();
        return in_array($migrationName, $executed, true);
    }

    /**
     * Registra una migración como ejecutada, persistiendo el cambio en `MyFiles/migrations.json`.
     *
     * Si el nombre ya estaba registrado no se hace nada. Antes de escribir se asegura que el
     * directorio MyFiles existe; el JSON se guarda con `JSON_PRETTY_PRINT` para que sea legible
     * y diffeable a mano.
     */
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

    /**
     * Ejecuta el callback indicado y marca la migración como aplicada, salvo que ya lo estuviera.
     *
     * Es el único punto por el que pasan todas las migraciones del núcleo. Si el callback lanza
     * una excepción, la migración no se marcará como ejecutada y se reintentará en el próximo
     * arranque.
     */
    private static function runMigration(string $migrationName, callable $callback): void
    {
        if (self::isMigrationExecuted($migrationName)) {
            return;
        }

        call_user_func($callback);
        self::markMigrationAsExecuted($migrationName);
    }
}
