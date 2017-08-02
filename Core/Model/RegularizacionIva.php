<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * Una regularización de IVA.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class RegularizacionIva
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var int
     */
    public $idregiva;

    /**
     * ID del asiento generado.
     * @var int
     */
    public $idasiento;

    /**
     * TODO
     * @var string
     */
    public $codejercicio;

    /**
     * TODO
     * @var string
     */
    public $fechaasiento;

    /**
     * TODO
     * @var string
     */
    public $fechafin;

    /**
     * TODO
     * @var string
     */
    public $fechainicio;

    /**
     * TODO
     * @var
     */
    public $periodo;

    /**
     * RegularizacionIva constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init('co_regiva', 'idregiva');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->idregiva === null) {
            return 'index.php?page=ContabilidadRegusiva';
        }
        return 'index.php?page=ContabilidadRegusiva&id=' . $this->idregiva;
    }

    /**
     * TODO
     * @return string
     */
    public function asientoUrl()
    {
        if ($this->idasiento === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasiento;
    }

    /**
     * TODO
     * @return string
     */
    public function ejercicioUrl()
    {
        if ($this->codejercicio === null) {
            return 'index.php?page=ContabilidadEjercicios';
        }
        return 'index.php?page=ContabilidadEjercicio&cod=' . $this->codejercicio;
    }

    /**
     * TODO
     * @return array|bool
     */
    public function getPartidas()
    {
        if ($this->idasiento !== null) {
            $partida = new Partida();
            return $partida->allFromAsiento($this->idasiento);
        }
        return false;
    }

    /**
     * Devuelve la regularización de IVA correspondiente a esa fecha,
     * es decir, la regularización cuya fecha de inicio sea anterior
     * a la fecha proporcionada y su fecha de fin sea posterior a la fecha
     * proporcionada. Así puedes saber si el periodo sigue abierto para poder
     * facturar.
     *
     * @param string $fecha
     *
     * @return bool|RegularizacionIva
     */
    public function getFechaInside($fecha)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fechainicio <= ' . $this->var2str($fecha)
            . ' AND fechafin >= ' . $this->var2str($fecha) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new RegularizacionIva($data[0]);
        }
        return false;
    }

    /**
     * TODO
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idregiva = ' . $this->var2str($this->idregiva) . ';';
        if ($this->dataBase->exec($sql)) {
            /// si hay un asiento asociado lo eliminamos
            if ($this->idasiento !== null) {
                $asiento = new Asiento();
                $as0 = $asiento->get($this->idasiento);
                if ($as0) {
                    $as0->delete();
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Devuelve todas las regularizaciones del ejercicio.
     *
     * @param string $codejercicio
     *
     * @return array
     */
    public function allFromEjercicio($codejercicio)
    {
        $reglist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio)
            . ' ORDER BY fechafin DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $r) {
                $reglist[] = new RegularizacionIva($r);
            }
        }

        return $reglist;
    }
}
