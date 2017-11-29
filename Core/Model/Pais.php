<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * Un país, por ejemplo España.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Pais
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Varchar(3).
     *
     * @var string Código alfa-3 del país.
     *             http://es.wikipedia.org/wiki/ISO_3166-1
     */
    public $codpais;

    /**
     * Código alfa-2 del país.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     *
     * @var string
     */
    public $codiso;

    /**
     * Nombre del pais.
     *
     * @var string
     */
    public $nombre;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'paises';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codpais';
    }

    /**
     * Devuelve True si el pais es el predeterminado de la empresa
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpais === AppSettings::get('default', 'codpais');
    }

    /**
     * Comprueba los datos del pais, devuelve True si son correctos
     *
     * @return bool
     */
    public function test()
    {
        $this->codpais = trim($this->codpais);
        $this->nombre = self::noHtml($this->nombre);

        if (!preg_match('/^[A-Z0-9]{1,20}$/i', $this->codpais)) {
            $this->miniLog->alert($this->i18n->trans('country-cod-invalid', [$this->codpais]));

            return false;
        }

        if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            $this->miniLog->alert($this->i18n->trans('country-name-invalid'));

            return false;
        }

        return true;
    }

    /**
     * Crea la consulta necesaria para crear los paises en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return CSVImport::importTableSQL($this->tableName());
    }
}
