<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Divisas;

/**
 * Trait que añade el código de divisa (coddivisa) y su tasa de conversión
 * (tasaconv), y el método setCurrency() que copia dicha tasa desde la divisa.
 * No interviene al guardar el registro.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait CurrencyRelationTrait
{
    /**
     * Código de la divisa utilizada en el documento.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Tasa de conversión de la divisa seleccionada, copiada por setCurrency().
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Asigna la divisa y copia su tasa de conversión en el momento de la llamada:
     * tasaconvcompra si $purchase es true (y la divisa la tiene), si no tasaconv.
     * Si la divisa no existe, asigna el código indicado con tasa 1.0.
     * Se invoca desde clear() de PurchaseDocument y SalesDocument.
     *
     * @param string $coddivisa Código de la divisa.
     * @param bool $purchase Indica si se usa la tasa de compra.
     */
    public function setCurrency($coddivisa, $purchase = false): void
    {
        foreach (Divisas::all() as $divisa) {
            if ($divisa->coddivisa === $coddivisa) {
                $this->coddivisa = $divisa->coddivisa;
                $this->tasaconv = $purchase && $divisa->tasaconvcompra !== null ? $divisa->tasaconvcompra : $divisa->tasaconv;
                return;
            }
        }

        $this->coddivisa = $coddivisa;
        $this->tasaconv = 1.0;
    }
}
