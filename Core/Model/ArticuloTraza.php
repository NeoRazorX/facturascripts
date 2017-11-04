<?php
/**
 * This file is part of facturacion_base
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
 * Esta clase sirve para guardar la información de trazabilidad del artículo.
 * Números de serie, de lote y albaranes y facturas relacionadas.
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
     * Clave primaria.
     *
     * @var string
     */
    public $numserie;

    /**
     * Número o identificador del lote
     *
     * @var string
     */
    public $lote;

    /**
     * Id linea albaran venta
     *
     * @var int
     */
    public $idlalbventa;

    /**
     * id linea factura venta
     *
     * @var int
     */
    public $idlfacventa;

    /**
     * Id linea albaran compra
     *
     * @var int
     */
    public $idlalbcompra;

    /**
     * Id linea factura compra
     *
     * @var int
     */
    public $idlfaccompra;

    /**
     * Fecha de entrada del artículo
     *
     * @var |DateTime
     */
    public $fecha_entrada;

    /**
     * Fecha de salida del artículo
     *
     * @var |DateTime
     */
    public $fecha_salida;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulo_trazas';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Devuelve la url del albarán o la factura de compra.
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
     * Devuelve la url del albarán o factura de venta.
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
     * Devuelve la traza correspondiente al número de serie $numserie.
     *
     * @param string $numserie
     *
     * @return bool|ArticuloTraza
     */
    public function getByNumserie($numserie)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE numserie = ' . $this->var2str($numserie) . ';';
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
     * @param bool   $sololibre
     *
     * @return self[]
     */
    public function allFromRef($ref, $sololibre = false)
    {
        $lista = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref);
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
     * Devuelve todas las trazas cuya columna $tipo tenga valor $idlinea
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
            . ' WHERE ' . $tipo . ' = ' . $this->var2str($idlinea) . ' ORDER BY id DESC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new self($d);
            }
        }

        return $lista;
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
        /// forzamos la comprobación de las tablas necesarias
        //new Articulo();
        //new LineaAlbaranCliente();
        //new LineaAlbaranProveedor();
        //new LineaFacturaCliente();
        //new LineaFacturaProveedor();

        return '';
    }
}
