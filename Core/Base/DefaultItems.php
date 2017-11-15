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
namespace FacturaScripts\Core\Base;

/**
 * Esta clase sólo sirve para que los modelos sepan que elementos son los
 * predeterminados para la sesión. Pero para guardar los valores hay que usar
 * las funciones fs_controller::save_lo_que_sea()
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DefaultItems
{

    /**
     * Código de ejercicio
     *
     * @var string
     */
    private static $codEjercicio;

    /**
     * Código de almacén
     *
     * @var string
     */
    private static $codAlmacen;

    /**
     * Código de divisa
     *
     * @var string
     */
    private static $codDivisa;

    /**
     * Código de forma de pago
     *
     * @var string
     */
    private static $codPago;

    /**
     * Código de impuesto
     *
     * @var string
     */
    private static $codImpuesto;

    /**
     * Código de país
     *
     * @var string
     */
    private static $codPais;

    /**
     * Código de serie
     *
     * @var string
     */
    private static $codSerie;

    /**
     * Devuelve el código de ejercicio por defecto
     *
     * @return string|null
     */
    public function codEjercicio()
    {
        return self::$codEjercicio;
    }

    /**
     * Asigna el código de ejercicio por defecto
     *
     * @param string $cod
     */
    public function setCodEjercicio($cod)
    {
        self::$codEjercicio = $cod;
    }

    /**
     * Devuelve el código de almacén por defecto
     *
     * @return string|null
     */
    public function codAlmacen()
    {
        return self::$codAlmacen;
    }

    /**
     * Asigna el código de almacén por defecto
     *
     * @param string $cod
     */
    public function setCodAlmacen($cod)
    {
        self::$codAlmacen = $cod;
    }

    /**
     * Devuelve el código de la divisa por defecto
     *
     * @return string|null
     */
    public function codDivisa()
    {
        return self::$codDivisa;
    }

    /**
     * Asigna el código de la divisa por defecto
     *
     * @param string $cod
     */
    public function setCodDivisa($cod)
    {
        self::$codDivisa = $cod;
    }

    /**
     * Devuelve el código de la forma de pago por defecto
     *
     * @return string|null
     */
    public function codPago()
    {
        return self::$codPago;
    }

    /**
     * Asigna el código de la forma de pago por defecto
     *
     * @param string $cod
     */
    public function setCodPago($cod)
    {
        self::$codPago = $cod;
    }

    /**
     * Devuelve el código del impuesto por defecto
     *
     * @return string|null
     */
    public function codImpuesto()
    {
        return self::$codImpuesto;
    }

    /**
     * Asigna el código del impuesto por defecto
     *
     * @param string $cod
     */
    public function setCodImpuesto($cod)
    {
        self::$codImpuesto = $cod;
    }

    /**
     * Devuelve el código de país por defecto
     *
     * @return string|null
     */
    public function codPais()
    {
        return self::$codPais;
    }

    /**
     * Asigna el código de país por defecto
     *
     * @param string $cod
     */
    public function setCodPais($cod)
    {
        self::$codPais = $cod;
    }

    /**
     * Devuelve el código de la serie por defecto
     *
     * @return string|null
     */
    public function codSerie()
    {
        return self::$codSerie;
    }

    /**
     * Asigna el código de la serie por defecto
     *
     * @param string $cod
     */
    public function setCodSerie($cod)
    {
        self::$codSerie = $cod;
    }
}
