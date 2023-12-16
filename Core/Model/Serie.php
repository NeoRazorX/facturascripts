<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A series of invoicing or accounting, to have different numbering
 * in each series.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Serie extends ModelClass
{
    use ModelTrait;

    /**
     * @var int
     */
    public $canal;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codserie;

    /**
     * Description of the billing series.
     *
     * @var string
     */
    public $descripcion;

    /**
     * @var int
     */
    public $iddiario;

    /**
     * If associated invoices are without tax True, else False.
     *
     * @var bool
     */
    public $siniva;

    /**
     *
     * @var string
     */
    public $tipo;

    public function clear()
    {
        parent::clear();
        $this->siniva = false;
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-serie');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Series::clear();
            return true;
        }

        return false;
    }

    public function install(): string
    {
        // needed dependencies
        new Diario();

        return parent::install();
    }

    /**
     * Returns True if this is the default serie.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codserie === Tools::settings('default', 'codserie');
    }

    public static function primaryColumn(): string
    {
        return 'codserie';
    }

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Series::clear();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'series';
    }

    public function test(): bool
    {
        $this->codserie = trim($this->codserie);
        if ($this->codserie && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codserie)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codserie, '%column%' => 'codserie', '%min%' => '1', '%max%' => '4']
            );
            return false;
        }

        $this->descripcion = Tools::noHtml($this->descripcion);

        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codserie)) {
            $this->codserie = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
