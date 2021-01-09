<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\Divisa;

/**
 * Description of CurrencyRelationTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait CurrencyRelationTrait
{

    /**
     * Currency of the document.
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var Divisa[]
     */
    private static $divisas;

    /**
     * Rate of conversion to Euros of the selected currency.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * 
     * @param string $coddivisa
     * @param bool   $purchase
     */
    public function setCurrency($coddivisa, $purchase = false)
    {
        if (empty(self::$divisas)) {
            $divisaModel = new Divisa();
            self::$divisas = $divisaModel->all([], [], 0, 0);
        }

        foreach (self::$divisas as $divisa) {
            if ($divisa->coddivisa === $coddivisa) {
                $this->coddivisa = $divisa->coddivisa;
                $this->tasaconv = $purchase ? $divisa->tasaconvcompra : $divisa->tasaconv;
                return;
            }
        }

        $this->coddivisa = $coddivisa;
        $this->tasaconv = 1.0;
    }
}
