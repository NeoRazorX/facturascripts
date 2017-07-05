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

/**
 * Esta clase sólo sirve para que los modelos sepan que elementos son los
 * predeterminados para la sesión. Pero para guardar los valores hay que usar
 * las funciones fs_controller::save_lo_que_sea()
 * 
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DefaultItems {

    private static $defaultPage;
    private static $showingPage;
    private static $codEjercicio;
    private static $codAlmacen;
    private static $codDivisa;
    private static $codPago;
    private static $codImpuesto;
    private static $codPais;
    private static $codSerie;

    public function codEjercicio() {
        return self::$codEjercicio;
    }

    public function setCodEjercicio($cod) {
        self::$codEjercicio = $cod;
    }

    public function codAlmacen() {
        return self::$codAlmacen;
    }

    public function setCodAlmacen($cod) {
        self::$codAlmacen = $cod;
    }

    public function codDivisa() {
        return self::$codDivisa;
    }

    public function setCodDivisa($cod) {
        self::$codDivisa = $cod;
    }

    public function codPago() {
        return self::$codPago;
    }

    public function setCodPago($cod) {
        self::$codPago = $cod;
    }

    public function codImpuesto() {
        return self::$codImpuesto;
    }

    public function setCodImpuesto($cod) {
        self::$codImpuesto = $cod;
    }

    public function codPais() {
        return self::$codPais;
    }

    public function setCodPais($cod) {
        self::$codPais = $cod;
    }

    public function codSerie() {
        return self::$codSerie;
    }

    public function setCodSerie($cod) {
        self::$codSerie = $cod;
    }

    public function defaultPage() {
        return self::$defaultPage;
    }

    public function setDefaultPage($name) {
        self::$defaultPage = $name;
    }

    public function showingPage() {
        return self::$showingPage;
    }

    public function setShowingPage($name) {
        self::$showingPage = $name;
    }

}
