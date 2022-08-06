<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all common method for VAT Regime.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class RegimenIVA
{

    const TAX_SYSTEM_EXEMPT = 'Exento';
    const TAX_SYSTEM_GENERAL = 'General';
    const TAX_SYSTEM_SURCHARGE = 'Recargo';
    const TAX_SYSTEM_SIMPLIFIED = 'Simplificado';
    const TAX_SYSTEM_ESPECIAL_AGP = 'Especial_AGP';

    const ES_TAX_EXCEPTION_E1 = 'ES_20';
    const ES_TAX_EXCEPTION_E2 = 'ES_21';
    const ES_TAX_EXCEPTION_E3 = 'ES_22';
    const ES_TAX_EXCEPTION_E4 = 'ES_23_24';
    const ES_TAX_EXCEPTION_E5 = 'ES_25';
    const ES_TAX_EXCEPTION_E6 = 'ES_OTHER';

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::TAX_SYSTEM_EXEMPT => ToolBox::i18n()->trans('tax-system-exempt'),
            self::TAX_SYSTEM_GENERAL => ToolBox::i18n()->trans('tax-system-general'),
            self::TAX_SYSTEM_SURCHARGE => ToolBox::i18n()->trans('tax-system-surcharge'),
            self::TAX_SYSTEM_SIMPLIFIED => ToolBox::i18n()->trans('tax-system-simplified'),
            self::TAX_SYSTEM_ESPECIAL_AGP => ToolBox::i18n()->trans('tax-system-especial-agp'),
        ];
    }

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function allExceptions()
    {
        return [
            self::ES_TAX_EXCEPTION_E1 => ToolBox::i18n()->trans('es-tax-exception-e1'),
            self::ES_TAX_EXCEPTION_E2 => ToolBox::i18n()->trans('es-tax-exception-e2'),
            self::ES_TAX_EXCEPTION_E3 => ToolBox::i18n()->trans('es-tax-exception-e3'),
            self::ES_TAX_EXCEPTION_E4 => ToolBox::i18n()->trans('es-tax-exception-e4'),
            self::ES_TAX_EXCEPTION_E5 => ToolBox::i18n()->trans('es-tax-exception-e5'),
            self::ES_TAX_EXCEPTION_E6 => ToolBox::i18n()->trans('es-tax-exception-e6'),
        ];
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return self::TAX_SYSTEM_GENERAL;
    }
}
