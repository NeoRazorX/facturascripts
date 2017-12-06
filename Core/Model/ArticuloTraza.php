<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016      Luismipr               <luismipr@gmail.com>.
 * Copyright (C) 2016-2017 Carlos García Gómez    <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * Lpublished by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * LeGNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

/**
 * This class is used to save the traceability information of the article.
 * Serial numbers, batch numbers and delivery notes and related invoices.
 *
 * @author Luismipr              <luismipr@gmail.com>
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 */
class ArticuloTraza
{

    use Base\ModelTrait;

    /**
     * Clave primaria
     *
     * @var int
     */
    public $id;

    /**
     * Referencia del artículo
     *
     * @var string
     */
    public $referencia;

    /**
     * Numero de serie
     * Primary key.
     *
     * @var string
     */
    public $numserie;

    /**
     * Lot number or identifier
     *
     * @var string
     */
    public $lote;

    /**
     * Id line sale
     *
     * @var int
     */
    public $idlalbventa;

    /**
     * id line invoice sale
     *
     * @var int
     */
    public $idlfacventa;

    /**
     * Id linea albaran buy
     *
     * @var int
     */
    public $idlalbcompra;

    /**
     * Id line invoice purchase
     *
     * @var int
     */
    public $idlfaccompra;

    /**
     * Fecha de entrada del artículo
     *
     * @var \DateTime
     */
    public $fecha_entrada;

    /**
     * Fecha de salida del artículo
     *
     * @var \DateTime
     */
    public $fecha_salida;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulo_trazas';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the url of the delivery note or the purchase invoice.
     *
     * @return string
     */
    public function documentoCompraUrl()
    {
        if ($this->idlalbcompra) {
            $lin0 = new LineaAlbaranProveedor();
            $linea = $lin0->get($this->idlalbcompra);
            if ($linea) {
                return $linea->url();
            }
        } elseif ($this->idlfaccompra) {
            $lin0 = new LineaFacturaProveedor();
            $linea = $lin0->get($this->idlfaccompra);
            if ($linea) {
                return $linea->url();
            }
        }

        return '#';
    }

    /**
     * Returns the url of the delivery note or sales invoice.
     *
     * @return string
     */
    public function documentoVentaUrl()
    {
        if ($this->idlalbventa) {
            $lin0 = new LineaAlbaranCliente();
            $linea = $lin0->get($this->idlalbventa);
            if ($linea) {
                return $linea->url();
            }
        }
        if ($this->idlfaccompra) {
            $lin0 = new LineaFacturaProveedor();
            $linea = $lin0->get($this->idlfaccompra);
            if ($linea) {
                return $linea->url();
            }
        }

        return '#';
    }

    /**
     * Returns the trace corresponding to the serial number $ numserie.
     *
     * @param string $numserie
     *
     * @return bool|ArticuloTraza
     */
    public function getByNumserie($numserie)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE numserie = ' . $this->dataBase->var2str($numserie) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Devuelve todas las trazas de un artículo.
     *
     * @param string $ref
     * @param bool $sololibre
     *
     * @return self[]
     */
    public function allFromRef($ref, $sololibre = false)
    {
        $lista = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->dataBase->var2str($ref);
        if ($sololibre) {
            $sql .= ' AND idlalbventa IS NULL AND idlfacventa IS NULL';
        }
        $sql .= ' ORDER BY id ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new self($d);
            }
        }

        return $lista;
    }

    /**
     * Returns all the traces whose $ type column has value $ idlinea
     *
     * @param string $tipo
     * @param string $idlinea
     *
     * @return self[]
     */
    public function allFromLinea($tipo, $idlinea)
    {
        $lista = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE ' . $tipo . ' = ' . $this->dataBase->var2str($idlinea) . ' ORDER BY id DESC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new self($d);
            }
        }

        return $lista;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// forzamos la comprobación de las tablas necesarias
        new Articulo();
        new LineaAlbaranCliente();
        new LineaAlbaranProveedor();
        new LineaFacturaCliente();
        new LineaFacturaProveedor();

        return '';
    }
}
