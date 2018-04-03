<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\Utils;

/**
 * This class groups the data and bank calculation methods
 * for a generic use.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class BankAccount extends ModelClass
{

    /**
     * Primary key. Varchar(10).
     *
     * @var int
     */
    public $codcuenta;

    /**
     * Descriptive identification for humans.
     *
     * @var string
     */
    public $descripcion;

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
     * @param bool $espacios
     *
     * @return string
     */
    public function getIban(bool $espacios = false)
    {
        $iban = str_replace(' ', '', $this->iban);
        if ($espacios) {
            $txt = '';
            for ($i = 0; $i < $len = strlen($iban); $i += 4) {
                $txt .= substr($iban, $i, 4) . ' ';
            }

            return $txt;
        }

        return $iban;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        parent::test();
        $this->descripcion = Utils::noHtml($this->descripcion);

        if (!$this->testBankAccount()) {
            self::$miniLog->alert(self::$i18n->trans('error-incorrect-bank-details'));

            return false;
        }

        return true;
    }

    /**
     * Check if the DC's of an IBAN are correct.
     *
     * @param string $iban
     *
     * @return boolean
     */
    public function verificarIBAN(string $iban)
    {
        if (strlen($iban) != 24) {
            return false;
        }

        $codpais = substr($iban, 0, 2);
        $ccc = substr($iban, -20);

        return $iban == $this->calcularIBAN($ccc, $codpais);
    }

    /**
     * Calculate the IBAN from the bank account.
     *
     * @param string $ccc
     * @param string $codpais
     *
     * @return string
     */
    private function calcularIBAN(string $ccc, string $codpais = '')
    {
        $pais = substr($codpais, 0, 2);
        $pesos = ['A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35',
        ];

        $dividendo = $ccc . $pesos[$pais[0]] . $pesos[$pais[1]] . '00';
        $digitoControl = 98 - \bcmod($dividendo, '97');

        if (strlen($digitoControl) === 1) {
            $digitoControl = '0' . $digitoControl;
        }

        return $pais . $digitoControl . $ccc;
    }

    /**
     * Check the reported bank details.
     *
     * @return boolean
     */
    protected function testBankAccount()
    {
        return (empty($this->iban) || $this->verificarIBAN($this->iban));
    }
}
