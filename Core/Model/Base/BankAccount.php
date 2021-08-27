<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use PHP_IBAN\IBAN;

/**
 * This class groups the data and bank calculation methods
 * for a generic use.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class BankAccount extends ModelClass
{

    const GROUP_LENGTH = 4;

    /**
     * Primary key. Varchar(10).
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Descriptive identification for humans.
     *
     * @var string
     */
    public $descripcion;

    /**
     * @var bool
     */
    private $disableIbanTest = false;

    /**
     * Bank account international format.
     *
     * @var string
     */
    public $iban;

    /**
     * International bank identification of the bank and entity.
     *
     * @var string
     */
    public $swift;

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
        $iban = str_replace(' ', '', $this->iban);

        /// split in groups
        $groups = [];
        for ($num = 0; $num < strlen($iban); $num += self::GROUP_LENGTH) {
            $groups[] = substr($iban, $num, self::GROUP_LENGTH);
        }

        /// censor
        if ($censure) {
            $groups[1] = $groups[2] = $groups[3] = $groups[4] = 'XXXX';
        }

        return $spaced ? implode(' ', $groups) : implode('', $groups);
    }

    /**
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codcuenta';
    }

    /**
     *
     * @param bool $value
     */
    public function setDisableIbanTest(bool $value)
    {
        $this->disableIbanTest = $value;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (!empty($this->codcuenta) && false === is_numeric($this->codcuenta)) {
            $this->toolBox()->i18nLog()->error('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        $utils = $this->toolBox()->utils();
        $this->descripcion = $utils->noHtml($this->descripcion);
        $this->iban = $utils->noHtml($this->iban);
        $this->swift = $utils->noHtml($this->swift);

        return parent::test() && $this->testBankAccount();
    }

    /**
     * Check if the DC's of an IBAN are correct.
     *
     * @param string $iban
     *
     * @return bool
     */
    public function verifyIBAN(string $iban): bool
    {
        if ($this->toolBox()->appSettings()->get('default', 'validate_iban', false)) {
            $object = new IBAN($iban);
            return $object->Verify();
        }

        return true;
    }

    /**
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codcuenta)) {
            $this->codcuenta = $this->newCode();
        }

        return parent::saveInsert($values);
    }

    /**
     * Check the reported bank details.
     *
     * @return bool
     */
    protected function testBankAccount(): bool
    {
        if (empty($this->iban) || $this->disableIbanTest || $this->verifyIBAN($this->getIban())) {
            return true;
        }

        $this->toolBox()->i18nLog()->warning('invalid-iban', ['%iban%' => $this->iban]);
        return false;
    }
}
