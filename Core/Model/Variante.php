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

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\CodeModel;

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
        $find = Utils::noHtml(mb_strtolower($query, 'UTF8'));

        $sql = "SELECT v." . $field . " AS code, p.descripcion AS description, v.idatributovalor1, v.idatributovalor2"
            . " FROM " . self::tableName() . " v"
            . " LEFT JOIN " . Producto::tableName() . " p ON v.idproducto = p.idproducto"
            . " WHERE LOWER(v.referencia) LIKE '" . $find . "%'"
            . " OR v.codbarras = '" . $find . "'"
            . " OR LOWER(p.descripcion) LIKE '%" . $find . "%'"
            . " ORDER BY v." . $field . " asc";

        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $data) {
            $this->completeDescription($data['description'], $data['idatributovalor1'], $data['idatributovalor2']);
            $results[] = new CodeModel($data);
        }

        return $results;
    }

    /**
     * 
     * @return string
     */
    public function description()
    {
        $description = $this->getProducto()->descripcion;
        $this->completeDescription($description, $this->idatributovalor1, $this->idatributovalor2);
        return $description;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        $product = $this->getProducto();
        if ($this->referencia == $product->referencia) {
            self::$miniLog->warning(self::$i18n->trans('you-cant-delete-primary-variant'));
            return false;
        }

        return parent::delete();
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
            $product = $this->getProducto();
            $product->update();
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
        $this->codbarras = Utils::noHtml($this->codbarras);
        $this->referencia = Utils::noHtml($this->referencia);

        if (strlen($this->referencia) < 1 || strlen($this->referencia) > 30) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'referencia', '%min%' => '1', '%max%' => '30']));
            return false;
        }

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
     * @param string $description
     * @param int    $idatributoval1
     * @param int    $idatributoval2
     */
    protected function completeDescription(&$description, $idatributoval1, $idatributoval2)
    {
        $atributo_valor = new AtributoValor();
        $extra = '';

        if (!empty($idatributoval1) && $atributo_valor->loadFromCode($idatributoval1)) {
            $extra .= "\n" . $atributo_valor->descripcion;
        }

        if (!empty($idatributoval2) && $atributo_valor->loadFromCode($idatributoval2)) {
            $extra .= empty($extra) ? "\n" : ', ';
            $extra .= $atributo_valor->descripcion;
        }

        $description .= $extra;
    }
}
