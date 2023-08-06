<?php

namespace Base\DataBase;

use FacturaScripts\Core\Base\DataBase\MysqlQueries;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class MysqlQueriesTest extends TestCase
{
    use LogErrorsTrait;

    public function testSqlTableConstraints()
    {
        $mysqlQueries = new MysqlQueries();

        $xmlCons = [
            [
                'name' => 'ca_anticipos_users',
                'constraint' => 'FOREIGN KEY ("user") REFERENCES users (nick) ON DELETE RESTRICT ON UPDATE CASCADE'
            ]
        ];

        $result = $mysqlQueries->sqlTableConstraints($xmlCons);

        $expected = ', CONSTRAINT ca_anticipos_users FOREIGN KEY (`user`) REFERENCES users (nick) ON DELETE RESTRICT ON UPDATE CASCADE';
        $this->assertEquals($expected, $result);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
