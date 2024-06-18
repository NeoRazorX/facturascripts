<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos García Gómez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ProductType;
use FacturaScripts\Dinamic\Model\AtributoValor as DinAtributoValor;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\ProductoImagen as DinProductoImagen;
use FacturaScripts\Dinamic\Model\Stock as DinStock;

/**
 * Define method and attributes of table variantes.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class Variante extends Base\ModelClass
{
    use Base\ModelTrait;
    use Base\ProductRelationTrait;

    /**
     * Barcode. Maximum 20 characters.
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
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor3;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor4;

    /**
     * Primary Key, autoincremental.
     *
     * @var int
     */
    public $idvariante;

    /**
     * @var float
     */
    public $margen;

    /**
     * Price of the variant. Without tax.
     *
     * @var int|float
     */
    public $precio;

    /**
     * Reference of the variant. Maximum 30 characters.
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

    public function clear()
    {
        parent::clear();
        $this->coste = 0.0;
        $this->margen = 0.0;
        $this->precio = 0.0;
        $this->stockfis = 0.0;
    }

    /**
     * @param string $query
     * @param string $fieldCode
     * @param DataBaseWhere[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $results = [];
        $field = empty($fieldCode) ? $this->primaryColumn() : $fieldCode;
        $find = Tools::noHtml(mb_strtolower($query, 'UTF8'));

        // añadimos opciones al inicio del where
        array_unshift(
            $where,
            new DataBaseWhere('LOWER(v.referencia)', $find . '%', 'LIKE'),
            new DataBaseWhere('LOWER(v.referencia)', '%' . $find, 'LIKE', 'OR'),
            new DataBaseWhere('LOWER(v.codbarras)', $find, '=', 'OR'),
            new DataBaseWhere('LOWER(p.descripcion)', $find, 'LIKE', 'OR')
        );

        $sql = "SELECT v." . $field . " AS code, p.descripcion AS description, v.idatributovalor1, v.idatributovalor2, v.idatributovalor3, v.idatributovalor4"
            . " FROM " . static::tableName() . " v"
            . " LEFT JOIN " . DinProducto::tableName() . " p ON v.idproducto = p.idproducto"
            . DataBaseWhere::getSQLWhere($where)
            . " ORDER BY v." . $field . " ASC";

        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $data) {
            $data['description'] = $this->getAttributeDescription(
                $data['idatributovalor1'],
                $data['idatributovalor2'],
                $data['idatributovalor3'],
                $data['idatributovalor4'],
                $data['description']
            );
            $results[] = new CodeModel($data);
        }

        return $results;
    }

    public function description(bool $onlyAttributes = false): string
    {
        $pipeReturn = $this->pipe('description', $onlyAttributes);
        if ($pipeReturn) {
            return $pipeReturn;
        }

        $description = $onlyAttributes ? '' : $this->getProducto()->descripcion;
        return $this->getAttributeDescription(
            $this->idatributovalor1,
            $this->idatributovalor2,
            $this->idatributovalor3,
            $this->idatributovalor4,
            $description
        );
    }

    public function delete(): bool
    {
        // no se puede eliminar la variante principal
        if ($this->referencia === $this->getProducto()->referencia) {
            Tools::log()->warning('you-cant-delete-primary-variant');
            return false;
        }

        // no podemos eliminar la variante si hay documentos relacionados
        if ($this->isInDocuments()) {
            Tools::log()->warning('cant-delete-variant-with-documents', ['%reference%' => $this->referencia]);
            return false;
        }

        // eliminamos las imágenes de la variante
        foreach ($this->getImages(false) as $image) {
            $image->delete();
        }

        // eliminamos el registro de la base de datos
        return parent::delete();
    }

    /**
     * @param int $idAttVal1
     * @param int $idAttVal2
     * @param int $idAttVal3
     * @param int $idAttVal4
     * @param string $description
     * @param string $separator1
     * @param string $separator2
     *
     * @return string
     */
    protected function getAttributeDescription($idAttVal1, $idAttVal2, $idAttVal3, $idAttVal4, $description = '', $separator1 = "\n", $separator2 = ', '): string
    {
        $pipeReturn = $this->pipe('getAttributeDescription', $idAttVal1, $idAttVal2, $idAttVal3, $idAttVal4, $description, $separator1, $separator2);
        if ($pipeReturn) {
            return $pipeReturn;
        }

        // obtenemos las descripciones de los atributos
        $attributeValue = new DinAtributoValor();
        $attDesc = [];
        foreach ([$idAttVal1, $idAttVal2, $idAttVal3, $idAttVal4] as $id) {
            if (!empty($id) && $attributeValue->loadFromCode($id)) {
                $attribute = $attributeValue->getAtributo();
                $attDesc[] = [
                    'position' => empty($attribute->num_selector) ? 99 : $attribute->num_selector,
                    'value' => $attributeValue->descripcion
                ];
            }
        }

        // ordenamos por posición
        usort($attDesc, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        $extra = [];
        foreach ($attDesc as $item) {
            $extra[] = $item['value'];
        }

        // devolvemos la descripción
        if (empty($description)) {
            return implode($separator2, $extra);
        }

        // combinamos la descripción con los atributos
        return empty($extra) ? $description : implode($separator1, [$description, implode($separator2, $extra)]);
    }

    /**
     * @param bool $imgProduct
     *
     * @return ProductoImagen[]
     */
    public function getImages(bool $imgProduct = true): array
    {
        // buscamos las imágenes propias de esta variante
        $image = new DinProductoImagen();
        $whereVar = [new DataBaseWhere('referencia', $this->referencia)];
        $orderBy = ['orden' => 'ASC'];
        $images = $image->all($whereVar, $orderBy, 0, 0);

        // si solo queremos las imágenes de la variante, terminamos
        if (false === $imgProduct) {
            return $images;
        }

        // añadimos las imágenes del producto para todas las variantes
        $whereProd = [
            new DataBaseWhere('idproducto', $this->idproducto),
            new DataBaseWhere('referencia', null, 'IS')
        ];
        return array_merge($images, $image->all($whereProd, $orderBy, 0, 0));
    }

    public function install(): string
    {
        new DinProducto();
        new DinAtributoValor();

        return parent::install();
    }

    public function isInDocuments(): bool
    {
        $tables = [
            'lineasalbaranescli', 'lineasalbaranesprov', 'lineasfacturascli', 'lineasfacturasprov',
            'lineaspedidoscli', 'lineaspedidosprov', 'lineaspresupuestoscli', 'lineaspresupuestosprov'
        ];
        foreach ($tables as $table) {
            $count = DbQuery::table($table)
                ->whereEq('referencia', $this->referencia)
                ->count();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    public function priceWithTax(): float
    {
        $product = $this->getProducto();

        if ($product->tipo === ProductType::SECOND_HAND) {
            $diff = $this->precio - $this->coste;
            $newPrice = $this->precio + ($diff * $product->getTax()->iva / 100);
            return round($newPrice, DinProducto::ROUND_DECIMALS);
        }

        $newPrice = $this->precio * (100 + $product->getTax()->iva) / 100;
        return round($newPrice, DinProducto::ROUND_DECIMALS);
    }

    public static function primaryColumn(): string
    {
        return 'idvariante';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'referencia';
    }

    public function save(): bool
    {
        $this->precio = $this->precio ?: 0.0;
        $this->coste = $this->coste ?: 0.0;
        $this->margen = $this->margen ?: 0.0;

        if ($this->margen > 0) {
            $newPrice = $this->coste * (100 + $this->margen) / 100;
            $this->precio = round($newPrice, DinProducto::ROUND_DECIMALS);
        }

        if (parent::save()) {
            $this->getProducto()->update();
            return true;
        }

        return false;
    }

    public function setPriceWithTax(float $price): void
    {
        $product = $this->getProducto();
        $this->margen = 0;

        if ($product->tipo === ProductType::SECOND_HAND) {
            $price -= $this->coste;
            $newPrice = $this->coste + (100 * $price) / (100 + $product->getTax()->iva);
            $this->precio = round($newPrice, DinProducto::ROUND_DECIMALS);
            return;
        }

        $newPrice = (100 * $price) / (100 + $product->getTax()->iva);
        $this->precio = round($newPrice, DinProducto::ROUND_DECIMALS);
    }

    public static function tableName(): string
    {
        return 'variantes';
    }

    public function test(): bool
    {
        $this->codbarras = Tools::noHtml($this->codbarras);
        $this->referencia = Tools::noHtml($this->referencia);

        if (empty($this->referencia)) {
            $this->referencia = (string)$this->newCode('referencia');
        }
        if (strlen($this->referencia) > 30) {
            Tools::log()->warning(
                'invalid-column-lenght',
                ['%value%' => $this->referencia, '%column%' => 'referencia', '%min%' => '1', '%max%' => '30']
            );
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getProducto()->url($type);
    }

    protected function saveInsert(array $values = []): bool
    {
        // comprobamos si la referencia ya existe
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        if ($this->count($where) > 0) {
            Tools::log()->warning('duplicated-reference', ['%reference%' => $this->referencia]);
            return false;
        }

        if (false === parent::saveInsert($values)) {
            return false;
        }

        // establecemos el nuevo stock
        if ($this->stockfis != 0.0) {
            $stock = new DinStock();
            $stock->cantidad = $this->stockfis;
            $stock->codalmacen = Tools::settings('default', 'codalmacen');
            $stock->idproducto = $this->idproducto;
            $stock->referencia = $this->referencia;
            $stock->save();
        }

        return true;
    }
}
