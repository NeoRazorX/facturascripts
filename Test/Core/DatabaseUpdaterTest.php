<?php

declare(strict_types=1);

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\DatabaseUpdater;
use FacturaScripts\Core\Model\Role;
use PHPUnit\Framework\TestCase;

/**
 * Class DatabaseUpdaterTest
 */
class DatabaseUpdaterTest extends TestCase
{
    private string $tableName;

    private array $xmlCols;

    private array $xmlCons;

    private DataBase $dataBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tableName = 'test';

        $this->xmlCols = [
            [
                'name' => 'test_field',
                'type' => 'character varying(20)',
                'null' => 'NO',
                'default' => '',
            ],
        ];

        $this->xmlCons = [
            [
                'name' => 'tests_pkey',
                'constraint' => 'PRIMARY KEY (test_field)',
            ],
        ];

        $this->dataBase = new DataBase();
        $this->dataBase->connect();
    }

    /**
     * Comprobamos que al ejecutar el metodo checkTable
     * devuelva el sql de creación completa de la tabla
     * según las columnas(xmlCols) y constraints(xmlCons) pasados
     * ya que la tabla no se encuentra creada en la base de datos.
     */
    public function testCheckTableWhenTableDoesNotExist()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Ejecutamos el metodo checkTable.
        $sql_result = DatabaseUpdater::checkTable($this->tableName, $this->xmlCols, $this->xmlCons);

        // Obtenemos el resultado esperado directamente de la clase Database.
        $sql_expected = $this->dataBase->getEngine()->getSQL()->sqlAlterAddColumn($this->tableName, $this->xmlCols[0]);

        // Comprobamos que sean iguales.
        static::assertEquals($sql_expected, $sql_result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva un
     * string vacio ya que no es necesario modificar
     * nada en la base de datos porque hemos creado la tabla
     * en la base de datos con las mismas columnas y constraints
     * que hemos pasado al metodo.
     */
    public function testCheckTableWhenTableExist()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla con las mismas columnas y mismas restrcciones que al ejecutar el metodo checkTable.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, $this->xmlCons);
        $this->dataBase->exec($sql_query);

        // Ejecutamos el metodo checkTable.
        $sql_result = DatabaseUpdater::checkTable($this->tableName, $this->xmlCols, $this->xmlCons);

        // Esperamos que el sql necesario sea vacio.
        $sql_expected = '';

        // Comprobamos que sean iguales.
        static::assertEquals($sql_expected, $sql_result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para crear las columnas ya que
     * hemos creado la tabla sin columnas.
     */
    public function testCheckTableWhenTableExistWithoutColumns()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla sin columnas, solo con constraints.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, [], $this->xmlCons);
        $this->dataBase->exec($sql_query);

        // Ejecutamos el metodo checkTable tanto con columnas como con constraints.
        $result = DatabaseUpdater::checkTable($this->tableName, $this->xmlCols, $this->xmlCons);

        // El sql esperado es el necesario para crear las columnas.
        $expected = $this->dataBase->getEngine()->getSQL()->sqlAlterAddColumn($this->tableName, $this->xmlCols[0]);

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para crear las constraints ya que
     * hemos creado la tabla sin ellas.
     */
    public function testCheckTableWhenTableExistWithoutConstraints()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla sin constraints.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, []);
        $this->dataBase->exec($sql_query);

        // Ejecutamos el metodo checkTable pasandole tanto columnas como constraints.
        $result = DatabaseUpdater::checkTable($this->tableName, $this->xmlCols, [['name' => 'test', 'constraint' => 'UNIQUE (test)']]);

        // El sql esperado es el necesario para crear las constraints.
        $expected = $this->dataBase->getEngine()->getSQL()->sqlAddConstraint($this->tableName, 'test', 'UNIQUE (test)');

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * como sql un string vacio al no pasarle ni columnas
     * ni constraints como parametro.
     */
    public function testCheckTableWithoutXMLConstraints()
    {
        $result = DatabaseUpdater::checkTable($this->tableName, [], []);
        $expected = '';
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para modificar el nuevo tipo de una columna
     * que es diferente al tipo de la columna que ya existe
     * en la base de datos.
     */
    public function testCheckTableModifyColumn()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla con todos los datos.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, $this->xmlCons);
        $this->dataBase->exec($sql_query);

        // Modificamos el tipo de una de las columnas.
        $xmlCols = $this->xmlCols;
        $xmlCols[0]['type'] = 'date';

        // Ejecutamos el metodo checkTable con la columna modificada.
        $result = DatabaseUpdater::checkTable($this->tableName, $xmlCols, $this->xmlCons);

        // Esperamos obtener el sql necesario para modificar la columna.
        $expected = $this->dataBase->getEngine()->getSQL()->sqlAlterModifyColumn($this->tableName, $xmlCols[0]);

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para modificar el valor 'default'
     * de una columna existente en la base de datos.
     */
    public function testCheckTableModifyColumnDefault()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla con todos los datos.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, $this->xmlCons);
        $this->dataBase->exec($sql_query);

        // Modificamos el valor 'default' de una de las columnas.
        $xmlCols = $this->xmlCols;
        $xmlCols[0]['default'] = 'default_value';

        // Ejecutamos el metodo checkTable con la columna default modificada.
        $result = DatabaseUpdater::checkTable($this->tableName, $xmlCols, $this->xmlCons);

        // Esperamos obtener el sql necesario para modificar la columna .
        $expected = $this->dataBase->getEngine()->getSQL()->sqlAlterColumnDefault($this->tableName, $xmlCols[0]);

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para modificar el valor NULL
     * de una columna existente en la base de datos.
     */
    public function testCheckTableModifyColumnNullable()
    {
        // Borramos la tabla si existe.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable($this->tableName);
        $this->dataBase->exec($sql_query);

        // Creamos la tabla con todos los datos.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, $this->xmlCons);
        $this->dataBase->exec($sql_query);

        // Modificamos el valor NULL de una de las columnas.
        $xmlCols = $this->xmlCols;
        $xmlCols[0]['null'] = 'YES';

        // Ejecutamos el metodo checkTable con el valor NULL modificado.
        $result = DatabaseUpdater::checkTable($this->tableName, $xmlCols, $this->xmlCons);

        // Esperamos obtener el sql necesario para modificar la columna .
        $expected = $this->dataBase->getEngine()->getSQL()->sqlAlterColumnNull($this->tableName, $xmlCols[0]);

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo checkTable devuelva
     * el sql necesario para borrar las dos constraints que
     * no se pasan por parametros y añadir la constraint nueva
     * pasada por parametros.
     */
    public function testCheckTableModifyConstraints()
    {
        // Borramos las tablas si existen.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable('test_1');
        $this->dataBase->exec($sql_query);
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable('test_2');
        $this->dataBase->exec($sql_query);

        // Creamos las tablas necesarias para poder usar la restriccion FOREIGN KEY.
        // TABLA test_1
        $column = [
            'name' => 'cod_test',
            'type' => 'character varying(20)',
            'null' => 'NO',
            'default' => '',
        ];
        $constraint = [
            'name' => 'test_pkey',
            'constraint' => 'PRIMARY KEY (cod_test)',
        ];
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable('test_1', [$column], [$constraint]);
        $this->dataBase->exec($sql_query);

        // TABLA test_2
        $column = [
            'name' => 'cod_test',
            'type' => 'character varying(20)',
            'null' => 'NO',
            'default' => '',
        ];
        $constraints = [
            [
                'name' => 'ca_test2_test1',
                'constraint' => 'FOREIGN KEY (cod_test) REFERENCES test_1 (cod_test) ON DELETE CASCADE ON UPDATE CASCADE',
            ],
            [
                'name' => 'unique_test',
                'constraint' => 'UNIQUE (cod_test)',
            ],
        ];
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlCreateTable('test_2', [$column], $constraints);
        $this->dataBase->exec($sql_query);

        // Ejecutamos el metodo checkTable pasando una sola constraint para que
        // de esta forma elimine las dos constraints existentes en la base de datos
        // y agregue la nueva que le hemos pasado.
        $result = DatabaseUpdater::checkTable('test_2', [$column], [
            [
                'name' => 'test',
                'constraint' => 'UNIQUE',
            ],
        ]);

        // Esperamos obtener el sql necesario para eliminar las dos constraints y agregar la nueva.
        $expected = $this->dataBase->getEngine()->getSQL()->sqlDropConstraint('test_2', [
            'name' => 'ca_test2_test1',
            'type' => 'FOREIGN KEY',
        ]);
        $expected .= $this->dataBase->getEngine()->getSQL()->sqlDropConstraint('test_2', [
            'name' => 'unique_test',
            'type' => 'UNIQUE',
        ]);
        $expected .= $this->dataBase->getEngine()->getSQL()->sqlAddConstraint('test_2', 'test', 'UNIQUE');

        // Comprobamos que sean iguales.
        static::assertEquals($expected, $result);

        // Borramos las tablas.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable('test_1');
        $this->dataBase->exec($sql_query);
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable('test_2');
        $this->dataBase->exec($sql_query);
    }

    /**
     * Comprobamos que el metodo generateTable devuelva
     * el sql necesario para crear una tabla sin campos
     * y sin constraints.
     */
    public function testGenerateTableWithoutColumnsAndConstraints()
    {
        $response = DatabaseUpdater::generateTable($this->tableName, [], []);

        $expected = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, [], []);

        static::assertEquals($expected, $response);
    }

    /**
     * Comprobamos que el metodo generateTable devuelva
     * el sql necesario para crear una tabla con campos
     * pero sin constraints.
     */
    public function testGenerateTableWithColumns()
    {
        $response = DatabaseUpdater::generateTable($this->tableName, $this->xmlCols, []);

        $expected = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, []);

        static::assertEquals($expected, $response);
    }

    /**
     * Comprobamos que el metodo generateTable devuelva
     * el sql necesario para crear una tabla sin campos
     * pero si con constraints.
     */
    public function testGenerateTableWithConstraints()
    {
        $response = DatabaseUpdater::generateTable($this->tableName, [], $this->xmlCons);

        $expected = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, [], $this->xmlCons);

        static::assertEquals($expected, $response);
    }

    /**
     * Comprobamos que el metodo generateTable devuelva
     * el sql necesario para crear una tabla con campos
     * y constraints.
     */
    public function testGenerateTableWithColumnsAndConstraints()
    {
        $response = DatabaseUpdater::generateTable($this->tableName, $this->xmlCols, $this->xmlCons);

        $expected = $this->dataBase->getEngine()->getSQL()->sqlCreateTable($this->tableName, $this->xmlCols, $this->xmlCons);

        static::assertEquals($expected, $response);
    }

    /**
     * Comprobamos que el metodo getXmlTableLocation devuelva
     * el path del archivo xml que pertenece a la tabla
     * pasada por parametros.
     */
    public function testGetXmlTableLocation()
    {
        $result = DatabaseUpdater::getXmlTableLocation($this->tableName);
        // normalizamos el separador de los path segun el sistema operativo donde corre el test.
        $result = str_replace('/', DIRECTORY_SEPARATOR, $result);

        $expected = implode(DIRECTORY_SEPARATOR, [FS_FOLDER, 'Dinamic', 'Table', $this->tableName . '.xml']);
        if (FS_DEBUG) {
            $expected = implode(DIRECTORY_SEPARATOR, [FS_FOLDER, 'Core', 'Table', $this->tableName . '.xml']);
        }

        static::assertEquals($expected, $result);
    }

    /**
     * Comprobamos que el metodo getXmlTable devuelve
     * un array con las columnas y otro con las constraints
     * extraidos del archivo xml de la tabla pasada por parametros.
     */
    public function testGetXmlTable()
    {
        $columns = [];
        $constraints = [];
        $result = DatabaseUpdater::getXmlTable('roles', $columns, $constraints);

        static::assertEquals(true, $result);
        static::assertEquals([
            [
                'name' => 'codrole',
                'type' => 'character varying(20)',
                'null' => 'NO',
                'default' => '',
            ],
            [
                'name' => 'descripcion',
                'type' => 'character varying(200)',
                'null' => 'NO',
                'default' => '',
            ],
        ], $columns);
        static::assertEquals([
            [
                'name' => 'roles_pkey',
                'constraint' => 'PRIMARY KEY (codrole)',
            ],
        ], $constraints);
    }

    /**
     * Comprobamos que el metodo getXmlTable devuelve false
     * y crea un log critico cuando no se encuentra el
     * archivo xml que pertenece a la tabla pasada por parametros.
     */
    public function testGetXmlTableFileNotExist()
    {
        MiniLog::clear();

        $columns = [];
        $constraints = [];
        $result = DatabaseUpdater::getXmlTable('wrong_table_name', $columns, $constraints);

        static::assertEquals(false, $result);
        static::assertEquals(1, count(MiniLog::read('master', ['critical'])));
    }

    /**
     * Comprobamos que el metodo getXmlTable devuelve false
     * y crea un log critico cuando no se puede leer el
     * archivo xml que pertenece a la tabla pasada por parametros.
     */
    public function testGetXmlTableFileNotReadlable()
    {
        MiniLog::clear();

        $file_path = implode(DIRECTORY_SEPARATOR, [FS_FOLDER, 'Dinamic', 'Table', $this->tableName . '.xml']);
        touch($file_path);

        $columns = [];
        $constraints = [];
        $result = DatabaseUpdater::getXmlTable($this->tableName, $columns, $constraints);

        static::assertEquals(false, $result);
        static::assertEquals(1, count(MiniLog::read('master', ['critical'])));

        unlink($file_path);
    }

    /**
     * Comprobamos que el metodo getXmlTable devuelve false
     * y crea un log critico cuando no se pueden leer las columnas
     * del archivo xml que pertenece a la tabla pasada por parametros.
     */
    public function testGetXmlTableColumnsNotReadlables()
    {
        MiniLog::clear();

        $file_path = implode(DIRECTORY_SEPARATOR, [FS_FOLDER, 'Dinamic', 'Table', $this->tableName . '.xml']);
        file_put_contents($file_path, '<?xml version="1.0" encoding="UTF-8"?><table></table>');

        $columns = [];
        $constraints = [];
        $result = DatabaseUpdater::getXmlTable($this->tableName, $columns, $constraints);

        static::assertEquals(false, $result);

        unlink($file_path);
    }

    /**
     * Comprobamos que al instanciar un modelo se crea la tabla
     * correspondiente.
     */
    public function testCreateTableFromModelInstanced()
    {
        $file_path = DatabaseUpdater::CHECKED_TABLES_FILE_PATH;

        // Borramos las tablas checkeadas.
        DatabaseUpdater::removeCheckedTablesFile();

        // Borramos las tablas si existen.
        $sql_query = $this->dataBase->getEngine()->getSQL()->sqlDropTable('roles');
        $this->dataBase->exec($sql_query);

        // Instanciamos el modelo Role que tiene pocas columnas y así
        // podemos comparar mejor el string de las columnas en el test.
        $role = new Role();

        $resultColumns = $this->dataBase->getColumns($role::tableName());

        self::assertArrayHasKey('codrole', $resultColumns);
        self::assertArrayHasKey('descripcion', $resultColumns);

        self::assertFileExists($file_path);
        self::assertEquals('["roles"]', file_get_contents($file_path));

        // Volvemos a instanciar el modelo de nuevo para comprobar
        // que no se vuelve a checkear la tabla.
        new Role();
        self::assertEquals('["roles"]', file_get_contents($file_path));
    }

    /**
     * Comprobamos que al instanciar un modelo se crea la tabla
     * correspondiente.
     */
    public function testDeleteCheckedTablesFile()
    {
        $file_path = DatabaseUpdater::CHECKED_TABLES_FILE_PATH;

        // Creamos el archivo para el test
        if (!file_exists($file_path)) {
            file_put_contents($file_path, json_encode([]));
        }

        // Borramos el archivo json con las tablas checkeadas.
        self::assertTrue(DatabaseUpdater::removeCheckedTablesFile());
    }
}
