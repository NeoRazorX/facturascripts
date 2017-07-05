<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  carlos@facturascripts.com
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

use FacturaScripts\Core\Model;

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
     * Página por defecto
     * @var Model\Page
     */
    private static $defaultPage;

    /**
     * Página que se está mostrando
     * @var Model\Page
     */
    private static $showingPage;

    /**
     * Código de ejercicio
     * @var Model\Ejercicio
     */
    private static $codEjercicio;

    /**
     * Código de almacén
     * @var Model\Almacen
     */
    private static $codAlmacen;

    /**
     * Código de divisa
     * @var Model\Divisa
     */
    private static $codDivisa;

    /**
     * Código de forma de pago
     * @var Model\FormaPago
     */
    private static $codPago;

    /**
     * Código de impuesto
     * @var Model\Impuesto
     */
    private static $codImpuesto;

    /**
     * Código de país
     * @var Model\Pais
     */
    private static $codPais;

    /**
     * Código de serie
     * @var Model\Serie
     */
    private static $codSerie;

    /**
     * Devuelve el código de ejercicio por defecto
     * @return Model\Ejercicio|null
     */
    public function codEjercicio()
    {
        return self::$codEjercicio;
    }

    /**
     * Asigna el código de ejercicio por defecto
     * @param $cod
     */
    public function setCodEjercicio($cod)
    {
        self::$codEjercicio = $cod;
    }

    /**
     * Devuelve el código de almacén por defecto
     * @return Model\Almacen|null
     */
    public function codAlmacen()
    {
        return self::$codAlmacen;
    }

    /**
     * Asigna el código de almacén por defecto
     * @param $cod
     */
    public function setCodAlmacen($cod)
    {
        self::$codAlmacen = $cod;
    }

    /**
     * Devuelve el código de la divisa por defecto
     * @return Model\Divisa|null
     */
    public function codDivisa()
    {
        return self::$codDivisa;
    }

    /**
     * Asigna el código de la divisa por defecto
     * @param $cod
     */
    public function setCodDivisa($cod)
    {
        self::$codDivisa = $cod;
    }

    /**
     * Devuelve el código de la forma de pago por defecto
     * @return Model\FormaPago|null
     */
    public function codPago()
    {
        return self::$codPago;
    }

    /**
     * Asigna el código de la forma de pago por defecto
     * @param $cod
     */
    public function setCodPago($cod)
    {
        self::$codPago = $cod;
    }

    /**
     * Devuelve el código del impuesto por defecto
     * @return Model\Impuesto|null
     */
    public function codImpuesto()
    {
        return self::$codImpuesto;
    }

    /**
     * Asigna el código del impuesto por defecto
     * @param $cod
     */
    public function setCodImpuesto($cod)
    {
        self::$codImpuesto = $cod;
    }

    /**
     * Devuelve el código de país por defecto
     * @return Model\Pais|null
     */
    public function codPais()
    {
        return self::$codPais;
    }

    /**
     * Asigna el código de país por defecto
     * @param $cod
     */
    public function setCodPais($cod)
    {
        self::$codPais = $cod;
    }

    /**
     * Devuelve el código de la serie por defecto
     * @return Model\Serie|null
     */
    public function codSerie()
    {
        return self::$codSerie;
    }

    /**
     * Asigna el código de la serie por defecto
     * @param $cod
     */
    public function setCodSerie($cod)
    {
        self::$codSerie = $cod;
    }

    /**
     * Devuelve la página por defecto
     * @return Model\Page|null
     */
    public function defaultPage()
    {
        return self::$defaultPage;
    }

    /**
     * Asigna la página por defecto
     * @param $name
     */
    public function setDefaultPage($name)
    {
        self::$defaultPage = $name;
    }

    /**
     * Devuelve la página que se está mostrando
     * @return Model\Page|null
     */
    public function showingPage()
    {
        return self::$showingPage;
    }

    /**
     * Asigna la página que se está mostrando
     * @param $name
     */
    public function setShowingPage($name)
    {
        self::$showingPage = $name;
    }
}
