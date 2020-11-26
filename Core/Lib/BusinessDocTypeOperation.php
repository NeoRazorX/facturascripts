<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Translator;

/**
 * This class centralizes all common method for Business Doc Type Operation.
 *
 * @author Frank Aguirre <faguirre@soenac.com>
 * @deprecated since version 2020.82
 */
class BusinessDocTypeOperation
{

    const TYPE_OPERATION_DOCUMENT_STAN = 'ESTANDAR';

    /**
     *
     * @var Translator
     */
    public static $i18n;

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        if (!isset(self::$i18n)) {
            self::$i18n = new Translator();
        }

        return [
            self::TYPE_OPERATION_DOCUMENT_STAN => self::$i18n->trans('standard'),
        ];
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return self::TYPE_OPERATION_DOCUMENT_STAN;
    }
}
