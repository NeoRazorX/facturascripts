<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos García Gómez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

/**
 * Define method and attributes of table variantes.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class Variante extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Barcode. Maximun 20 characteres.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Cost price.
     *
     * @var int|float
     */
    public $coste;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor1;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor2;

    /**
     * Product identifier.
     *
     * @var int
     */
    public $idproducto;

    /**
     * Primary Key, autoincremental.
     *
     * @var int
     */
    public $idvariante;

    /**
     * Price of the variant. Without tax.
     *
     * @var int|float
     */
    public $precio;

    /**
     * Reference of the variant. Maximun 30 characteres.
     *
     * @var string
     */
    public $referencia;

    /**
     * Physical stock.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * Sets default values.
     */
    public function clear()
    {
        parent::clear();
        $this->coste = 0.0;
        $this->precio = 0.0;
        $this->stockfis = 0.0;
    }

    /**
     * 
     * @param string $query
     * @param string $fieldcode
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldcode = '')
    {
        $results = [];
        $field = empty($fieldcode) ? $this->primaryColumn() : $fieldcode;
        $find = $this->toolBox()->utils()->noHtml(mb_strtolower($query, 'UTF8'));

        $sql = "SELECT v." . $field . " AS code, p.descripcion AS description, v.idatributovalor1, v.idatributovalor2"
            . " FROM " . self::tableName() . " v"
            . " LEFT JOIN " . Producto::tableName() . " p ON v.idproducto = p.idproducto"
            . " WHERE LOWER(v.referencia) LIKE '" . $find . "%'"
            . " OR v.codbarras = '" . $find . "'"
            . " OR LOWER(p.descripcion) LIKE '%" . $find . "%'"
            . " ORDER BY v." . $field . " asc";

        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $data) {
            $data['description'] = $this->getAttributeDescription($data['idatributovalor1'], $data['idatributovalor2'], $data['description']);
            $results[] = new CodeModel($data);
        }

        return $results;
    }

    /**
     * 
     * @return string
     */
    public function description(bool $onlyAttributes = false)
    {
        $description = $onlyAttributes ? '' : $this->getProducto()->descripcion;
        return $this->getAttributeDescription($this->idatributovalor1, $this->idatributovalor2, $description);
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        $product = $this->getProducto();
        if ($this->referencia == $product->referencia) {
            $this->toolBox()->i18nLog()->warning('you-cant-delete-primary-variant');
            return false;
        }

        return parent::delete();
    }

    /**
     * 
     * @param int    $idatributoval1
     * @param int    $idatributoval2
     * @param string $description
     * @param string $separator1
     * @param string $separator2
     *
     * @return string
     */
    protected function getAttributeDescription($idatributoval1, $idatributoval2, $description = '', $separator1 = "\n", $separator2 = ', ')
    {
        $atributeValue = new AtributoValor();
        $extra = [];
        foreach ([$idatributoval1, $idatributoval2] as $id) {
            if (!empty($id) && $atributeValue->loadFromCode($id)) {
                $extra[] = $atributeValue->descripcion;
            }
        }

        /// compose text
        if (empty($description)) {
            return implode($separator2, $extra);
        }

        return empty($extra) ? $description : implode($separator1, [$description, implode($separator2, $extra)]);
    }

    /**
     * Returns related product.
     *
     * @return Producto
     */
    public function getProducto()
    {
        $producto = new Producto();
        $producto->loadFromCode($this->idproducto);
        return $producto;
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
        new Producto();
        new AtributoValor();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idvariante';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'referencia';
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (parent::save()) {
            $this->getProducto()->update();
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'variantes';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->referencia = $utils->noHtml($this->referencia);
        if (strlen($this->referencia) < 1 || strlen($this->referencia) > 30) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%value%' => $this->referencia, '%column%' => 'referencia', '%min%' => '1', '%max%' => '30']
            );
            return false;
        }

        $this->codbarras = $utils->noHtml($this->codbarras);
        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        switch ($type) {
            case 'edit':
                return is_null($this->idproducto) ? 'EditProducto' : 'EditProducto?code=' . $this->idproducto;

            case 'list':
                return $list . 'Producto';

            case 'new':
                return 'EditProducto';
        }

        /// default
        return empty($this->idproducto) ? $list . 'Producto' : 'EditProducto?code=' . $this->idproducto;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            /// set new stock?
            if ($this->stockfis != 0.0) {
                $stock = new Stock();
                $stock->cantidad = $this->stockfis;
                $stock->codalmacen = $this->toolBox()->appSettings()->get('default', 'codalmacen');
                $stock->idproducto = $this->idproducto;
                $stock->referencia = $this->referencia;
                $stock->save();
            }

            return true;
        }

        return false;
    }
}
