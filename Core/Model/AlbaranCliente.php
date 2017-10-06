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
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Albarán de cliente o albarán de venta. Representa la entrega a un cliente
 * de un material que se le ha vendido. Implica la salida de ese material
 * del almacén de la empresa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranCliente
{

    use Base\DocumentoVenta;

    /**
     * Clave primaria. Integer.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * ID de la factura relacionada, si la hay.
     *
     * @var int
     */
    public $idfactura;

    /**
     * TRUE => está pendiente de factura.
     *
     * @var bool
     */
    public $ptefactura;

    public function tableName()
    {
        return 'albaranescli';
    }

    public function primaryColumn()
    {
        return 'idalbaran';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearDocumentoVenta();
        $this->ptefactura = true;
    }

    /**
     * Devuelve las líneas del albarán.
     *
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranCliente();
        return $lineaModel->all(new DataBaseWhere('idalbaran', $this->idalbaran));
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si son correctos
     *
     * @return bool
     */
    public function test()
    {
        return $this->testTrait();
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return FALSE;
    }

    /**
     * TODO
     */
    public function cronJob()
    {
        /**
         * Ponemos a NULL todos los idfactura que no están en facturascli.
         * ¿Por qué? Porque muchos usuarios se dedican a tocar la base de datos.
         */
        $this->dataBase->exec('UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturascli);');
    }
}
