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
use PHP_IBAN\IBAN;

/**
 * Trait que añade el campo iban con el código IBAN de una cuenta bancaria,
 * junto con métodos para formatearlo (con o sin espacios, censurado) y
 * validarlo. La validación solo se aplica si el ajuste default/validate_iban
 * está activado, y puede desactivarse con setDisableIbanTest().
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait IbanTrait
{
    /** @var bool Indica si se debe omitir la validación del código IBAN. */
    private $disable_iban_test = false;

    /** @var string Código IBAN de la cuenta bancaria. */
    public $iban;

    /**
     * Devuelve el IBAN, con o sin espacios (grupos de 4) y opcionalmente censurado.
     *
     * @param bool $spaced Indica si se separan los grupos con espacios.
     * @param bool $censure Indica si se sustituyen los grupos 2 a 5 por XXXX.
     *
     * @return string
     */
    public function getIban(bool $spaced = false, bool $censure = false): string
    {
        if (empty($this->iban)) {
            return '';
        }

        $iban = str_replace(' ', '', $this->iban);
        $group_length = 4;

        // split in groups
        $groups = [];
        for ($num = 0; $num < strlen($iban); $num += $group_length) {
            $groups[] = substr($iban, $num, $group_length);
        }

        // censor
        if ($censure) {
            $groups[1] = $groups[2] = $groups[3] = $groups[4] = 'XXXX';
        }

        return $spaced ? implode(' ', $groups) : implode('', $groups);
    }

    /**
     * Activa o desactiva la omisión de la validación del IBAN al guardar.
     *
     * @param bool $value
     */
    public function setDisableIbanTest(bool $value): void
    {
        $this->disable_iban_test = $value;
    }

    /**
     * Verifica el IBAN indicado; si el ajuste default/validate_iban está desactivado, devuelve siempre true.
     *
     * @param string $iban
     * @return bool
     */
    public function verifyIBAN(string $iban): bool
    {
        if (Tools::settings('default', 'validate_iban', false)) {
            $object = new IBAN($iban);
            return $object->Verify();
        }

        return true;
    }

    /**
     * Sanea el IBAN y lo valida salvo que esté vacío o la validación esté desactivada.
     *
     * @return bool
     */
    protected function testIBAN(): bool
    {
        $this->iban = Tools::noHtml($this->iban);

        if (empty($this->iban) || $this->disable_iban_test || $this->verifyIBAN($this->getIban())) {
            return true;
        }

        Tools::log()->warning('invalid-iban', ['%iban%' => $this->iban]);
        return false;
    }
}
