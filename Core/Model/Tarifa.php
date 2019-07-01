<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * A fee for the products.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Tarifa extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Formula to apply.
     *
     * @var
     */
    public $aplicar;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Do not sell above retail price.
     *
     * @var bool
     */
    public $maxpvp;

    /**
     * Do not sell below cost.
     *
     * @var bool
     */
    public $mincoste;

    /**
     * Name of the rate.
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var float
     */
    public $valorx;

    /**
     *
     * @var float
     */
    public $valory;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->aplicar = 'pvp';
        $this->maxpvp = false;
        $this->mincoste = false;
        $this->valorx = 0.0;
        $this->valory = 0.0;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codtarifa';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'tarifas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codtarifa = trim($this->codtarifa);
        $this->nombre = Utils::noHtml($this->nombre);

        if (empty($this->codtarifa) || strlen($this->codtarifa) > 6) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'codtarifa', '%min%' => '1', '%max%' => '6']));
        } elseif (empty($this->nombre) || strlen($this->nombre) > 50) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'nombre', '%min%' => '1', '%max%' => '50']));
        } else {
            return true;
        }

        return false;
    }
}
