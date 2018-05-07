<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * A VAT regularization.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RegularizacionImpuestos extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idregularizacion;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Period of regularization.
     *
     * @var
     */
    public $periodo;

    /**
     * Start date.
     *
     * @var string
     */
    public $fechainicio;

    /**
     * End date.
     *
     * @var string
     */
    public $fechafin;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuentaacreedora;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuentaacreedora;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuentadeudora;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuentadeudora;

    /**
     * ID of the generated accounting entry.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Date of entry.
     *
     * @var string
     */
    public $fechaasiento;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'regularizacionimpuestos';
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
     * Returns the items per accounting entry.
     *
     * @return Partida[]|bool
     */
    public function getPartidas()
    {
        if ($this->idasiento !== null) {
            $partida = new Partida();

            return $partida->all([new DataBaseWhere('idasiento', $this->idasiento)]);
        }

        return false;
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
     * @return bool|RegularizacionImpuestos
     */
    public function getFechaInside($fecha)
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE fechainicio <= ' . self::$dataBase->var2str($fecha)
            . ' AND fechafin >= ' . self::$dataBase->var2str($fecha) . ';';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Deletes the regularization of VAT from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName()
            . ' WHERE idregularizacion = ' . self::$dataBase->var2str($this->idregularizacion) . ';';
        if (self::$dataBase->exec($sql)) {
            /// si hay un asiento asociado lo eliminamos
            if ($this->idasiento !== null) {
                $asientoModel = new Asiento();
                $asiento = $asientoModel->get($this->idasiento);
                if ($asiento) {
                    $asiento->delete();
                }
            }

            return true;
        }

        return false;
    }
}
