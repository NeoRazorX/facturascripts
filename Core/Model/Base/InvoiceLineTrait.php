<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\DocTransformation;

trait InvoiceLineTrait
{
    abstract public function hasRefundedQuantity(): bool;

    /**
     * Indica si se puede rectificar esa cantidad de la línea: debe tener el mismo signo
     * que la línea y no superar la cantidad pendiente de rectificar.
     */
    public function canRefundQuantity(float $quantity): bool
    {
        $pending = $this->refundableQuantity();
        if ($quantity * $pending <= 0) {
            return false;
        }

        return abs($quantity) <= abs($pending) || Tools::floatCmp(abs($quantity), abs($pending), 6, true);
    }

    /**
     * Devuelve la cantidad de la línea que aún se puede rectificar, con el signo de la línea.
     */
    public function refundableQuantity(): float
    {
        $pending = abs((float)$this->cantidad) - $this->refundedQuantity();
        if ($pending <= 0 || Tools::floatCmp($pending, 0.0, 6, true)) {
            return 0.0;
        }

        return $this->cantidad < 0 ? -$pending : $pending;
    }

    public function refundedQuantity(): float
    {
        // si no hay una rectificativa, devolvemos 0
        if (false === $this->hasRefundedQuantity()) {
            return 0.0;
        }

        // comprobamos líneas de facturas rectificativas
        $quantity = 0.0;
        foreach (self::allWhereEq('idlinearect', $this->idlinea) as $line) {
            $quantity += abs($line->cantidad);
        }
        if ($quantity) {
            return $quantity;
        }

        // comprobamos líneas relacionadas
        $whereTrans = [
            Where::eq('model1', $this->getDocument()->modelClassName()),
            Where::eq('iddoc1', $this->idfactura),
            Where::eq('idlinea1', $this->idlinea)
        ];
        foreach (DocTransformation::all($whereTrans, [], 0, 0) as $docTrans) {
            $quantity += abs($docTrans->cantidad);
        }

        return $quantity;
    }

    /**
     * Si es una línea de rectificativa nueva o con la cantidad cambiada, comprueba que no
     * rectifique más de lo pendiente de la línea original, sin contar esta misma línea.
     */
    protected function testRefundQuantity(): bool
    {
        if (empty($this->idlinearect) || empty($this->cantidad)) {
            return true;
        }

        // solo comprobamos las líneas nuevas o las que cambian de cantidad o de línea original
        if (false === $this->isDirty('cantidad') && false === $this->isDirty('idlinearect')) {
            return true;
        }

        $original = new static();
        if (false === $original->load($this->idlinearect)) {
            return true;
        }

        $refunded = 0.0;
        foreach (self::allWhereEq('idlinearect', $this->idlinearect) as $line) {
            if ((int)$line->idlinea !== (int)$this->idlinea) {
                $refunded += abs($line->cantidad);
            }
        }

        // la línea de la rectificativa lleva el signo contrario a la original
        $quantity = 0 - (float)$this->cantidad;
        $pending = abs((float)$original->cantidad) - $refunded;
        $fits = abs($quantity) <= $pending || Tools::floatCmp(abs($quantity), $pending, 6, true);
        if ($quantity * $original->cantidad > 0 && $fits) {
            return true;
        }

        $pending = max($pending, 0.0);
        Tools::log()->warning('invalid-refund-quantity', [
            '%line%' => $this->referencia ?: Tools::textBreak($this->descripcion),
            '%quantity%' => Tools::number($original->cantidad < 0 ? -$pending : $pending),
        ]);
        return false;
    }
}
