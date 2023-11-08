<?php

namespace FacturaScripts\Test\Core\Base\Template;

use FacturaScripts\Core\Template\Model;
use FacturaScripts\Core\Where;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{

    /**
     * Comprobamos el método where dinámico
     */
    public function testDynamicWhere()
    {
        $model = new class extends Model {
            public const TABLE_NAME = 'ciudades';
        };

        $result = $model::table()->where([Where::column('ciudad', 'Amurrio')])->get();
        static::assertEquals(2, $result[0]['idciudad']);

        $result = $model::table()->whereCiudad('Amurrio')->get();
        static::assertEquals(2, $result[0]['idciudad']);

        $result = $model::table()->whereIdprovincia(1)->get();
        static::assertCount(54, $result);
    }
}
