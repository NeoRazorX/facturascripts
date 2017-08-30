<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Description of LineaDocumento
 *
 * @author Carlos García Gómez
 */
trait LineaDocumento
{

    /**
     * Clave primaria.
     * @var int
     */
    public $idlinea;

    /**
     * Referencia del artículo.
     * @var string
     */
    public $referencia;

    /**
     * Código de la combinación seleccionada, en el caso de los artículos con atributos.
     * @var
     */
    public $codcombinacion;

    /**
     * TODO
     * @var string
     */
    public $descripcion;

    /**
     * TODO
     * @var float
     */
    public $cantidad;

    /**
     * % de descuento.
     * @var float
     */
    public $dtopor;

    /**
     * Código del impuesto relacionado.
     * @var string
     */
    public $codimpuesto;

    /**
     * % del impuesto relacionado.
     * @var float
     */
    public $iva;

    /**
     * Importe neto de la línea, sin impuestos.
     * @var float
     */
    public $pvptotal;

    /**
     * Importe neto sin descuentos.
     * @var float
     */
    public $pvpsindto;

    /**
     * Precio del artículo, una unidad.
     * @var float
     */
    public $pvpunitario;

    /**
     * % de IRPF de la línea.
     * @var float
     */
    public $irpf;

    /**
     * % de recargo de equivalencia de la línea.
     * @var float
     */
    public $recargo;

    /**
     * TODO
     * @return float|int
     */
    public function pvpIva()
    {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    /**
     * TODO
     * @return float|int
     */
    public function totalIva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /**
     * TODO
     * @return float|int
     */
    public function totalIva2()
    {
        if ($this->cantidad === 0) {
            return 0;
        }

        return $this->pvptotal * (100 + $this->iva) / 100 / $this->cantidad;
    }

    /**
     * TODO
     * @return string
     */
    public function getDescripcion()
    {
        return nl2br($this->descripcion);
    }

    /**
     * TODO
     * @return string
     */
    public function articuloUrl()
    {
        if ($this->referencia === null || $this->referencia === '') {
            return 'index.php?page=VentasArticulos';
        }

        return 'index.php?page=VentasArticulo&ref=' . urlencode($this->referencia);
    }
}
