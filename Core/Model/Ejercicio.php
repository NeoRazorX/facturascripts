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

/**
 * Ejercicio contable. Es el periodo en el que se agrupan asientos, facturas, albaranes...
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Ejercicio
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Varchar(4).
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Nombre del ejercicio
     *
     * @var string
     */
    public $nombre;

    /**
     * Fecha de inicio del ejercicio
     *
     * @var string con formato fecha
     */
    public $fechainicio;

    /**
     * Fecha de fin del ejercicio
     *
     * @var string con formato fecha
     */
    public $fechafin;

    /**
     * Estado del ejercicio: ABIERTO|CERRADO
     *
     * @var string
     */
    public $estado;

    /**
     * ID del asiento de cierre del ejercicio.
     *
     * @var int
     */
    public $idasientocierre;

    /**
     * ID del asiento de pérdidas y ganancias.
     *
     * @var int
     */
    public $idasientopyg;

    /**
     * ID del asiento de apertura.
     *
     * @var int
     */
    public $idasientoapertura;

    /**
     * Identifica el plan contable utilizado. Esto solamente es necesario
     * para dar compatibilidad con Eneboo. En FacturaScripts no se utiliza.
     *
     * @var string
     */
    public $plancontable;

    /**
     * Longitud de caracteres de las subcuentas asignadas.
     *
     * @var int
     */
    public $longsubcuenta;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'ejercicios';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codejercicio';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codejercicio = null;
        $this->nombre = '';
        $this->fechainicio = date('01-01-Y');
        $this->fechafin = date('31-12-Y');
        $this->estado = 'ABIERTO';
        $this->idasientocierre = null;
        $this->idasientopyg = null;
        $this->idasientoapertura = null;
        $this->plancontable = '08';
        $this->longsubcuenta = 10;
    }

    /**
     * Devuelve el estado del ejercicio ABIERTO->true | CERRADO->false
     *
     * @return bool
     */
    public function abierto()
    {
        return $this->estado === 'ABIERTO';
    }

    /**
     * Devuelve el valos del año del ejercicio
     *
     * @return string en formato año
     */
    public function year()
    {
        return date('Y', strtotime($this->fechainicio));
    }

    /**
     * Devuelve un nuevo código para un ejercicio
     *
     * @param string $cod
     *
     * @return string
     */
    public function newCodigo($cod = '0001')
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->dataBase->var2str($cod) . ';';
        if (!$this->dataBase->select($sql)) {
            return $cod;
        }

        $sql = 'SELECT MAX(' . $this->dataBase->sql2Int('codejercicio') . ') as cod FROM ' . $this->tableName() . ';';
        $newCod = $this->dataBase->select($sql);
        if (!empty($newCod)) {
            return sprintf('%04s', 1 + (int) $newCod[0]['cod']);
        }

        return '0001';
    }

    /**
     * Devuelve la fecha más próxima a $fecha que esté dentro del intervalo de este ejercicio
     *
     * @param string $fecha
     * @param bool   $showError
     *
     * @return string
     */
    public function getBestFecha($fecha, $showError = false)
    {
        $fecha2 = strtotime($fecha);

        if ($fecha2 >= strtotime($this->fechainicio) && $fecha2 <= strtotime($this->fechafin)) {
            return $fecha;
        }

        if ($fecha2 > strtotime($this->fechainicio)) {
            if ($showError) {
                $this->miniLog->alert($this->i18n->trans('date-out-of-rage-selected-better'));
            }

            return $this->fechafin;
        }

        if ($showError) {
            $this->miniLog->alert($this->i18n->trans('date-out-of-rage-selected-better'));
        }

        return $this->fechainicio;
    }

    /**
     * Devuelve el ejercicio para la fecha indicada.
     * Si no existe, lo crea.
     *
     * @param string $fecha
     * @param bool   $soloAbierto
     * @param bool   $crear
     *
     * @return bool|ejercicio
     */
    public function getByFecha($fecha, $soloAbierto = true, $crear = true)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fechainicio <= '
            . $this->dataBase->var2str($fecha) . ' AND fechafin >= ' . $this->dataBase->var2str($fecha) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $eje = new self($data[0]);
            if ($eje->abierto() || !$soloAbierto) {
                return $eje;
            }
        } elseif ($crear) {
            $eje = new self();
            $eje->codejercicio = $eje->newCodigo(date('Y', strtotime($fecha)));
            $eje->nombre = date('Y', strtotime($fecha));
            $eje->fechainicio = date('1-1-Y', strtotime($fecha));
            $eje->fechafin = date('31-12-Y', strtotime($fecha));

            if (strtotime($fecha) < 1) {
                $this->miniLog->alert($this->i18n->trans('date-invalid-date', [$fecha]));
            } elseif ($eje->save()) {
                return $eje;
            }
        }

        return false;
    }

    /**
     * Comprueba los datos del ejercicio, devuelve True si son correctos
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codejercicio = trim($this->codejercicio);
        $this->nombre = self::noHtml($this->nombre);

        if (!preg_match('/^[A-Z0-9_]{1,4}$/i', $this->codejercicio)) {
            $this->miniLog->alert($this->i18n->trans('fiscal-year-code-invalid'));
        } elseif (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            $this->miniLog->alert($this->i18n->trans('fiscal-year-name-invalid'));
        } elseif (strtotime($this->fechainicio) > strtotime($this->fechafin)) {
            $params = [$this->fechainicio, $this->fechafin];
            $this->miniLog->alert($this->i18n->trans('start-date-later-end-date', $params));
        } elseif (strtotime($this->fechainicio) < 1) {
            $this->miniLog->alert($this->i18n->trans('date-invalid'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Crea la consulta necesaria para dotar de datos a un ejercicio en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codejercicio,nombre,fechainicio,fechafin,'
            . 'estado,longsubcuenta,plancontable,idasientoapertura,idasientopyg,idasientocierre) '
            . "VALUES ('" . date('Y') . "','" . date('Y') . "'," . $this->dataBase->var2str(date('01-01-Y'))
            . ', ' . $this->dataBase->var2str(date('31-12-Y')) . ",'ABIERTO',10,'08',null,null,null);";
    }
}
