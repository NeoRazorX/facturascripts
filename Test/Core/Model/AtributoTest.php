<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Worker\AtributoWorker;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AtributoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un atributo
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // comprobamos que se ha guardado
        $this->assertNotNull($attribute->id(), 'attribute-not-stored');
        $this->assertTrue($attribute->exists(), 'attribute-cant-persist');

        // eliminamos
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testCreateWithNoCode(): void
    {
        $attribute = new Atributo();
        $attribute->nombre = 'Test Attribute with new code';
        $this->assertTrue($attribute->save(), 'attribute-cant-save');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testAttributeValues(): void
    {
        // creamos el atributo
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // creamos un valor
        $attValue = new AtributoValor();
        $attValue->codatributo = $attribute->codatributo;
        $attValue->valor = 'Value 1';
        $this->assertTrue($attValue->save(), 'attribute-value-cant-save');

        // creamos otro valor, pero con getNewValue
        $attValue2 = $attribute->getNewValue('Value 2');
        $this->assertTrue($attValue2->save(), 'attribute-value-cant-save');

        // comprobamos que el atributo tiene los valores
        $this->assertCount(2, $attribute->getValues(), 'attribute-values-not');
        $this->assertTrue($attribute->hasValue('Value 1'), 'attribute-value-not-found');
        $this->assertTrue($attribute->hasValue('Value 2'), 'attribute-value-not-found');

        // eliminamos el atributo
        $this->assertTrue($attribute->delete(), 'attribute-value-cant-delete');

        // se deben haber eliminado los valores
        $this->assertFalse($attValue->exists(), 'attribute-value-still-persist');
        $this->assertFalse($attValue2->exists(), 'attribute-value-still-persist');
    }

    public function testAddValues(): void
    {
        // creamos el atributo
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // añadimos un valor
        $this->assertTrue($attribute->addValue('Value 1'), 'attribute-value-cant-add');

        // comprobamos que el atributo tiene el valor
        $this->assertCount(1, $attribute->getValues(), 'attribute-values-not');
        $this->assertTrue($attribute->hasValue('Value 1'), 'attribute-value-not-found');

        // añadimos otro valor
        $this->assertTrue($attribute->addValue('Value 2'), 'attribute-value-cant-add');
        $this->assertCount(2, $attribute->getValues(), 'attribute-values-not');
        $this->assertTrue($attribute->hasValue('Value 2'), 'attribute-value-not-found');
        $this->assertTrue($attribute->hasValue('Value 1'), 'attribute-value-not-found');

        // eliminamos el primer valor
        $this->assertTrue($attribute->removeValue('Value 1'), 'attribute-value-cant-remove');
        $this->assertCount(1, $attribute->getValues(), 'attribute-values-not');
        $this->assertFalse($attribute->hasValue('Value 1'), 'attribute-value-still-found');
        $this->assertTrue($attribute->hasValue('Value 2'), 'attribute-value-not-found');

        // eliminamos
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testValueNoAttribute(): void
    {
        $attributeValue = new AtributoValor();
        $attributeValue->valor = 'Value 1';
        $this->assertFalse($attributeValue->save(), 'value-can-save-without-attribute');
    }

    public function testRenameUpdatesValueDescriptions(): void
    {
        // creamos el atributo
        $attribute = $this->getTestAttribute();
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // añadimos 2 valores
        $value1 = $attribute->getNewValue('Value 1');
        $this->assertTrue($value1->save(), 'attribute-value-cant-save');

        $value2 = $attribute->getNewValue('Value 2');
        $this->assertTrue($value2->save(), 'attribute-value-cant-save');

        // la descripción combina el nombre del atributo con el valor
        $this->assertEquals('Test Attribute Value 1', $value1->descripcion, 'bad-attribute-value-description');
        $this->assertEquals('Test Attribute Value 2', $value2->descripcion, 'bad-attribute-value-description');

        // renombramos el atributo
        $attribute->nombre = 'Renamed Attribute';
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        // como la descripción está desnormalizada, en la base de datos sigue la anterior
        $stored = new AtributoValor();
        $this->assertTrue($stored->load($value1->id), 'attribute-value-not-found');
        $this->assertEquals('Test Attribute Value 1', $stored->descripcion, 'attribute-value-description-changed');

        // ejecutamos el worker que las regenera
        $event = new WorkEvent();
        $event->name = 'Model.Atributo.Update';
        $event->value = $attribute->codatributo;
        $event->setParams($attribute->toArray());

        $worker = new AtributoWorker();
        $this->assertTrue($worker->run($event), 'atributo-worker-fail');

        // ahora las descripciones están actualizadas
        $this->assertTrue($stored->load($value1->id), 'attribute-value-not-found');
        $this->assertEquals('Renamed Attribute Value 1', $stored->descripcion, 'attribute-value-description-not-updated');

        $this->assertTrue($stored->load($value2->id), 'attribute-value-not-found');
        $this->assertEquals('Renamed Attribute Value 2', $stored->descripcion, 'attribute-value-description-not-updated');

        // renombramos con update(), que solo manda en los params los campos modificados
        $this->assertTrue($attribute->update(['nombre' => 'Updated Attribute']), 'attribute-cant-update');

        $event2 = new WorkEvent();
        $event2->name = 'Model.Atributo.Update';
        $event2->value = $attribute->codatributo;
        $event2->setParams(['nombre' => 'Updated Attribute']);

        $this->assertTrue($worker->run($event2), 'atributo-worker-fail');

        $this->assertTrue($stored->load($value1->id), 'attribute-value-not-found');
        $this->assertEquals('Updated Attribute Value 1', $stored->descripcion, 'attribute-value-description-not-updated');

        // eliminamos
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    private function getTestAttribute(): Atributo
    {
        $attribute = new Atributo();
        $attribute->codatributo = 'Test';
        $attribute->nombre = 'Test Attribute';
        return $attribute;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
