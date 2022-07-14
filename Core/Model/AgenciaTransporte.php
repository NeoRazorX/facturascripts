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

/**
 * Merchandise transport agency.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AgenciaTransporte extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Contains True if is enabled.
     *
     * @var bool
     */
    public $activo;

    /**
     * Primary key. Varchar(8).
     *
     * @var string
     */
    public $codtrans;

    /**
     * Name of the agency.
     *
     * @var string
     */
    public $nombre;

    /**
     * @var string
     */
    public $telefono;

    /**
     * @var string
     */
    public $web;

    public function clear()
    {
        parent::clear();
        $this->activo = true;
    }

    public static function primaryColumn(): string
    {
        return 'codtrans';
    }

    public static function tableName(): string
    {
        return 'agenciastrans';
    }

    public function test(): bool
    {
        if (!empty($this->codtrans) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codtrans)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codtrans, '%column%' => 'codtrans', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        $utils = $this->toolBox()->utils();
        $this->nombre = $utils->noHtml($this->nombre);
        $this->telefono = $utils->noHtml($this->telefono);
        $this->web = $utils->noHtml($this->web);

        // check if the web is a valid url
        if (!empty($this->web) && false === self::toolBox()::utils()::isValidUrl($this->web)) {
            self::toolBox()::i18nLog()->error('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codtrans)) {
            $this->codtrans = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
