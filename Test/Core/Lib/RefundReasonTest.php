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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\RefundReason;
use PHPUnit\Framework\TestCase;

/**
 * Motivos de rectificación de las facturas.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
final class RefundReasonTest extends TestCase
{
    public function testAllReturnsDefaults(): void
    {
        $all = RefundReason::all();

        $this->assertArrayHasKey(RefundReason::ES_REFUND_REASON_80_1_2_6, $all);
        $this->assertArrayHasKey(RefundReason::ES_REFUND_REASON_80_3, $all);
        $this->assertArrayHasKey(RefundReason::ES_REFUND_REASON_80_4, $all);
        $this->assertArrayHasKey(RefundReason::ES_REFUND_REASON_OTHER, $all);
        $this->assertCount(4, $all);

        // las claves caben en la columna motivorect
        foreach (array_keys($all) as $key) {
            $this->assertLessThanOrEqual(RefundReason::MAX_KEY_LENGTH, strlen($key), $key);
        }
    }

    public function testAddAndRemove(): void
    {
        RefundReason::add('custom-reason', 'custom-reason-label');
        $this->assertEquals('custom-reason-label', RefundReason::get('custom-reason'));

        // limpiamos
        RefundReason::remove('custom-reason');
        $this->assertNull(RefundReason::get('custom-reason'));
    }

    public function testAddTruncatesLongKeys(): void
    {
        RefundReason::add('a-very-long-refund-reason-key', 'long-label');
        $this->assertEquals('long-label', RefundReason::get('a-very-long-refund-r'));

        // limpiamos
        RefundReason::remove('a-very-long-refund-reason-key');
        $this->assertNull(RefundReason::get('a-very-long-refund-r'));
    }

    public function testGet(): void
    {
        $this->assertEquals('es-refund-reason-80-4', RefundReason::get(RefundReason::ES_REFUND_REASON_80_4));
        $this->assertNull(RefundReason::get('unknown'));
        $this->assertNull(RefundReason::get(null));
    }

    public function testRemoveDefaultAndReAdd(): void
    {
        RefundReason::remove(RefundReason::ES_REFUND_REASON_OTHER);
        $this->assertNull(RefundReason::get(RefundReason::ES_REFUND_REASON_OTHER));

        // restauramos
        RefundReason::add(RefundReason::ES_REFUND_REASON_OTHER, 'es-refund-reason-other');
        $this->assertCount(4, RefundReason::all());
    }
}
