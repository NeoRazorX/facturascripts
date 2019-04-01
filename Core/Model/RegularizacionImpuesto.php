<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;

/**
 * A VAT regularization.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class RegularizacionImpuesto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuentaacreedora;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuentadeudora;

    /**
     * Date of entry.
     *
     * @var string
     */
    public $fechaasiento;

    /**
     * End date.
     *
     * @var string
     */
    public $fechafin;

    /**
     * Start date.
     *
     * @var string
     */
    public $fechainicio;

    /**
     * ID of the generated accounting entry.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idregularizacion;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuentaacreedora;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuentadeudora;

    /**
     * Period of regularization.
     *
     * @var string
     */
    public $periodo;

    public function clear()
    {
        parent::clear();
        $this->idempresa = AppSettings::get('default', 'idempresa');
    }

    /**
     * Deletes the regularization of VAT from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $asiento = $this->getAsiento();
        if ($asiento->exists()) {
            $asiento->delete();
        }

        return parent::delete();
    }

    /**
     * 
     * @return Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        $asiento->loadFromCode($this->idasiento);
        return $asiento;
    }

    /**
     * Returns the VAT regularization corresponding to that date,
     * that is, the regularization whose start date is earlier
     * to the date provided and its end date is after the date
     * provided. So you can know if the period is still open to be able
     * check in.
     *
     * @param string $fecha
     *
     * @return bool|RegularizacionImpuesto
     */
    public function getFechaInside($fecha)
    {
        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE fechainicio <= ' . self::$dataBase->var2str($fecha)
            . ' AND fechafin >= ' . self::$dataBase->var2str($fecha) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Returns the items per accounting entry.
     *
     * @return Partida[]
     */
    public function getPartidas()
    {
        $asiento = $this->getAsiento();
        return $asiento->getLines();
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Ejercicio();
        new Asiento();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idregularizacion';
    }

    /**
     * Returns the description for model data.
     *
     * @return string
     */
    public function primaryDescription()
    {
        return $this->codejercicio . ' - ' . $this->periodo;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'regularizacionimpuestos';
    }
}
