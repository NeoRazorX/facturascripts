<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Model;

/**
 * Description of AccountingGenerator
 *
 * @author Carlos García Gómez
 */
class AccountingGenerator
{

    /**
     * Listado de ejercicios disponibles.
     * @var Model\Ejercicio[] 
     */
    protected $ejercicios;

    /**
     * Proporciona acceso al generador de datos.
     * @var DataGeneratorTools
     */
    protected $tools;

    public function __construct()
    {
        $ejercicioModel = new Model\Ejercicio();
        $this->ejercicios = $ejercicioModel->all();

        $this->tools = new DataGeneratorTools();
    }

    public function cuentas($max = 50)
    {
        $epigrafes = $this->randomEpigrafes();
        for ($num = 0; $num < $max; ++$num) {
            $cuenta = new Model\Cuenta();
            $cuenta->codcuenta = $epigrafes[0]->codepigrafe . mt_rand(1, 99);
            $cuenta->codejercicio = $epigrafes[0]->codejercicio;
            $cuenta->codepigrafe = $epigrafes[0]->codepigrafe;
            $cuenta->descripcion = $this->tools->descripcion();
            $cuenta->idepigrafe = $epigrafes[0]->idepigrafe;
            if (!$cuenta->save()) {
                break;
            }
        }

        return $num;
    }

    public function epigrafes($max = 50)
    {
        $grupos = $this->randomGruposEpigrafes();
        for ($num = 0; $num < $max; ++$num) {
            $epigrafe = new Model\Epigrafe();
            $epigrafe->codejercicio = $grupos[0]->codejercicio;
            $epigrafe->codepigrafe = $grupos[0]->codgrupo . mt_rand(1, 99);
            $epigrafe->codgrupo = $grupos[0]->codgrupo;
            $epigrafe->descripcion = $this->tools->descripcion();
            $epigrafe->idgrupo = $grupos[0]->idgrupo;
            if (!$epigrafe->save()) {
                break;
            }
        }

        return $num;
    }

    public function gruposEpigrafes($max = 50)
    {
        shuffle($this->ejercicios);
        for ($num = 0; $num < $max; ++$num) {
            $grupo = new Model\GrupoEpigrafes();
            $grupo->codejercicio = $this->ejercicios[0]->codejercicio;
            $grupo->codgrupo = mt_rand(1, 99);
            $grupo->descripcion = $this->tools->descripcion();
            if (!$grupo->save()) {
                break;
            }
        }

        return $num;
    }

    protected function randomEpigrafes()
    {
        $epigrafeModel = new Model\Epigrafe();
        $epigrafes = $epigrafeModel->all();
        if (empty($epigrafes)) {
            return [];
        }

        shuffle($epigrafes);
        return $epigrafes;
    }

    protected function randomGruposEpigrafes()
    {
        $grupoModel = new Model\GrupoEpigrafes();
        $grupos = $grupoModel->all();
        if (empty($grupos)) {
            return [];
        }

        shuffle($grupos);
        return $grupos;
    }
}
