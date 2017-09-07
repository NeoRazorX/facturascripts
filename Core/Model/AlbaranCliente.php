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
    use Base\ModelTrait {
        clear as clearTrait;
    }

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
        $this->clearTrait();
        $this->codserie = $this->defaultItems->codSerie();
        $this->codpago = $this->defaultItems->codPago();
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->tasaconv = 1.0;
        $this->ptefactura = true;
    }

    /**
     * Devuelve la url donde se pueden ver/modificar los datos de la factura
     *
     * @return string
     */
    public function facturaUrl()
    {
        if ($this->idfactura === null) {
            return '#';
        }

        return 'index.php?page=VentasFactura&id=' . $this->idfactura;
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
     * Devuelve un albarán por su código si existe,
     * sino devuelve false
     *
     * @param string $cod
     *
     * @return AlbaranCliente|bool
     */
    public function getByCodigo($cod)
    {
        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE upper(codigo) = ' . strtoupper($this->var2str($cod)) . ';';
        $albaran = $this->dataBase->select($sql);
        if (!empty($albaran)) {
            return new self($albaran[0]);
        }

        return false;
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si son correctos
     *
     * @return bool
     */
    public function test()
    {
        $this->nombrecliente = self::noHtml($this->nombrecliente);
        if ($this->nombrecliente === '') {
            $this->nombrecliente = '-';
        }

        $this->direccion = self::noHtml($this->direccion);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->provincia = self::noHtml($this->provincia);
        $this->envio_nombre = self::noHtml($this->envio_nombre);
        $this->envio_apellidos = self::noHtml($this->envio_apellidos);
        $this->envio_direccion = self::noHtml($this->envio_direccion);
        $this->envio_ciudad = self::noHtml($this->envio_ciudad);
        $this->envio_provincia = self::noHtml($this->envio_provincia);
        $this->numero2 = self::noHtml($this->numero2);
        $this->observaciones = self::noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->idfactura) {
            $this->ptefactura = false;
        }

        if ($this->floatcmp(
                $this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true
            )) {
            return true;
        }

        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Avisa al informático!');

        return false;
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
     * Devuelve un array con todos los albaranes que coinciden con $query
     *
     * @param string $query
     * @param int    $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alblist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $a) {
                $alblist[] = new self($a);
            }
        }

        return $alblist;
    }

    /**
     * Devuelve un array con los albaranes del cliente $codcliente que coincidan
     * con los filtros.
     *
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $obs
     * @param string $coddivisa
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $desde, $hasta, $codserie = '', $obs = '', $coddivisa = '')
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($codcliente)
            . ' AND ptefactura AND fecha BETWEEN ' . $this->var2str($desde) . ' AND ' . $this->var2str($hasta);

        if ($codserie !== '') {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }

        if ($obs !== '') {
            $sql .= ' AND lower(observaciones) = ' . $this->var2str(mb_strtolower($obs, 'UTF8'));
        }

        if ($coddivisa !== '') {
            $sql .= ' AND coddivisa = ' . $this->var2str($coddivisa);
        }

        $sql .= ' ORDER BY fecha ASC, codigo ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $a) {
                $albalist[] = new self($a);
            }
        }

        return $albalist;
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
