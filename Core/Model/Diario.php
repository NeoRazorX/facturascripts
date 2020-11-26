<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * A division of acounting entries in different journals
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class Diario extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Description of journal.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $iddiario;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'iddiario';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'diarios';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        if (\strlen($this->descripcion) < 1 || \strlen($this->descripcion) > 100) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'description', '%min%' => '1', '%max%' => '100']);
            return false;
        }

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
        if (empty($this->iddiario)) {
            $this->iddiario = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
