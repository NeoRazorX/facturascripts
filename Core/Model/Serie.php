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

/**
 * Una serie de facturación o contabilidad, para tener distinta numeración
 * en cada serie.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Serie
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar (2).
     *
     * @var string
     */
    public $codserie;

    /**
     * Descripción de la serie de facturación
     *
     * @var string
     */
    public $descripcion;

    /**
     * If associated invoices are without tax True, else False.
     *
     * @var bool
     */
    public $siniva;

    /**
     * % de retención IRPF de las facturas asociadas.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * ejercicio para el que asignamos la numeración inicial de la serie.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * numeración inicial para las facturas de esta serie.
     *
     * @var int
     */
    public $numfactura;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'series';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codserie';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->codserie = '';
        $this->descripcion = '';
        $this->siniva = false;
        $this->irpf = 0.0;
        $this->codejercicio = null;
        $this->numfactura = 1;
    }

    /**
     * Returns True if is the default serie for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codserie === AppSettings::get('default', 'codserie');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codserie = trim($this->codserie);
        $this->descripcion = self::noHtml($this->descripcion);

        if ($this->numfactura < 1) {
            $this->numfactura = 1;
        }

        if (!preg_match('/^[A-Z0-9]{1,4}$/i', $this->codserie)) {
            $this->miniLog->alert($this->i18n->trans('serie-cod-invalid'));
        } elseif (!(strlen($this->descripcion) > 1) && !(strlen($this->descripcion) < 100)) {
            $this->miniLog->alert($this->i18n->trans('serie-desc-invalid'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Crea la consulta necesaria para crear una nueva serie en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . static::tableName() . ' (codserie,descripcion,siniva,irpf) VALUES '
            . "('A','SERIE A',false,'0'),('R','RECTIFICATIVAS',false,'0');";
    }
}
