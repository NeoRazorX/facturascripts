<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\DataSrc\Provincias;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
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

    /** @var string Nombre alternativo o alias de la provincia. */
    public $alias;

    /** @var string Código geográfico identificativo de la provincia. */
    public $codeid;

    /** @var string Código normalizado utilizado para identificar la provincia. */
    public $codisoprov;

    /** @var string Código del país al que pertenece la provincia. */
    public $codpais;

    /** @var string Fecha y hora de creación de la provincia. */
    public $creation_date;

    /** @var string Identificador único de la provincia. */
    public $idprovincia;

    /** @var string Nombre del último usuario que modificó la provincia. */
    public $last_nick;

    /** @var string Fecha y hora de la última modificación. */
    public $last_update;

    /** @var float Latitud geográfica de referencia de la provincia. */
    public $latitude;

    /** @var float Longitud geográfica de referencia de la provincia. */
    public $longitude;

    /** @var string Nombre del usuario que creó la provincia. */
    public $nick;

    /** @var string Nombre de la provincia. */
    public $provincia;

    /** @var string Prefijo telefónico de la provincia. */
    public $telephone_prefix;

    public function clear(): void
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais');
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Provincias::clear();
    }

    public function getCities(): array
    {
        return $this->hasMany(Ciudad::class, 'idprovincia');
    }

    public function getCountry(): Pais
    {
        return Paises::get($this->codpais);
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
        if ('list' === $type && !empty($this->id())) {
            return $this->getCountry()->url() . '&activetab=List' . $this->modelClassName();
        }

        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        if (empty($this->idprovincia)) {
            // asignamos el nuevo ID así para evitar problemas con postgresql por haber importado el listado con ids incluidos
            $this->idprovincia = $this->newCode();
        }

        return parent::saveInsert();
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        return parent::saveUpdate();
    }
}
