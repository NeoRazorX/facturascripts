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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\DocTransformation;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba los índices de la tabla doctransformations, que se consulta por
 * documento de origen (model1, iddoc1) y de destino (model2, iddoc2).
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
final class DocTransformationTest extends TestCase
{
    use LogErrorsTrait;

    /** @var array Índices esperados: nombre en la base de datos => columnas. */
    const INDEXES = [
        'fs_doctransformations_doc1' => ['model1', 'iddoc1'],
        'fs_doctransformations_doc2' => ['model2', 'iddoc2'],
    ];

    public function testTableHasDocumentIndexes(): void
    {
        // al instanciar el modelo se revisa la tabla y se crean los índices
        new DocTransformation();

        // agrupamos las columnas de cada índice
        $found = [];
        foreach ((new DataBase())->getIndexes(DocTransformation::tableName()) as $index) {
            $found[$index['name']][] = $index['column'];
        }

        foreach (self::INDEXES as $name => $columns) {
            $this->assertArrayHasKey($name, $found, 'Falta el índice ' . $name);
            $this->assertEqualsCanonicalizing($columns, $found[$name], 'Columnas incorrectas en ' . $name);
        }
    }

    public function testDocumentQueriesUseIndexes(): void
    {
        new DocTransformation();
        $db = new DataBase();
        $postgresql = $db->type() === 'postgresql';

        // en PostgreSQL, con pocas filas, el planificador prefiere recorrer la tabla:
        // desactivamos el recorrido secuencial para comprobar que el índice es utilizable
        if ($postgresql) {
            $db->exec('SET enable_seqscan = off;');
        }

        foreach (self::INDEXES as $name => [$modelColumn, $idColumn]) {
            $sql = 'EXPLAIN SELECT * FROM ' . DocTransformation::tableName()
                . ' WHERE ' . $modelColumn . ' = ' . $db->var2str('AlbaranCliente')
                . ' AND ' . $idColumn . ' = 1;';
            $plan = json_encode($db->select($sql));
            $this->assertStringContainsString($name, $plan, 'La consulta por ' . $modelColumn . ', ' . $idColumn . ' no usa el índice: ' . $plan);
        }

        if ($postgresql) {
            $db->exec('SET enable_seqscan = on;');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
