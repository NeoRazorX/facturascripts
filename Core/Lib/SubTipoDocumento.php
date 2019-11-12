<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Frank Aguirre <faguirre@soenac.com>
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
 * This class centralizes all common method for Sub Type Document.
 *
 * @author Frank Aguirre <faguirre@soenac.com>
 */
class SubTipoDocumento
{
    const SUB_TYPE_DOCUMENT_NSI = 'FACTURAVENTA';
    const SUB_TYPE_DOCUMENT_EB = 'FACTURAEXPORTACION';

    public static $i18n;

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        self::$i18n = new Translator();

        return [
            self::SUB_TYPE_DOCUMENT_NSI => self::$i18n->trans('sales-invoice'),
            self::SUB_TYPE_DOCUMENT_EB => self::$i18n->trans('export-bill'),
        ];
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return self::SUB_TYPE_DOCUMENT_NSI;
    }
}
