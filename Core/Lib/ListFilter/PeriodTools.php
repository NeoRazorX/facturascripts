<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Translator;

/**
 * PeriodTools give us some basic and common methods for periods.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class PeriodTools
{
    const DATE_FORMAT = 'd-m-Y';

    public static function applyFormatToDate(string $format, string $dateFormat = self::DATE_FORMAT, string $date = ''): string
    {
        $time = empty($date) ? time() : strtotime($date);
        return date($dateFormat, strtotime($format, $time));
    }

    public static function applyFormatToPeriod(string &$startDate, string &$endDate, string $startFormat, string $endFormat, string $dateFormat = self::DATE_FORMAT)
    {
        $startDate = static::applyFormatToDate($startFormat, $dateFormat);
        $endDate = static::applyFormatToDate($endFormat, $dateFormat);
    }

    /**
     * Applies on the start and end date indicated the relative format corresponding to the period indicated
     * starting from the current date
     *
     * @param string $period
     * @param string $startDate
     * @param string $endDate
     */
    public static function applyPeriod(string $period, string &$startDate, string &$endDate)
    {
        switch ($period) {
            case 'today':
                static::applyFormatToPeriod($startDate, $endDate, 'today', 'today');
                break;

            case 'yesterday':
                static::applyFormatToPeriod($startDate, $endDate, 'yesterday', 'yesterday');
                break;

            case 'this-week':
                static::applyFormatToPeriod($startDate, $endDate, 'mon this week', 'sun this week');
                break;

            case 'this-last-week':
                static::applyFormatToPeriod($startDate, $endDate, '-6 day', 'today');
                break;

            case 'last-week':
                static::applyFormatToPeriod($startDate, $endDate, 'mon last week', 'sun last week');
                break;

            case 'this-last-fortnight':
                static::applyFormatToPeriod($startDate, $endDate, '-14 day', 'today');
                break;

            case 'this-month':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of', 'last day of');
                break;

            case 'this-last-month':
                static::applyFormatToPeriod($startDate, $endDate, '-1 month', 'today');
                break;

            case 'last-month':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of last month', 'last day of last month');
                break;

            case 'first-quarter':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of January', 'last day of March');
                break;

            case 'second-quarter':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of April', 'last day of June');
                break;

            case 'third-quarter':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of July', 'last day of September');
                break;

            case 'fourth-quarter':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of October', 'last day of December');
                break;

            case 'previous-quarter':
                $trimestre = ceil(date('n', strtotime("-3 month")) / 3);
                $startDate = "01-" . str_pad((($trimestre - 1) * 3) + 1, 2, "0", STR_PAD_LEFT) . "-" . date('Y', strtotime("-3 month"));
                $endDate = date("t-m-Y", strtotime("01" . "-" . str_pad((($trimestre - 1) * 3) + 3, 2, "0", STR_PAD_LEFT) . "-" . date('Y', strtotime("-3 month"))));
                break;

            case 'current-quarter':
                $trimestre = ceil(date('n') / 3);
                $startDate = "01-" . str_pad((($trimestre - 1) * 3) + 1, 2, "0", STR_PAD_LEFT) . "-" . date('Y');
                $endDate = date("t-m-Y", strtotime("01" . "-" . str_pad((($trimestre - 1) * 3) + 3, 2, "0", STR_PAD_LEFT) . "-" . date('Y')));
                break;

            case 'this-year':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of january', 'last day of December');
                break;

            case 'this-last-year':
                static::applyFormatToPeriod($startDate, $endDate, '-1 year', 'today');
                break;

            case 'last-year':
                static::applyFormatToPeriod($startDate, $endDate, 'first day of january last year', 'last day of December last year');
                break;
        }
    }

    /**
     * Return list of periods for select base filter
     *
     * @param Translator $i18n
     *
     * @return array
     */
    public static function getFilterOptions(Translator $i18n): array
    {
        $result = [
            ['code' => '', 'description' => '------']
        ];
        foreach (static::getPeriods() as $value) {
            $result[] = empty($value) ?
                ['code' => '', 'description' => '------'] :
                ['code' => $value, 'description' => $i18n->trans($value)];
        }
        return $result;
    }

    /**
     * Return list of available periods
     *
     * @return array
     */
    protected static function getPeriods(): array
    {
        return [
            'today',
            'yesterday',
            'this-week',
            'this-last-week',
            'last-week',
            'this-last-fortnight',
            'this-month',
            'this-last-month',
            'last-month',
            '',
            'previous-quarter',
            'current-quarter',
            'first-quarter',
            'second-quarter',
            'third-quarter',
            'fourth-quarter',
            '',
            'this-year',
            'this-last-year',
            'last-year'
        ];
    }
}
