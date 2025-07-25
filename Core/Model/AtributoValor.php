<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Atributo as DinAtributo;

/**
 * A Value for an article attribute.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AtributoValor extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Code of the related attribute.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Attribute name + value.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Position for visualization and print
     *
     * @var int
     */
    public $orden;

    /**
     * Value of the attribute
     *
     * @var string
     */
    public $valor;

    public function clear()
    {
        parent::clear();
        $this->orden = 100;
    }

    public function codeModelAll(string $fieldCode = ''): array
    {
        $results = [];
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;

        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $this->primaryDescriptionColumn() . ' AS description, codatributo, orden '
            . 'FROM ' . static::tableName() . ' ORDER BY codatributo ASC, orden ASC';
        foreach (self::$dataBase->selectLimit($sql, CodeModel::getlimit()) as $d) {
            $results[] = new CodeModel($d);
        }

        return $results;
    }

    public function getAtributo(): Atributo
    {
        $atributo = new DinAtributo();
        $atributo->loadFromCode($this->codatributo);
        return $atributo;
    }

    public function install(): string
    {
        // needed dependency
        new DinAtributo();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'atributos_valores';
    }

    public function test(): bool
    {
        $this->valor = Tools::noHtml($this->valor);

        // combine attribute name + value
        $attribute = new DinAtributo();
        if ($attribute->loadFromCode($this->codatributo)) {
            $this->descripcion = $attribute->nombre . ' ' . $this->valor;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAtributo'): string
    {
        $value = $this->codatributo;
        switch ($type) {
            case 'edit':
                return null === $value ? 'EditAtributo' : 'EditAtributo?code=' . $value;

            case 'list':
                return $list;

            case 'new':
                return 'EditAtributo';
        }

        // default
        return empty($value) ? $list : 'EditAtributo?code=' . $value;
    }
}
