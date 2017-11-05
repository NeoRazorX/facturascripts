<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Esta clase agrupa los datos y métodos de cálculo bancarios
 * para un uso genérico.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
trait BankAccount
{

    /**
     * Cuenta bancaria
     *
     * @var string
     */
    public $ccc;

    /**
     * Cuenta bancaria formato internacional
     *
     * @var string
     */
    public $iban;

    /**
     * Identificativo bancario internacional del banco y entidad
     *
     * @var string
     */
    public $swift;

    /**
     * Devuelve el CCC con o sin espacios.
     *
     * @param bool $espacios
     *
     * @return string
     */
    public function getCcc($espacios = false)
    {
        $ccc = str_replace(' ', '', $this->ccc);
        if ($espacios) {
            $ccc = substr($ccc, 0, 4) . ' '
                . substr($ccc, 4, 4) . ' '
                . substr($ccc, 8, 2) . ' '
                . substr($ccc, 10, 10);
        }

        return $ccc;
    }

    /**
     * Devuelve el IBAN con o sin espacios.
     *
     * @param bool $espacios
     *
     * @return string
     */
    public function getIban($espacios = false)
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
     * Inicializa los valores de los campos bancarios
     */
    private function clearBankAccount()
    {
        $this->ccc = null;
        $this->iban = null;
        $this->swift = null;
    }

    /**
     * Comprueba los datos bancarios informados
     *
     * @return boolean
     */
    public function testBankAccount()
    {
        $ibanOK = (empty($this->iban) || $this->verificarIBAN($this->iban));
        $cccOK = (empty($this->ccc) || $this->verificarCCC($this->ccc));

        return $ibanOK && $cccOK;
    }

    /**
     * Calcula el IBAN a partir de la cuenta bancaria
     *
     * @param string $ccc
     * @param string $codpais
     *
     * @return string
     */
    private function calcularIBAN($ccc, $codpais = '')
    {
        $pais = substr($codpais, 0, 2);
        $pesos = ['A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
            'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21', 'M' => '22',
            'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27', 'S' => '28', 'T' => '29',
            'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33', 'Y' => '34', 'Z' => '35',
        ];

        $dividendo = $ccc . $pesos[$pais[0]] . $pesos[$pais[1]] . '00';
        $digitoControl = 98 - bcmod($dividendo, '97');

        if (strlen($digitoControl) == 1) {
            $digitoControl = '0' . $digitoControl;
        }

        return $pais . $digitoControl . $ccc;
    }

    /**
     * Calcula el DC para la cadena en base 11 con los pesos indicados
     *
     * @param string $cadena
     * @param array  $pesos
     *
     * @return string
     */
    private function calcularDC($cadena, $pesos)
    {
        $totPeso = 0;
        for ($i = 0; $i < $len = strlen($cadena); ++$i) {
            $val = (int) $cadena[$i];
            $totPeso += ($pesos[$i] * $val);
        }

        $result = 11 - bcmod($totPeso, '11');
        switch (TRUE) {
            case $result == 11:
                $result = 0;
                break;

            case $result == 10:
                $result = 1;
                break;
        }

        return (string) $result;
    }

    /**
     * Calcula la cuenta bancaria para una entidad, banco y cuenta
     *
     * @param string $entidad
     * @param string $oficina
     * @param string $cuenta
     *
     * @return string
     */
    private function calcularCCC($entidad, $oficina, $cuenta)
    {
        $banco = $entidad . $oficina;
        if ((strlen($banco) != 8) || (strlen($cuenta) != 10)) {
            return '';
        }

        $dc1 = $this->calcularDC($banco, [4, 8, 5, 10, 9, 7, 3, 6]);
        $dc2 = $this->calcularDC($cuenta, [1, 2, 4, 8, 5, 10, 9, 7, 3, 6]);

        return $banco . $dc1 . $dc2 . $cuenta;
    }

    /**
     * Comprueba si los DC de una cuenta bancaria son correctos
     *
     * @param string $ccc
     *
     * @return boolean
     */
    public function verificarCCC($ccc)
    {
        if (strlen($ccc) != 20) {
            return false;
        }

        $entidad = substr($ccc, 0, 4);
        $oficina = substr($ccc, 4, 4);
        $cuenta = substr($ccc, 10, 10);

        return $ccc == $this->calcularCCC($entidad, $oficina, $cuenta);
    }

    /**
     * Comprueba si los DC de un IBAN son correctos
     *
     * @param string $iban
     *
     * @return boolean
     */
    public function verificarIBAN($iban)
    {
        if (strlen($iban) != 24) {
            return false;
        }

        $codpais = substr($iban, 0, 2);
        $ccc = substr($iban, -20);

        return $iban == $this->calcularIBAN($ccc, $codpais);
    }
}
