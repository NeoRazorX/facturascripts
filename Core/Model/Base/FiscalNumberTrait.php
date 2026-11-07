<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\FiscalNumberValidator;

/**
 * Trait que añade el número de identificación fiscal (cifnif) junto con su
 * tipo (tipoidfiscal), y valida el identificador mediante FiscalNumberValidator,
 * que solo depende del tipo y no valida si el tipo tiene validar=false.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait FiscalNumberTrait
{
    /** @var string Número de identificación fiscal. */
    public $cifnif;

    /** @var string Tipo de identificación fiscal utilizado. */
    public $tipoidfiscal;

    /**
     * Sanea cifnif y tipoidfiscal y, si hay cifnif, lo valida; devuelve false si no es válido.
     *
     * @return bool
     */
    protected function testFiscalNumber(): bool
    {
        $this->cifnif = Tools::noHtml($this->cifnif);
        $this->tipoidfiscal = Tools::noHtml($this->tipoidfiscal);

        $validator = new FiscalNumberValidator();
        if (!empty($this->cifnif) && false === $validator->validate($this->tipoidfiscal, $this->cifnif)) {
            Tools::log()->warning('not-valid-fiscal-number', [
                '%type%' => $this->tipoidfiscal,
                '%number%' => $this->cifnif
            ]);
            return false;
        }

        return true;
    }
}
