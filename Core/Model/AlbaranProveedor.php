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
 * Albarán de proveedor o albarán de compra. Representa la recepción
 * de un material que se ha comprado. Implica la entrada de ese material
 * al almacén.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AlbaranProveedor
{
    use Base\DocumentoCompra;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria. Integer
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
        return 'albaranesprov';
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
     * Devuelve la url donde ver/modificar los datos de la factura
     *
     * @return string
     */
    public function facturaUrl()
    {
        if ($this->idfactura === null) {
            return '#';
        }

        return 'index.php?page=ComprasFactura&id=' . $this->idfactura;
    }

    /**
     * Devuelve la url donde ver/modificar los datos de agentes
     *
     * @return string
     */
    public function agenteUrl()
    {
        if ($this->codagente === null) {
            return 'index.php?page=AdminAgentes';
        }

        return 'index.php?page=AdminAgente&cod=' . $this->codagente;
    }

    /**
     * Devuelve la url donde ver/modificar los datos de proveedores
     *
     * @return string
     */
    public function proveedorUrl()
    {
        if ($this->codproveedor === null) {
            return 'index.php?page=ComprasProveedores';
        }

        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * Devuelve las líneas asociadas al albarán
     *
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaAlbaranProveedor();

        return $lineaModel->all(new DataBaseWhere('idalbaran', $this->idalbaran));
    }

    /**
     * Genera un nuevo código y número para el albarán
     */
    public function newCodigo()
    {
        $this->numero = fsDocumentoNewNumero(
            $this->dataBase, $this->tableName(), $this->codejercicio, $this->codserie, 'nalbaranprov'
        );
        $this->codigo = fsDocumentoNewCodigo(FS_ALBARAN, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos del albarán, devuelve TRUE si está correcto
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);
        if ($this->nombre === '') {
            $this->nombre = '-';
        }

        $this->numproveedor = self::noHtml($this->numproveedor);
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
     * Elimina el albarán de la base de datos
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idalbaran = ' . $this->var2str($this->idalbaran) . ';';
        if ($this->dataBase->exec($sql)) {
            if ($this->idfactura) {
                /**
                 * Delegamos la eliminación de la factura en la clase correspondiente,
                 * que tendrá que hacer más cosas.
                 */
                $factura = new FacturaProveedor();
                $factura0 = $factura->get($this->idfactura);
                if ($factura0) {
                    $factura0->delete();
                }
            }

            $this->miniLog->info(ucfirst(FS_ALBARAN) . ' de compra ' . $this->codigo . ' eliminado correctamente.');

            return true;
        }

        return false;
    }

    /**
     * Devuelve un array con los albaranes que coinciden con $query
     *
     * @param string  $query
     * @param integer $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $alblist = [];
        $query = self::noHtml(mb_strtolower($query, 'UTF8'));

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%'"
                . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
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
     * Devuelve un array con los albaranes del proveedor $codproveedor
     * que coincidan con los filtros.
     *
     * @param string $codproveedor
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $coddivisa
     *
     * @return array
     */
    public function searchFromProveedor($codproveedor, $desde, $hasta, $codserie = '', $coddivisa = '')
    {
        $albalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = '
            . $this->var2str($codproveedor) . ' AND ptefactura AND fecha BETWEEN '
            . $this->var2str($desde) . ' AND ' . $this->var2str($hasta);

        if ($codserie) {
            $sql .= ' AND codserie = ' . $this->var2str($codserie);
        }

        if ($coddivisa) {
            $sql .= ' AND coddivisa = ' . $this->var2str($coddivisa);
        }

        $sql .= ' ORDER BY fecha ASC, codigo ASC';

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
         * Ponemos a NULL todos los idfactura que no están en facturasprov
         */
        $sql = 'UPDATE ' . $this->tableName() . ' SET idfactura = NULL WHERE idfactura IS NOT NULL'
            . ' AND idfactura NOT IN (SELECT idfactura FROM facturasprov);';
        $this->dataBase->exec($sql);
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
        /// nos aseguramos de que se comprueban las tablas de facturas y series antes
        // new Serie();
        // new FacturaProveedor();

        return '';
    }
}
