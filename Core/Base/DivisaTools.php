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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Dinamic\Model\Divisa;

/**
 * DivisaTools give us some basic and common methods for currency numbers.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DivisaTools extends NumberTools
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
        parent::__construct();

        if (false === \defined('FS_CURRENCY_POS')) {
            \define('FS_CURRENCY_POS', 'right');
        }

        if (!isset(self::$divisas)) {
            $divisa = new Divisa();
            self::$divisas = $divisa->all();

            $coddivisa = AppSettings::get('default', 'coddivisa');
            self::$selectedDivisa = static::get($coddivisa);
        }
    }

    /**
     * Convert the amount form currency1 to currency2.
     *
     * @param float  $amount
     * @param string $coddivisa1
     * @param string $coddivisa2
     *
     * @return float
     */
    public static function convert($amount, $coddivisa1, $coddivisa2)
    {
        if ($coddivisa1 != $coddivisa2) {
            return (float) $amount / static::get($coddivisa1)->tasaconv * static::get($coddivisa2)->tasaconv;
        }

        return (float) $amount;
    }

    /**
     * Finds a coddivisa and uses it as selected currency.
     * 
     * @param object $model
     */
    public function findDivisa($model)
    {
        if (isset($model->coddivisa)) {
            self::$selectedDivisa = static::get($model->coddivisa);
        }
    }

    /**
     * Returns the value of the formatted currency.
     *
     * @param mixed  $number
     * @param mixed  $decimals
     * @param string $decoration
     *
     * @return string
     */
    public static function format($number, $decimals = FS_NF0, $decoration = 'symbol')
    {
        $txt = parent::format($number, $decimals);
        switch ($decoration) {
            case 'symbol':
                return FS_CURRENCY_POS === 'right' ? $txt . ' ' . static::getSymbol() : static::getSymbol() . ' ' . $txt;

            case 'coddivisa':
                return isset(self::$selectedDivisa) ? $txt . ' ' . self::$selectedDivisa->coddivisa : $txt . ' ???';

            default:
                return $txt;
        }
    }

    /**
     * 
     * @return string
     */
    public static function getSymbol()
    {
        return isset(self::$selectedDivisa) ? (string) self::$selectedDivisa->simbolo : '?';
    }

    /**
     * 
     * @param string $coddivisa
     *
     * @return Divisa
     */
    private static function get($coddivisa)
    {
        foreach (self::$divisas as $div) {
            if ($div->coddivisa == $coddivisa) {
                return $div;
            }
        }

        return new Divisa();
    }
}
