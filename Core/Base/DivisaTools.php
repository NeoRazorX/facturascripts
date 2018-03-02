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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Divisa;

/**
 * DivisaTools give us some basic and common methods for currency numbers.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DivisaTools
{

    /**
     *
     * @var Divisa[]
     */
    private static $divisas;

    /**
     *
     * @var Divisa
     */
    private static $selectedDivisa;

    /**
     * DivisaTools constructor.
     */
    public function __construct()
    {
        if (!defined('FS_CURRENCY_POS')) {
            define('FS_CURRENCY_POS', 'right');
        }

        if (!isset(self::$divisas)) {
            $divisa = new Divisa();
            self::$divisas = $divisa->all();

            $coddivisa = AppSettings::get('default', 'coddivisa');
            foreach (self::$divisas as $div) {
                if ($div->coddivisa == $coddivisa) {
                    self::$selectedDivisa = $div;
                    break;
                }
            }
        }
    }

    /**
     * Finds a coddivisa and uses it as selected currency.
     * 
     * @param mixed $model
     */
    public function findDivisa($model)
    {
        if (isset($model->coddivisa)) {
            foreach (self::$divisas as $div) {
                if ($div->coddivisa == $model->coddivisa) {
                    self::$selectedDivisa = $div;
                    break;
                }
            }
        }
    }

    /**
     * Returns the value of the formatted currency.
     *
     * @param float|string $number
     * @param int|string   $decimals
     * @param bool         $addSymbol
     *
     * @return string
     */
    public static function format($number, $decimals = FS_NF0, $addSymbol = true)
    {
        $txt = number_format((float) $number, (int) $decimals, FS_NF1, FS_NF2);
        if (!$addSymbol) {
            return $txt;
        }

        $symbol = self::$selectedDivisa->simbolo;
        if (FS_CURRENCY_POS === 'right') {
            return $txt . ' ' . $symbol;
        }

        return $symbol . ' ' . $txt;
    }

    /**
     * Return format mask for edit grid
     *
     * @param int $decimals
     * 
     * @return string
     */
    public static function gridMoneyFormat($decimals = FS_NF0)
    {
        $moneyFormat = '0.';
        for ($num = 0; $num < $decimals; $num++) {
            $moneyFormat .= '0';
        }
        return $moneyFormat;
    }
}
