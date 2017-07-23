<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Permite relacionar cuentas especiales (VENTAS, por ejemplo)
 * con la cuenta o subcuenta real.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class CuentaEspecial
{
    use Model;

    /**
     * Identificador de la cuenta especial.
     * @var
     */
    public $idcuentaesp;
    /**
     * TODO
     * @var
     */
    public $descripcion;

    /**
     * CuentaEspecial constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'co_cuentasesp', 'idcuentaesp');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * TODO
     * @return array
     */
    public function all()
    {
        $culist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY descripcion ASC;';
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $c) {
                $culist[] = new CuentaEspecial($c);
            }
        }

        /// comprobamos la de acreedores
        $encontrada = false;
        foreach ($culist as $ce) {
            if ($ce->idcuentaesp === 'ACREED') {
                $encontrada = true;
            }
        }
        if (!$encontrada) {
            $ce = new CuentaEspecial();
            $ce->idcuentaesp = 'ACREED';
            $ce->descripcion = 'Cuentas de acreedores';
            if ($ce->save()) {
                $culist[] = $ce;
            }
        }

        return $culist;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
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
