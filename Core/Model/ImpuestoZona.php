<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;

/**
 * A tax (VAT) that can be associated to tax, country, province, and.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar        <rafael.sanjose@x-netdigital.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class ImpuestoZona extends ModelClass
{
    use ModelTrait;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuestosel;

    /**
     * Foreign key with provincias table. varchar(10).
     *
     * @var string
     */
    public $codisopro;

    /**
     * Foreign key with country table. varchar(20).
     *
     * @var string
     */
    public $codpais;

    /**
     * Primary key autoincremental
     *
     * @var int
     */
    public $id;

    /**
     * Priority of taxt by zone.
     *
     * @var int
     */
    public $prioridad;

    /**
     *
     * @var string
     */
    protected $provincia;

    public function clear()
    {
        parent::clear();
        $this->codimpuesto = Tools::settings('default', 'codimpuesto');
        $this->codpais = Tools::settings('default', 'codpais');
        $this->prioridad = 1;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function provincia(): ?string
    {
        if (!isset($this->provincia)) {
            $provincia = new Provincia();
            $provincia->loadFromCode($this->codisopro);
            $this->provincia = $provincia->provincia;
        }

        return $this->provincia;
    }

    public static function tableName(): string
    {
        return 'impuestoszonas';
    }
}
