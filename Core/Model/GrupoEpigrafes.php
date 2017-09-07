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
 * Primer nivel del plan contable.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class GrupoEpigrafes
{

    use Base\ModelTrait;

    /**
     * Clave primaria
     *
     * @var int
     */
    public $idgrupo;

    /**
     * TODO
     *
     * @var string
     */
    public $codgrupo;

    /**
     * TODO
     *
     * @var string
     */
    public $codejercicio;

    /**
     * TODO
     *
     * @var string
     */
    public $descripcion;

    public function tableName()
    {
        return 'co_gruposepigrafes';
    }

    public function primaryColumn()
    {
        return 'idgrupo';
    }

    /**
     * TODO
     *
     * @return array
     */
    public function getEpigrafes()
    {
        $epigrafe = new Epigrafe();

        return $epigrafe->allFromGrupo($this->idgrupo);
    }

    /**
     * TODO
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|GrupoEpigrafes
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codgrupo = ' . $this->var2str($cod)
            . ' AND codejercicio = ' . $this->var2str($codejercicio) . ';';

        $grupo = $this->dataBase->select($sql);
        if (!empty($grupo)) {
            return new self($grupo[0]);
        }

        return false;
    }

    /**
     * TODO
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        if (strlen($this->codejercicio) > 0 && strlen($this->codgrupo) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }

        $this->miniLog->alert('Faltan datos en el grupo de epígrafes.');
        return false;
    }

    public function url($type = 'auto')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        $result = 'index.php?page=';
        switch ($type) {
            case 'list':
                $result .= 'ListCuenta&active=List' . $model;
                break;

            case 'edit':
                $result .= 'Edit' . $model . '&code=' . $value;
                break;

            case 'new':
                $result .= 'Edit' . $model;
                break;

            default:
                $result .= empty($value) ? 'ListCuenta&active=List' . $model : 'Edit' . $model . '&code=' . $value;
                break;
        }

        return $result;
    }
}
