<?php
/**
 * This file is part of facturacion_base
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
namespace FacturaScripts\Core\Model;

/**
 * Permite relacionar cuentas especiales (VENTAS, por ejemplo)
 * con la cuenta o subcuenta real.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaEspecial
{

    use Base\ModelTrait;

    /**
     * Identificador de la cuenta especial.
     *
     * @var string
     */
    public $idcuentaesp;

    /**
     * Descripción de la cuenta especial.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_cuentasesp';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idcuentaesp';
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        return "INSERT INTO co_cuentasesp (idcuentaesp,descripcion) VALUES 
         ('IVAREP','Cuentas de IVA repercutido'),
         ('IVASOP','Cuentas de IVA soportado'),
         ('IVARUE','Cuentas de IVA soportado UE'),
         ('IVASUE','Cuentas de IVA soportado UE'),
         ('IVAACR','Cuentas acreedoras de IVA en la regularización'),
         ('IVADEU','Cuentas deudoras de IVA en la regularización'),
         ('PYG','Pérdidas y ganancias'),
         ('PREVIO','Cuentas relativas al ejercicio previo'),
         ('CAMPOS','Cuentas de diferencias positivas de cambio'),
         ('CAMNEG','Cuentas de diferencias negativas de cambio'),
         ('DIVPOS','Cuentas por diferencias positivas en divisa extranjera'),
         ('EURPOS','Cuentas por diferencias positivas de conversión a la moneda local'),
         ('EURNEG','Cuentas por diferencias negativas de conversión a la moneda local'),
         ('CLIENT','Cuentas de clientes'),
         ('PROVEE','Cuentas de proveedores'),
         ('ACREED','Cuentas de acreedores'),
         ('COMPRA','Cuentas de compras'),
         ('VENTAS','Cuentas de ventas'),
         ('CAJA','Cuentas de caja'),
         ('IRPFPR','Cuentas de retenciones para proveedores IRPFPR'),
         ('IRPF','Cuentas de retenciones IRPF'),
         ('GTORF','Gastos por recargo financiero'),
         ('INGRF','Ingresos por recargo financiero'),
         ('DEVCOM','Devoluciones de compras'),
         ('DEVVEN','Devoluciones de ventas'),
         ('IVAEUE','IVA en entregas intracomunitarias U.E.'),
         ('IVAREX','Cuentas de IVA repercutido para clientes exentos de IVA'),
         ('IVARXP','Cuentas de IVA repercutido en exportaciones'),
         ('IVASIM','Cuentas de IVA soportado en importaciones')";
    }
}
