<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * This class groups the data and bank calculation methods
 * for a generic use.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raúl Jiménez          <raljopa@gmail.com>
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
     *
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
     * Message error in checked IBAN
     */
    private $errorMessage;

    /**
     * Returns the IBAN with or without spaces.
     *
     * @param bool $spaced
     *
     * @return string
     */
    public function getIban(bool $spaced = false)
    {
        $iban = \str_replace(' ', '', $this->iban);
        $groups = [];
        for ($num = 0; $num < \strlen($iban); $num += self::GROUP_LENGTH) {
            $groups[] = \substr($iban, $num, self::GROUP_LENGTH);
        }

        return $spaced ? \implode(' ', $groups) : $iban;
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
    public function setDisableIbanTest($value)
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
        if (!empty($this->codcuenta) && false === \is_numeric($this->codcuenta)) {
            $this->toolBox()->i18nLog()->error('invalid-number');
            return false;
        }

        $utils = $this->toolBox()->utils();
        $this->descripcion = $utils->noHtml($this->descripcion);
        $this->iban = $utils->noHtml($this->iban);
        $this->swift = $utils->noHtml($this->swift);

        return parent::test() && $this->testBankAccount();
    }

    /**
     * Check if a IBAN is correct.
     *
     * @param string $iban
     *
     * @return bool
     */
    protected function verifyIBAN($iban)
    {
        $countryCode = \strtoupper(\substr($iban, 0, 2));
        $countryConfig = $this->getCountryConfig($countryCode);
        $country = new \FacturaScripts\Dinamic\Model\Pais();
        $country = $country->all([new DatabaseWhere('codiso', $countryCode)])[0];
        if ($countryConfig['length'] == 0) {
            return true;
        }
        if (\strlen($iban) != $countryConfig['length']) {

            $this->errorMessage = 'bad-length ' . $country->nombre;
            return false;
        }
        /* Check format */
        $regex = '/' . $countryConfig['IBANRegexFormat'] . '/';
        if (!preg_match($regex, $iban)) {

            $this->errorMessage = 'bad-format ' . $country->nombre;
            return false;
        }
        $ccc = substr($iban, 4) . substr($iban, 0, 4);
        $this->errorMessage = 'bad-control-digit ' . $country->nombre;

        $ibanCharacters = range('A', 'Z');
        foreach (range(10, 35) as $tempvalue) {
            $ibanValues[] = strval($tempvalue);
        }

        $numericCcc = str_replace($ibanCharacters, $ibanValues, $ccc);

        return (\bcmod($numericCcc, '97') == 1);
    }

    private function getCountryConfig($countryCode)
    {
        $countryData = [
            'DK' => ['length' => '18', 'IBANFormat' => 'DK2!n4!n9!n1!n', 'IBANRegexFormat' => '^DK(\d{2})(\d{4})(\d{9})(\d{1})$'],
            'BE' => ['length' => '16', 'IBANFormat' => 'BE2!n3!n7!n2!n', 'IBANRegexFormat' => '^BE(\d{2})(\d{3})(\d{7})(\d{2})$'],
            'DE' => ['length' => '22', 'IBANFormat' => 'DE2!n8!n10!n', 'IBANRegexFormat' => '^DE(\d{2})(\d{8})(\d{10})$'],
            'ES' => ['length' => '24', 'IBANFormat' => 'ES2!n4!n4!n1!n1!n10!n', 'IBANRegexFormat' => '^ES(\d{2})(\d{4})(\d{4})(\d{1})(\d{1})(\d{10})$'],
            'FI' => ['length' => '18', 'IBANFormat' => 'FI2!n6!n7!n1!n', 'IBANRegexFormat' => '^FI(\d{2})(\d{6})(\d{7})(\d{1})$'],
            'FR' => ['length' => '27', 'IBANFormat' => 'FR2!n5!n5!n11!c2!n', 'IBANRegexFormat' => '^FR(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$'],
            'GB' => ['length' => '22', 'IBANFormat' => 'GB2!n4!a6!n8!n', 'IBANRegexFormat' => '^GB(\d{2})([A-Z]{4})(\d{6})(\d{8})$'],
            'GR' => ['length' => '27', 'IBANFormat' => 'GR2!n3!n4!n16!c', 'IBANRegexFormat' => '^GR(\d{2})(\d{3})(\d{4})([A-Za-z0-9]{16})$'],
            'IE' => ['length' => '22', 'IBANFormat' => 'IE2!n4!a6!n8!n', 'IBANRegexFormat' => '^IE(\d{2})([A-Z]{4})(\d{6})(\d{8})$'],
            'IT' => ['length' => '27', 'IBANFormat' => 'IT2!n1!a5!n5!n12!c', 'IBANRegexFormat' => '^IT(\d{2})([A-Z]{1})(\d{5})(\d{5})([A-Za-z0-9]{12})$'],
            'PT' => ['length' => '25', 'IBANFormat' => 'PT2!n4!n4!n11!n2!n', 'IBANRegexFormat' => '^PT(\d{2})(\d{4})(\d{4})(\d{11})(\d{2})$'],
        ];
        if (array_key_exists($countryCode, $countryData)) {
            return $countryData[$countryCode];
        }
        return ['length' => '0', 'IBANFormat' => '', 'IBANRegexFormat' => ''];
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
    protected function testBankAccount()
    {
        if (empty($this->iban) || $this->disableIbanTest || $this->verifyIBAN($this->getIban())) {
            return true;
        }

        $this->toolBox()->i18nLog()->error('invalid-iban', ['%iban%' => $this->iban]);
        $this->toolBox()->i18nLog()->error($this->errorMessage);
        return false;
    }
}
