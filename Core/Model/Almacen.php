<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;

/**
 * The warehouse where the items are physically.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Almacen extends Base\Address
{

    use Base\ModelTrait;
    use Base\CompanyRelationTrait;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Store name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Store phone number.
     *
     * @var string
     */
    public $telefono;

    public function delete(): bool
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-warehouse');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Almacenes::clear();
            return true;
        }

        return false;
    }

    public function install(): string
    {
        // needed dependencies
        new DinEmpresa();

        return parent::install();
    }

    /**
     * Returns True if this is the default warehouse.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codalmacen === $this->toolBox()->appSettings()->get('default', 'codalmacen');
    }

    public static function primaryColumn(): string
    {
        return 'codalmacen';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Almacenes::clear();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'almacenes';
    }

    public function test(): bool
    {
        if (!empty($this->codalmacen) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codalmacen)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codalmacen, '%column%' => 'codalmacen', '%min%' => '1', '%max%' => '4']
            );
            return false;
        }

        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        $utils = $this->toolBox()->utils();
        $this->nombre = $utils->noHtml($this->nombre);
        $this->telefono = $utils->noHtml($this->telefono);
        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codalmacen)) {
            $this->codalmacen = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
