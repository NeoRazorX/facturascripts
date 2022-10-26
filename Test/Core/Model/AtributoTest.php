<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Atributo;
use FacturaScripts\Core\Model\AtributoValor;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AtributoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');
        $this->assertNotNull($attribute->primaryColumnValue(), 'attribute-not-stored');
        $this->assertTrue($attribute->exists(), 'attribute-cant-persist');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testCreateWithNoCode()
    {
        $attribute = new Atributo();
        $attribute->nombre = 'Test Attribute with new code';
        $this->assertTrue($attribute->save(), 'attribute-cant-save');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testAttributeValues()
    {
        // creamos el atributo
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // creamos un valor
        $attributeValue = new AtributoValor();
        $attributeValue->codatributo = $attribute->codatributo;
        $attributeValue->valor = 'Value 1';
        $this->assertTrue($attributeValue->save(), 'attribute-value-cant-save');

        // eliminamos el atributo
        $this->assertTrue($attribute->delete(), 'attribute-value-cant-delete');

        // se debe haber eliminado el valor
        $this->assertFalse($attributeValue->exists(), 'attribute-value-still-persist');
    }

    public function testValueNoAttribute()
    {
        $attributeValue = new AtributoValor();
        $attributeValue->valor = 'Value 1';
        $this->assertFalse($attributeValue->save(), 'value-can-save-without-attribute');
    }

    private function getTestAttribute(): Atributo
    {
        $attribute = new Atributo();
        $attribute->codatributo = 'Test';
        $attribute->nombre = 'Test Atribute';
        return $attribute;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
