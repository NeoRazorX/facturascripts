<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Model to personalize the print format of sales and buy documents.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class FormatoDocumento extends ModelClass
{
    use ModelTrait;

    /**
     * @var bool
     */
    public $autoaplicar;

    /**
     * Foreign key with series table
     *
     * @var string
     */
    public $codserie;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Foreign key with table business
     *
     * @var int
     */
    public $idempresa;

    /**
     * @var int
     */
    public $idlogo;

    /**
     * @var string
     */
    public $nombre;

    /**
     * @var string
     */
    public $texto;

    /**
     * @var string
     */
    public $tipodoc;

    /**
     * @var string
     */
    public $titulo;

    public function clear()
    {
        parent::clear();
        $this->autoaplicar = true;
    }

    public function install(): string
    {
        // needed dependencies
        new Serie();
        new Empresa();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'formatos_documentos';
    }

    public function test(): bool
    {
        $this->nombre = empty($this->nombre) ? Tools::noHtml($this->titulo) : Tools::noHtml($this->nombre);
        $this->texto = Tools::noHtml($this->texto);
        $this->titulo = Tools::noHtml($this->titulo);

        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
