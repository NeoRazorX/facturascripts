<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Artículo vendido por un proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ArticuloProveedor
{

    use Base\ModelTrait {
        save as private saveTrait;
    }

    /**
     * TODO
     * @var array
     */
    private static $impuestos;

    /**
     * TODO
     * @var array
     */
    private static $nombres;

    /**
     * Clave primaria.
     * @var int
     */
    public $id;

    /**
     * Referencia del artículo en nuestro catálogo. Puede no estar actualmente.
     * @var string
     */
    public $referencia;

    /**
     * Código del proveedor asociado.
     * @var string
     */
    public $codproveedor;

    /**
     * Referencia del artículo para el proveedor.
     * @var string
     */
    public $refproveedor;

    /**
     * Descripción del artículo
     * @var string
     */
    public $descripcion;

    /**
     * Precio neto al que nos ofrece el proveedor este producto.
     * @var float
     */
    public $precio;

    /**
     * Descuento sobre el precio que nos hace el proveedor.
     * @var float
     */
    public $dto;

    /**
     * Impuesto asignado. Clase impuesto.
     * @var string
     */
    public $codimpuesto;

    /**
     * Stock del artículo en el almacén del proveedor.
     * @var float
     */
    public $stock;

    /**
     * TRUE -> el artículo no ofrece stock.
     * @var bool
     */
    public $nostock;

    /**
     * Código de barras del artículo
     * @var string
     */
    public $codbarras;

    /**
     * Part Number
     * @var string
     */
    public $partnumber;

    /**
     * % IVA del impuesto asignado.
     * @var float
     */
    private $iva;

    public function tableName()
    {
        return 'articulosprov';
    }

    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->referencia = null;
        $this->codproveedor = null;
        $this->refproveedor = null;
        $this->descripcion = null;
        $this->precio = 0;
        $this->dto = 0;
        $this->codimpuesto = null;
        $this->stock = 0;
        $this->nostock = true;
        $this->codbarras = null;
        $this->partnumber = null;
    }

    /**
     * TODO
     * @return string
     */
    public function nombreProveedor()
    {
        if (isset(self::$nombres[$this->codproveedor])) {
            return self::$nombres[$this->codproveedor];
        }
        $sql = 'SELECT razonsocial FROM proveedores WHERE codproveedor = ' . $this->var2str($this->codproveedor) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            self::$nombres[$this->codproveedor] = $data[0]['razonsocial'];
            return $data[0]['razonsocial'];
        }
        return '-';
    }

    /**
     * TODO
     * @return string
     */
    public function urlProveedor()
    {
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * Devuelve el % de IVA del artículo.
     * Si $reload es TRUE, vuelve a consultarlo en lugar de usar los datos cargados.
     *
     * @param bool $reload
     *
     * @return float|int|null
     */
    public function getIva($reload = true)
    {
        if ($reload) {
            $this->iva = null;
        }

        if ($this->iva === null) {
            $this->iva = 0;

            if (!$this->codimpuesto === null) {
                $encontrado = false;
                foreach (self::$impuestos as $i) {
                    if ($i instanceof Impuesto && $i->codimpuesto === $this->codimpuesto) {
                        $this->iva = $i->iva;
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $imp = new Impuesto();
                    $imp0 = $imp->get($this->codimpuesto);
                    if ($imp0) {
                        $this->iva = $imp0->iva;
                        self::$impuestos[] = $imp0;
                    }
                }
            }
        }

        return $this->iva;
    }

    /**
     * TODO
     * @return bool|mixed
     */
    public function getArticulo()
    {
        if ($this->referencia === null) {
            return false;
        }
        $art0 = new Articulo();
        return $art0->get($this->referencia);
    }

    /**
     * Devuelve el precio final, aplicando descuento e impuesto.
     * @return float
     */
    public function totalIva()
    {
        return $this->precio * (100 - $this->dto) / 100 * (100 + $this->getIva()) / 100;
    }

    /**
     * Devuelve el primer elemento que tenga $ref como referencia y $codproveedor
     * como codproveedor. Si se proporciona $refprov, entonces lo que devuelve es el
     * primer elemento que tenga $codproveedor como codproveedor y $refprov como refproveedor
     * o bien $ref como referencia.
     *
     * @param string $ref
     * @param string $codproveedor
     * @param string $refprov
     *
     * @return ArticuloProveedor|bool
     */
    public function getBy($ref, $codproveedor, $refprov = '')
    {
        $sql = 'SELECT * FROM articulosprov WHERE referencia = ' . $this->var2str($ref)
            . ' AND codproveedor = ' . $this->var2str($codproveedor) . ';';
        if ($refprov !== '') {
            $sql = 'SELECT * FROM articulosprov WHERE codproveedor = ' . $this->var2str($codproveedor)
                . ' AND (refproveedor = ' . $this->var2str($refprov)
                . ' OR referencia = ' . $this->var2str($ref) . ');';
        }

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new ArticuloProveedor($data[0]);
        }
        return false;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->descripcion = static::noHtml($this->descripcion);

        if ($this->nostock) {
            $this->stock = 0;
        }

        if ($this->refproveedor === null || empty($this->refproveedor) || strlen($this->refproveedor) > 25) {
            $this->miniLog->alert('La referencia de proveedor debe contener entre 1 y 25 caracteres.');
        }
        return $this->saveTrait();
    }

    /**
     * Devuelve todos los elementos que tienen $ref como referencia.
     *
     * @param string $ref
     *
     * @return array
     */
    public function allFromRef($ref)
    {
        $alist = [];
        $sql = 'SELECT * FROM articulosprov WHERE referencia = ' . $this->var2str($ref) . ' ORDER BY precio ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $alist[] = new ArticuloProveedor($d);
            }
        }

        return $alist;
    }

    /**
     * Devuelve el artículo con menor precio de los que tienen $ref como referencia.
     *
     * @param string $ref
     *
     * @return bool|ArticuloProveedor
     */
    public function mejorFromRef($ref)
    {
        $sql = 'SELECT * FROM articulosprov WHERE referencia = ' . $this->var2str($ref)
            . ' ORDER BY precio ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new ArticuloProveedor($data[0]);
        }
        return false;
    }

    /**
     * Devuelve todos los articulos que tienen asociada una referencia para actualizar.
     *
     * @param
     *
     * @return array
     */
    public function allConRef()
    {
        $alist = [];
        $sql = "SELECT * FROM articulosprov WHERE referencia !='' ORDER BY precio ASC;";

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $alist[] = new ArticuloProveedor($d);
            }
        }

        return $alist;
    }

    /**
     * Aplicamos correcciones a la tabla.
     */
    public function fixDb()
    {
        $fixes = [
            'DELETE FROM articulosprov WHERE codproveedor NOT IN (SELECT codproveedor FROM proveedores);',
            'UPDATE articulosprov SET refproveedor = referencia WHERE refproveedor IS NULL;'
        ];
        foreach ($fixes as $sql) {
            $this->dataBase->exec($sql);
        }
    }
}
