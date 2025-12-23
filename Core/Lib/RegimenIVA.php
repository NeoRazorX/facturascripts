<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * @deprecated use TaxRegime instead.
 * This class centralizes all common method for VAT Regime.
 *
 * @author          Carlos García Gómez         <carlos@facturascripts.com>
 * @collaborator    Daniel Fernández Giménez    <hola@danielfg.es>
 */
class RegimenIVA
{
    const ES_TAX_EXCEPTION_E1 = TaxException::ES_TAX_EXCEPTION_20; // E1 Exenta art. 20 LIVA – Exenciones interiores (sanidad, enseñanza, seguros, financieros…)
    const ES_TAX_EXCEPTION_E2 = TaxException::ES_TAX_EXCEPTION_21; // E2 Exenta art. 21 LIVA – Exportaciones a países terceros
    const ES_TAX_EXCEPTION_E3 = TaxException::ES_TAX_EXCEPTION_22; // E3 Exenta art. 22 LIVA – Operaciones asimiladas a exportaciones
    const ES_TAX_EXCEPTION_E4 = TaxException::ES_TAX_EXCEPTION_23_24; // E4 Exenta arts. 23–24 LIVA – Zonas francas y depósitos aduaneros
    const ES_TAX_EXCEPTION_E5 = TaxException::ES_TAX_EXCEPTION_25; // E5 Exenta art. 25 LIVA – Entregas intracomunitarias de bienes
    const ES_TAX_EXCEPTION_E6 = TaxException::ES_TAX_EXCEPTION_OTHER; // E6 Otras exenciones (oro de inversión, regímenes especiales, etc.)
    const ES_TAX_EXCEPTION_PASSIVE_SUBJECT = TaxException::ES_TAX_EXCEPTION_84; // S2 Inversión del sujeto pasivo (art. 84 LIVA)
    const ES_TAX_EXCEPTION_ART_7 = TaxException::ES_TAX_EXCEPTION_7; // N3 No sujeta art. 7 LIVA – Operaciones no sujetas (aportaciones, transmisión de UEA, muestras…)
    const ES_TAX_EXCEPTION_ART_14 = TaxException::ES_TAX_EXCEPTION_14; // N4 No sujeta art. 14 LIVA – Operaciones vinculadas a exportaciones
    const ES_TAX_EXCEPTION_LOCATION_RULES = TaxException::ES_TAX_EXCEPTION_68_70; // N2 No sujeta – Reglas de localización de servicios (arts. 69–70 LIVA, servicios B2B UE o fuera UE)
    const ES_TAX_EXCEPTION_N1 = TaxException::ES_TAX_EXCEPTION_68_70; // No sujeta N1 – Reglas de localización entregas de bienes (art. 68 LIVA)
    const ES_TAX_EXCEPTION_N5 = TaxException::ES_OTHER_NOT_SUBJECT; // No sujeta N5 – Otras disposiciones específicas (OTAN, convenios internacionales…)
    const TAX_SYSTEM_AGRARIAN = TaxRegime::ES_TAX_REGIME_AGRARIAN;
    const TAX_SYSTEM_CASH_CRITERIA = TaxRegime::ES_TAX_REGIME_CASH_CRITERIA;
    const TAX_SYSTEM_EXEMPT = 'Exento'; // es una característica de algunas operaciones dentro de un régimen, no un régimen en sí, es un tipo de operación.
    const TAX_SYSTEM_GENERAL = TaxRegime::ES_TAX_REGIME_GENERAL;
    const TAX_SYSTEM_GOLD = TaxRegime::ES_TAX_REGIME_GOLD;
    const TAX_SYSTEM_GROUP_ENTITIES = TaxRegime::ES_TAX_REGIME_GROUP_ENTITIES;
    const TAX_SYSTEM_ONE_STOP_SHOP_OSS = TaxRegime::ES_TAX_REGIME_DISTANCE_SALES; // esto sería ventas a distancia de bienes dentro de la UE
    const TAX_SYSTEM_ONE_STOP_SHOP_IOSS = TaxRegime::ES_TAX_REGIME_DISTANCE_SALES; // esto sería ventas a distancia de bienes importados desde fuera de la UE
    const TAX_SYSTEM_SIMPLIFIED = TaxRegime::ES_TAX_REGIME_SIMPLIFIED;
    const TAX_SYSTEM_SPECIAL_RETAIL_TRADERS = TaxRegime::ES_TAX_REGIME_SURCHARGE; // esto sería recargo de equivalencia
    const TAX_SYSTEM_SPECIAL_SMALL_BUSINESS = TaxRegime::ES_TAX_REGIME_SIMPLIFIED; // esto sería simplificado
    const TAX_SYSTEM_SURCHARGE = TaxRegime::ES_TAX_REGIME_SURCHARGE;
    const TAX_SYSTEM_TELECOM = TaxRegime::ES_TAX_REGIME_DISTANCE_SALES; // esto sería ventas a distancia
    const TAX_SYSTEM_TRAVEL = TaxRegime::ES_TAX_REGIME_TRAVEL;
    const TAX_SYSTEM_USED_GOODS = TaxRegime::ES_TAX_REGIME_USED_GOODS;

    public static function add(string $key, string $value): void
    {
        TaxRegime::add($key, $value);
    }

    public static function addException(string $key, string $value): void
    {
        TaxException::add($key, $value);
    }

    public static function all(): array
    {
        return TaxRegime::all();
    }

    public static function allExceptions(): array
    {
        return TaxException::all();
    }

    public static function defaultValue(): string
    {
        return self::TAX_SYSTEM_GENERAL;
    }
}
