<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloProveedor
{

    use Base\ModelTrait {
        url as private traitURL;
    }

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $id;

    /**
     * Referencia del artículo en nuestro catálogo. Puede no estar actualmente.
     *
     * @var string
     */
    public $referencia;

    /**
     * Código del proveedor asociado.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Referencia del artículo para el proveedor.
     *
     * @var string
     */
    public $refproveedor;

    /**
     * Descripción del artículo
     *
     * @var string
     */
    public $descripcion;

    /**
     * Precio neto al que nos ofrece el proveedor este producto.
     *
     * @var float|int
     */
    public $precio;

    /**
     * Descuento sobre el precio que nos hace el proveedor.
     *
     * @var float|int
     */
    public $dto;

    /**
     * Impuesto asignado. Clase impuesto.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Stock del artículo en el almacén del proveedor.
     *
     * @var float|int
     */
    public $stock;

    /**
     * TRUE -> el artículo no ofrece stock.
     *
     * @var bool
     */
    public $nostock;

    /**
     * Código de barras del artículo
     *
     * @var string
     */
    public $codbarras;

    /**
     * Part Number
     *
     * @var string
     */
    public $partnumber;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulosprov';
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
     * Crea la consulta necesaria para crear un nuevo agente en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        /// forzamos la comprobación de la tabla de proveedores
        new Proveedor();

        return '';
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
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        if ($this->nostock) {
            $this->stock = 0;
        }

        if ($this->refproveedor === null || empty($this->refproveedor) || strlen($this->refproveedor) > 25) {
            $this->miniLog->alert($this->i18n->trans('supplier-reference-valid-length'));
            return false;
        }

        return true;
    }

    /**
     * Devuelve la url donde ver/modificar los datos
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitURL($type, 'ListArticulo&active=List');
    }

    /**
     * Aplicamos correcciones a la tabla.
     */
    public function fixDb()
    {
        $fixes = [
            'DELETE FROM articulosprov WHERE codproveedor NOT IN (SELECT codproveedor FROM proveedores);',
            'UPDATE articulosprov SET refproveedor = referencia WHERE refproveedor IS NULL;',
        ];
        foreach ($fixes as $sql) {
            $this->dataBase->exec($sql);
        }
    }
}
