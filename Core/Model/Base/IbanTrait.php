<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

trait IbanTrait
{
    /** @var bool */
    private $disable_iban_test = false;

    /** @var string */
    public $iban;

    /**
     * Returns the IBAN with or without spaces.
     *
     * @param bool $spaced
     * @param bool $censure
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

    public function setDisableIbanTest(bool $value): void
    {
        $this->disable_iban_test = $value;
    }

    public function verifyIBAN(string $iban): bool
    {
        if (Tools::settings('default', 'validate_iban', false)) {
            $object = new IBAN($iban);
            return $object->Verify();
        }

        return true;
    }

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
