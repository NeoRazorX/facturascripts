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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase\SqliteQueries;
use FacturaScripts\Core\DB;
use FacturaScripts\Core\DB\Connection;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la clase DB (acceso a la base de datos con PDO) y de sus conexiones.
 *
 * Las pruebas con varias bases de datos usan SQLite en memoria, que no deja nada en la base
 * de datos de FacturaScripts.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 * @covers \FacturaScripts\Core\DB
 * @covers \FacturaScripts\Core\DB\Connection
 * @covers \FacturaScripts\Core\DB\SqliteFunctions
 * @covers \FacturaScripts\Core\Base\DataBase\SqliteQueries
 */
final class DBTest extends TestCase
{
    /** @var string Id de la conexión SQLite en memoria que usan los tests. */
    const MEMORY_ID = 'test-memory';

    protected function tearDown(): void
    {
        DB::close(self::MEMORY_ID);
    }

    public function testDefaultConnection(): void
    {
        $this->assertTrue(DB::connected(), DB::lastError());
        $this->assertSame(DB::DEFAULT_CONNECTION, DB::connection()->id());
        $this->assertContains(DB::DEFAULT_CONNECTION, DB::connections());

        // el tipo coincide con el de la configuración
        $this->assertSame(Connection::normalizeType((string)Tools::config('db_type')), DB::type());
        $this->assertNotEmpty(DB::version());
    }

    public function testDefaultSelect(): void
    {
        $this->assertTrue(DB::tableExists('settings'));
        $this->assertContains('settings', DB::tables());

        // insertamos dos registros con una consulta preparada
        $name1 = 'test-db-a-' . mt_rand(1, 99999);
        $name2 = 'test-db-b-' . mt_rand(1, 99999);
        $sql = 'INSERT INTO settings (name, properties) VALUES (?, ?), (?, ?)';
        $this->assertTrue(DB::exec($sql, [$name1, '{}', $name2, '{}']), DB::lastError());

        // consulta normal, preparada con nombre y paginada
        $rows = DB::select("SELECT * FROM settings WHERE name LIKE 'test-db-%' ORDER BY name");
        $this->assertCount(2, $rows);
        $this->assertSame($name1, $rows[0]['name']);

        $prepared = DB::select('SELECT * FROM settings WHERE name = :name', ['name' => $name2]);
        $this->assertCount(1, $prepared);
        $this->assertSame($name2, $prepared[0]['name']);

        $page = DB::selectLimit("SELECT * FROM settings WHERE name LIKE 'test-db-%' ORDER BY name", 1, 1);
        $this->assertCount(1, $page);
        $this->assertSame($name2, $page[0]['name']);

        // borramos los registros
        $this->assertTrue(DB::exec('DELETE FROM settings WHERE name IN (?, ?)', [$name1, $name2]));
        $this->assertCount(0, DB::select("SELECT * FROM settings WHERE name LIKE 'test-db-%'"));
    }

    public function testDefaultTransactionRollback(): void
    {
        $this->assertTrue(DB::beginTransaction());
        $this->assertTrue(DB::inTransaction());

        $name = 'test-db-' . mt_rand(1, 99999);
        $this->assertTrue(DB::exec('INSERT INTO settings (name, properties) VALUES (?, ?)', [$name, '{}']));
        $this->assertCount(1, DB::select('SELECT name FROM settings WHERE name = ?', [$name]));

        // al descartar la transacción, el registro desaparece
        $this->assertTrue(DB::rollback());
        $this->assertFalse(DB::inTransaction());
        $this->assertCount(0, DB::select('SELECT name FROM settings WHERE name = ?', [$name]));
    }

    public function testSelectError(): void
    {
        $this->assertSame([], DB::select('SELECT * FROM table_that_does_not_exist_db_test'));
        $this->assertNotEmpty(DB::lastError());

        // el error se limpia con la siguiente consulta correcta
        DB::select('SELECT * FROM settings');
        $this->assertSame('', DB::lastError());
    }

    public function testVar2str(): void
    {
        $this->assertSame('NULL', DB::var2str(null));
        $this->assertSame('TRUE', DB::var2str(true));
        $this->assertSame('FALSE', DB::var2str(false));
        $this->assertSame("'2024-01-31'", DB::var2str('31-01-2024'));
        $this->assertSame("'2024-01-31 10:20:30'", DB::var2str('31-01-2024 10:20:30'));
        // los números van entre comillas, salvo en SQLite
        $this->assertSame(DB::type() === Connection::TYPE_SQLITE ? '15' : "'15'", DB::var2str(15));

        // las comillas simples se escapan
        $quoted = DB::var2str("O'Brien");
        $this->assertContains($quoted, ["'O''Brien'", "'O\\'Brien'"]);
    }

    public function testVar2strArrayNotAllowed(): void
    {
        $this->expectException(KernelException::class);
        DB::var2str(['a']);
    }

    public function testNewConnection(): void
    {
        $this->skipWithoutSqlite();

        $params = ['id' => self::MEMORY_ID, 'type' => 'sqlite', 'name' => ':memory:'];

        // la conexión no se abre hasta llamar a connect()
        $memory = DB::newConnection($params);
        $this->assertFalse($memory->connected());
        $this->assertTrue($memory->connect(), $memory->lastError());
        $this->assertSame(Connection::TYPE_SQLITE, $memory->type());
        $this->assertStringStartsWith('SQLite ', $memory->version());

        // creamos una tabla e insertamos datos con consultas preparadas
        $this->assertTrue($memory->exec('CREATE TABLE familias (codfamilia VARCHAR(8) PRIMARY KEY, descripcion TEXT);'));
        $this->assertTrue($memory->exec('INSERT INTO familias (codfamilia, descripcion) VALUES (?, ?)', ['F1', 'Uno']));
        $this->assertTrue($memory->exec(
            'INSERT INTO familias (codfamilia, descripcion) VALUES (:cod, :desc)',
            ['cod' => 'F2', 'desc' => 'Dos']
        ));

        // la misma conexión se recupera por su id
        $this->assertSame($memory, DB::connection(self::MEMORY_ID));
        $families = DB::connection(self::MEMORY_ID)->select('SELECT * FROM familias ORDER BY codfamilia');
        $this->assertCount(2, $families);
        $this->assertSame('Uno', $families[0]['descripcion']);
        $this->assertTrue($memory->tableExists('familias'));
        $this->assertSame(['familias'], $memory->tables());

        // la base de datos de FacturaScripts sigue siendo otra conexión distinta
        $this->assertNotSame($memory, DB::connection());
        $this->assertNotSame(':memory:', DB::connection()->name());

        // al cerrarla, deja de estar registrada
        $this->assertTrue(DB::close(self::MEMORY_ID));
        $this->assertNotContains(self::MEMORY_ID, DB::connections());
    }

    public function testNewConnectionReplacesSameId(): void
    {
        $this->skipWithoutSqlite();

        $first = DB::newConnection(['id' => self::MEMORY_ID, 'type' => 'sqlite', 'name' => ':memory:']);
        $this->assertTrue($first->connect());

        $second = DB::newConnection(['id' => self::MEMORY_ID, 'type' => 'sqlite', 'name' => ':memory:']);
        $this->assertSame($second, DB::connection(self::MEMORY_ID));
        $this->assertFalse($first->connected());
    }

    public function testConnectionNotFound(): void
    {
        $this->expectException(KernelException::class);
        DB::connection('connection-that-does-not-exist');
    }

    public function testConnectError(): void
    {
        $this->skipWithoutSqlite();

        // un archivo SQLite dentro de un archivo que no es una carpeta no se puede abrir
        $file = Tools::folder('MyFiles', 'db-test-file.txt');
        file_put_contents($file, 'test');

        $connection = new Connection(['type' => 'sqlite', 'name' => $file . '/test.sqlite']);
        $this->assertFalse($connection->connect());
        $this->assertNotEmpty($connection->lastError());
        $this->assertSame([], $connection->select('SELECT 1'));

        unlink($file);
    }

    public function testTransactions(): void
    {
        $memory = $this->memory();
        $memory->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT);');

        // rollback
        $this->assertTrue($memory->beginTransaction());
        $this->assertTrue($memory->inTransaction());
        $memory->exec("INSERT INTO test (name) VALUES ('a');");
        $this->assertTrue($memory->rollback());
        $this->assertCount(0, $memory->select('SELECT * FROM test'));

        // commit
        $this->assertTrue($memory->beginTransaction());
        $memory->exec("INSERT INTO test (name) VALUES ('b');");
        $this->assertSame(1, $memory->lastInsertId());
        $this->assertTrue($memory->commit());
        $this->assertFalse($memory->inTransaction());
        $this->assertCount(1, $memory->select('SELECT * FROM test'));

        // sin transacción abierta no se puede confirmar ni descartar
        $this->assertFalse($memory->commit());
        $this->assertFalse($memory->rollback());
    }

    public function testSqliteFunctions(): void
    {
        $memory = $this->memory();

        $row = $memory->select("SELECT CONCAT('a', NULL, 'b', 1) AS c, 'Hola' REGEXP '^ho' AS r, NOW() AS n;")[0];
        $this->assertEquals('ab1', $row['c']);
        $this->assertEquals(1, $row['r']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['n']);
    }

    public function testEscapeColumn(): void
    {
        $mysql = new Connection(['type' => 'mysql']);
        $this->assertSame('`a`.`b``c`', $mysql->escapeColumn('a.b`c'));

        $pgsql = new Connection(['type' => 'postgresql']);
        $this->assertSame('"a"."b""c"', $pgsql->escapeColumn('a.b"c'));

        $sqlite = new Connection(['type' => 'sqlite', 'name' => ':memory:']);
        $this->assertSame('"user"', $sqlite->escapeColumn('user'));
    }

    public function testNormalizeType(): void
    {
        $this->assertSame('mysql', Connection::normalizeType('mysql'));
        $this->assertSame('mysql', Connection::normalizeType('MariaDB'));
        $this->assertSame('postgresql', Connection::normalizeType('postgres'));
        $this->assertSame('postgresql', Connection::normalizeType('PostgreSQL'));
        $this->assertSame('postgresql', Connection::normalizeType('pgsql'));
        $this->assertSame('sqlite', Connection::normalizeType('sqlite3'));
    }

    public function testParseUrl(): void
    {
        // url de PostgreSQL al estilo de neon.tech, con contraseña codificada y opciones
        $params = Connection::parseUrl('postgres://user1:p%40ss%3Aw@ep-test.eu.aws.neon.tech:6543/my_db?sslmode=require&endpoint=ep-test');
        $this->assertSame('postgresql', $params['type']);
        $this->assertSame('ep-test.eu.aws.neon.tech', $params['host']);
        $this->assertSame(6543, $params['port']);
        $this->assertSame('my_db', $params['name']);
        $this->assertSame('user1', $params['user']);
        $this->assertSame('p@ss:w', $params['pass']);
        $this->assertSame('require', $params['sslmode']);
        $this->assertSame('ep-test', $params['endpoint']);

        // url de MySQL sin puerto ni contraseña
        $params = Connection::parseUrl('mysql://root@localhost/facturascripts?charset=utf8mb4');
        $this->assertSame('mysql', $params['type']);
        $this->assertSame('localhost', $params['host']);
        $this->assertArrayNotHasKey('port', $params);
        $this->assertArrayNotHasKey('pass', $params);
        $this->assertSame('facturascripts', $params['name']);
        $this->assertSame('utf8mb4', $params['charset']);

        // urls de SQLite
        $this->assertSame('MyFiles/fs.sqlite', Connection::parseUrl('sqlite://MyFiles/fs.sqlite')['name']);
        $this->assertSame('/var/data/fs.sqlite', Connection::parseUrl('sqlite:///var/data/fs.sqlite')['name']);
        $this->assertSame(':memory:', Connection::parseUrl('sqlite://:memory:')['name']);

        // texto que no es una url
        $this->assertSame([], Connection::parseUrl('not an url'));
    }

    public function testConnectionFromUrl(): void
    {
        // la url se completa con los valores por defecto, y los parámetros explícitos tienen prioridad
        $connection = new Connection(['url' => 'pgsql://admin:secret@db.example.com/erp', 'id' => 'remote']);
        $params = $connection->params();
        $this->assertSame('remote', $connection->id());
        $this->assertSame('postgresql', $connection->type());
        $this->assertSame('db.example.com', $params['host']);
        $this->assertSame(5432, $params['port']);
        $this->assertSame('erp', $connection->name());
        $this->assertSame('admin', $params['user']);
        $this->assertArrayNotHasKey('pass', $params);

        $connection = new Connection(['url' => 'mysql://u:p@h:3310/db', 'name' => 'other']);
        $this->assertSame('other', $connection->name());
        $this->assertSame(3310, $connection->params()['port']);

        // en SQLite, las rutas relativas parten de la carpeta de FacturaScripts
        $sqlite = new Connection(['url' => 'sqlite://MyFiles/test.sqlite']);
        $this->assertSame(Tools::folder('MyFiles/test.sqlite'), $sqlite->name());

        // conectar con una url de SQLite en memoria
        $memory = DB::newConnection(['id' => self::MEMORY_ID, 'url' => 'sqlite://:memory:']);
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->assertTrue($memory->connect());
            $this->assertSame([['n' => 1]], $memory->select('SELECT 1 AS n'));
        }
    }

    public function testSqliteCreateTableFromXml(): void
    {
        $memory = $this->memory();
        $queries = new SqliteQueries();

        // creamos en SQLite tablas reales del core, con su clave primaria, claves ajenas e índices
        foreach (['attached_files', 'divisas', 'paises', 'empresas', 'almacenes', 'familias'] as $table) {
            $structure = DbUpdater::readTableXml(DbUpdater::getTableXmlLocation($table));
            $sql = $queries->sqlCreateTable($table, $structure['columns'], $structure['constraints'], $structure['indexes']);
            $this->assertTrue($memory->exec($sql), $table . ': ' . $memory->lastError());
        }

        // las columnas autoincrementales (serial) asignan el id automáticamente
        $memory->exec("INSERT INTO divisas (coddivisa, descripcion, tasaconv, tasaconvcompra) VALUES ('EUR', 'Euro', 1, 1);");
        $memory->exec("INSERT INTO paises (codpais, nombre) VALUES ('ESP', 'España');");
        $this->assertTrue($memory->exec("INSERT INTO empresas (nombre, nombrecorto, cifnif, codpais) VALUES ('Test', 'T', 'B1', 'ESP');"), $memory->lastError());
        $this->assertSame(1, $memory->lastInsertId());

        // las columnas se leen con la sentencia de SqliteQueries
        $columns = array_column($memory->select($queries->sqlColumns('empresas')), null, 'name');
        $this->assertArrayHasKey('idempresa', $columns);
        $this->assertSame('INTEGER', $columns['idempresa']['type']);

        // las claves ajenas están activas: no se puede usar una empresa que no existe
        $this->assertTrue($memory->exec("INSERT INTO almacenes (codalmacen, nombre, idempresa) VALUES ('A1', 'Almacén', 1);"), $memory->lastError());
        $this->assertFalse($memory->exec("INSERT INTO almacenes (codalmacen, nombre, idempresa) VALUES ('A2', 'Almacén', 99);"));

        // añadir y quitar una restricción UNIQUE en una tabla ya creada
        $sql = $queries->sqlAddConstraint('familias', 'uniq_test_familias', 'UNIQUE (descripcion)');
        $this->assertTrue($memory->exec($sql), $memory->lastError());
        $constraints = $memory->select($queries->sqlConstraints('familias'));
        $this->assertSame([['type' => 'UNIQUE', 'name' => 'uniq_test_familias']], $constraints);
        $this->assertTrue($memory->exec($queries->sqlDropConstraint('familias', $constraints[0])));
        $this->assertSame([], $memory->select($queries->sqlConstraints('familias')));

        // los ids autoincrementales no se reutilizan al borrar el último registro
        $insertFile = "INSERT INTO attached_files (date, filename, path, size) VALUES ('2026-01-01', 'a.txt', 'a.txt', 1);";
        $this->assertTrue($memory->exec($insertFile), $memory->lastError());
        $firstId = $memory->lastInsertId();
        $this->assertTrue($memory->exec('DELETE FROM attached_files WHERE idfile = ' . $firstId . ';'));
        $this->assertTrue($memory->exec($insertFile));
        $this->assertSame($firstId + 1, $memory->lastInsertId());

        // las claves ajenas no se pueden añadir después de crear la tabla
        $this->assertSame('', $queries->sqlAddConstraint('familias', 'ca_test', 'FOREIGN KEY (madre) REFERENCES familias (codfamilia)'));
    }

    /**
     * Crea y abre una conexión SQLite en memoria, o salta el test si no hay driver de SQLite.
     *
     * @return Connection
     */
    private function memory(): Connection
    {
        $this->skipWithoutSqlite();

        $memory = DB::newConnection(['id' => self::MEMORY_ID, 'type' => 'sqlite', 'name' => ':memory:']);
        $this->assertTrue($memory->connect(), $memory->lastError());
        return $memory;
    }

    /**
     * Salta el test si PHP no tiene el driver PDO de SQLite.
     */
    private function skipWithoutSqlite(): void
    {
        if (false === in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }
    }
}
