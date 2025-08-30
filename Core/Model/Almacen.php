<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * The warehouse where the items are physically.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Almacen extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;

    /** @var bool */
    public $activo;

    /** @var string */
    public $apartado;

    /** @var string */
    public $ciudad;

    /** @var string */
    public $codalmacen;

    /** @var string */
    public $codpais;

    /** @var string */
    public $codpostal;

    /** @var string */
    public $direccion;

    /** @var string */
    public $nombre;

    /** @var string */
    public $provincia;

    /** @var string */
    public $telefono;

    public function clear(): void
    {
        parent::clear();
        $this->activo = true;
        $this->codpais = Tools::settings('default', 'codpais');
    }

    public function clearCache(): void
    {
        parent::clearCache();
        Almacenes::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-warehouse');
            return false;
        }

        return parent::delete();
    }

    public function install(): string
    {
        // needed dependencies
        new Empresa();
        new Pais();

        return parent::install();
    }

    /**
     * Returns True if this is the default warehouse.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codalmacen === Tools::settings('default', 'codalmacen');
    }

    public static function primaryColumn(): string
    {
        return 'codalmacen';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'almacenes';
    }

    public function test(): bool
    {
        $this->nombre = Tools::noHtml($this->nombre);
        $this->telefono = Tools::noHtml($this->telefono);

        if (!empty($this->codalmacen) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codalmacen)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codalmacen, '%column%' => 'codalmacen', '%min%' => '1', '%max%' => '4']
            );
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codalmacen)) {
            $this->codalmacen = (string)$this->newCode();
        }

        return parent::saveInsert();
    }
}
