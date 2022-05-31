<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Retenciones;

/**
 * Class to manage the data of retenciones table
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar        <rafael.sanjose@x-netdigital.com>
 */
class Retencion extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. varchar(10).
     *
     * @var string
     */
    public $codretencion;

    /**
     * @var string
     */
    public $codsubcuentaret;

    /**
     * @var string
     */
    public $codsubcuentaacr;

    /**
     * Description of the tax.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Percent of the retention
     *
     * @var int
     */
    public $porcentaje;

    public function clear()
    {
        parent::clear();
        $this->porcentaje = 0.0;
    }

    public function delete(): bool
    {
        if (parent::delete()) {
            // limpiamos la caché
            Retenciones::clear();
            return true;
        }

        return false;
    }

    public function loadFromPercentage(float $percentaje): bool
    {
        $where = [new DataBaseWhere('porcentaje', $percentaje)];
        $order = ['codretencion' => 'ASC'];
        return $this->loadFromCode('', $where, $order);
    }

    public static function primaryColumn(): string
    {
        return 'codretencion';
    }

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Retenciones::clear();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'retenciones';
    }

    public function test(): bool
    {
        $this->codretencion = trim($this->codretencion);
        if ($this->codretencion && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codretencion)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codretencion, '%column%' => 'codretencion', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        $this->codsubcuentaret = empty($this->codsubcuentaret) ? null : $this->codsubcuentaret;
        $this->codsubcuentaacr = empty($this->codsubcuentaacr) ? null : $this->codsubcuentaacr;
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);

        if (empty($this->porcentaje) || intval($this->porcentaje) < 1) {
            $this->toolBox()->i18nLog()->warning('not-valid-percentage-retention');
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListImpuesto?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codretencion)) {
            $this->codretencion = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
