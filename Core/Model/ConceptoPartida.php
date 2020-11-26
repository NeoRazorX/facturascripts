<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * A predefined concept for a line item (the line of an accounting entry).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ConceptoPartida extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codconcepto;

    /**
     * Concept of departure.
     *
     * @var string
     */
    public $concepto;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codconcepto';
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'codconcepto';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'conceptos_partidas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = $this->toolBox()->utils()->noHtml($this->concepto);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListAsiento?activetab=List')
    {
        return parent::url($type, $list);
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codconcepto)) {
            $this->codconcepto = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
