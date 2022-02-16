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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\ToolBox;

/**
 * Class to apply patterns.
 *
 * @author Jose Antonio Cuello <yopli2000@gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CodePatterns
{
    const DATE_STYLE = 'd-m-Y';
    const DATETIME_STYLE = 'd-m-Y H:i:s';
    const HOUR_STYLE = 'H:i:s';

    /**
     * Transform a text according to patterns and indicated format.
     * The options parameter can contain the name of the field to use for each pattern.
     * If not reported, field names will be used by default.
     * eg: ['date' => 'creationdate']
     *
     * @param string $text
     * @param object $model
     * @param array $options
     *
     * @return string
     */
    public static function trans(string $text, &$model, array $options = []): string
    {
        // columnas
        $colFecha = $options['fecha'] ?? 'fecha';
        $colHora = $options['hora'] ?? 'hora';
        $colNumero = $options['numero'] ?? 'numero';
        $colSerie = $options['serie'] ?? 'codserie';
        $colEjercicio = $options['ejercicio'] ?? 'codejercicio';

        // valores
        $ejercicio = $model->{$colEjercicio} ?? '';
        $fecha = $model->{$colFecha} ?? date(self::DATE_STYLE);
        $hora = $model->{$colHora} ?? date(self::HOUR_STYLE);
        $long = $options['long'] ?? 0;
        $numero = $model->{$colNumero} ?? 0;
        $serie = $model->{$colSerie} ?? '';

        // El separador | sirve para aplicar filtros. Usaremos solamente la primera parte para transformar
        $parts = explode('|', $text);

        // transformación
        $result = strtr($parts[0], [
            '{FECHA}' => date(self::DATE_STYLE, strtotime($fecha)),
            '{HORA}' => date(self::HOUR_STYLE, strtotime($hora)),
            '{FECHAHORA}' => date(self::DATETIME_STYLE, strtotime($fecha . ' ' . $hora)),
            '{ANYO}' => date('Y', strtotime($fecha)),
            '{DIA}' => date('d', strtotime($fecha)),
            '{EJE}' => $ejercicio,
            '{EJE2}' => substr($ejercicio, -2),
            '{MES}' => date('m', strtotime($fecha)),
            '{NUM}' => $numero,
            '{SERIE}' => $serie,
            '{0NUM}' => str_pad($numero, $long, '0', STR_PAD_LEFT),
            '{0SERIE}' => str_pad($serie, 2, '0', STR_PAD_LEFT),
            '{NOMBREMES}' => ToolBox::i18n()->trans(strtolower(date('F', strtotime($fecha))))
        ]);

        // si hay filtros, los aplicamos ahora
        return count($parts) > 1 ? static::format($result, $parts[1]) : $result;
    }

    /**
     * Transform the text to the indicated format:
     *  - All to Uppercase
     *  - All to Lowercase
     *  - Uppercase only the First Word
     *  - Uppercase the first letter of each word
     *
     * @param string $text
     * @param string $option
     *
     * @return string
     */
    private static function format(string $text, string $option): string
    {
        switch ($option) {
            case 'M':
                return strtoupper($text);

            case 'm':
                return strtolower($text);

            case 'P':
                return ucfirst($text);

            case 'T':
                return ucwords($text);
        }

        return $text;
    }
}
