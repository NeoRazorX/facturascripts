<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

/**
 * A province.
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class Provincia extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $alias;

    /** @var string */
    public $creation_date;

    /** @var string */
    public $codeid;

    /**
     * 'Normalized' code in Spain to identify the provinces.
     *
     * @url: https://es.wikipedia.org/wiki/Provincia_de_España#Denominaci.C3.B3n_y_lista_de_las_provincias
     *
     * @var string
     */
    public $codisoprov;

    /** @var string */
    public $codpais;

    /** @var string */
    public $idprovincia;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var float */
    public $latitude;

    /** @var float */
    public $longitude;

    /** @var string */
    public $nick;

    /** @var string */
    public $provincia;

    /** @var string */
    public $telephone_prefix;

    public function clear()
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais');
    }

    public function getCountry(): Pais
    {
        $country = new Pais();
        $country->loadFromCode($this->codpais);
        return $country;
    }

    public function install(): string
    {
        // needed dependencies
        new Pais();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idprovincia';
    }

    public static function tableName(): string
    {
        return 'provincias';
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->alias = Tools::noHtml($this->alias);
        $this->provincia = Tools::noHtml($this->provincia);
        $this->telephone_prefix = Tools::noHtml($this->telephone_prefix);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        if ('list' === $type && !empty($this->primaryColumnValue())) {
            return $this->getCountry()->url() . '&activetab=List' . $this->modelClassName();
        }

        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->idprovincia)) {
            // asignamos el nuevo ID así para evitar problemas con postgresql por haber importado el listado con ids incluidos
            $this->idprovincia = $this->newCode();
        }

        return parent::saveInsert($values);
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();
        return parent::saveUpdate($values);
    }
}
