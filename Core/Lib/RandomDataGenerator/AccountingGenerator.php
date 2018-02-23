<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * 
 * NOTICE: This class is deprecated!!!
 * 
 */
namespace FacturaScripts\Core\Lib\RandomDataGenerator;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model;

/**
 * Description of AccountingGenerator
 *
 * @author Carlos García Gómez
 */
class AccountingGenerator
{

    /**
     * List of available periods
     *
     * @var Model\Ejercicio[]
     */
    protected $ejercicios;

    /**
     * Default company
     *
     * @var Model\Empresa
     */
    protected $empresa;

    /**
     * Provides access to the data generator
     *
     * @var DataGeneratorTools
     */
    protected $tools;

    /**
     * AccountingGenerator constructor.
     *
     * @param $empresa
     */
    public function __construct($empresa)
    {
        $ejercicioModel = new Model\Ejercicio();
        $this->ejercicios = $ejercicioModel->all();

        $this->empresa = $empresa;
        $this->tools = new DataGeneratorTools();
    }

    /**
     * Genera asiendos con datos aleatorios.
     *
     * @param int $max
     *
     * @return int
     */
    public function asientos($max = 25)
    {
        $subcuentas = $this->randomModel("\FacturaScripts\Dinamic\Model\Subcuenta");

        for ($num = 0; $num < $max; ++$num) {
            shuffle($this->ejercicios);

            $asiento = new Model\Asiento();
            $asiento->codejercicio = $this->ejercicios[0]->codejercicio;
            $asiento->concepto = $this->tools->descripcion();
            $asiento->fecha = date('d-m-Y', strtotime($this->ejercicios[0]->fechainicio . ' +' . mt_rand(1, 360) . ' days'));
            $asiento->importe = $this->tools->precio(-999, 150, 99999);
            if ($asiento->save()) {
                shuffle($subcuentas);
                $max2 = mt_rand(1, 20) * 2;
                $debe = true;

                for ($num2 = 0; $num2 < $max2; ++$num2) {
                    $partida = new Model\Partida();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->idsubcuenta = $subcuentas[$num2]->idsubcuenta;
                    $partida->codsubcuenta = $subcuentas[$num2]->codsubcuenta;
                    $partida->concepto = $asiento->concepto;
                    if ($debe) {
                        $partida->debe = $asiento->importe;
                    } else {
                        $partida->haber = $asiento->importe;
                    }

                    if ($partida->save()) {
                        $debe = !$debe;
                    }
                }
                continue;
            }

            break;
        }

        return $num;
    }

    /**
     * Genera cuentas con datos aleatorios.
     *
     * @param int $max
     *
     * @return int
     */
    public function cuentas($max = 50)
    {
        $where = [new DataBaseWhere('codejercicio', $this->ejercicios[0]->codejercicio)];
        $cuentas = $this->randomModel("\FacturaScripts\Dinamic\Model\Cuenta", $where);
        for ($num = 0; $num < $max; ++$num) {
            $cuenta = new Model\Cuenta();

            if (isset($cuentas[0])) {
                $cuenta->codcuenta = $cuenta->parent_codcuenta = $cuentas[0]->codcuenta;
                $cuenta->codejercicio = $cuentas[0]->codejercicio;
            } else {
                $cuenta->codejercicio = $this->ejercicios[0]->codejercicio;
                shuffle($this->ejercicios);
            }

            $cuenta->codcuenta .= mt_rand(1, 9);
            $cuenta->descripcion = $this->tools->descripcion();
            if (!$cuenta->save()) {
                break;
            }

            shuffle($cuentas);
        }

        return $num;
    }

    /**
     * Returns an array with random data of the given model.
     * 
     * @param string $modelName
     * @param array  $where
     * 
     * @return array
     */
    protected function randomModel($modelName = "\FacturaScripts\Dinamic\Model\Cuenta", $where = [])
    {
        $model = new $modelName();
        $data = $model->all($where);
        if (empty($model)) {
            return [];
        }

        shuffle($data);

        return $data;
    }

    /**
     * Genera subcuentas con datos aleatorios.
     *
     * @param int $max
     *
     * @return int
     */
    public function subcuentas($max = 50)
    {
        $cuentas = $this->randomModel("\FacturaScripts\Dinamic\Model\Cuenta");
        for ($num = 0; $num < $max; ++$num) {
            $subcuenta = new Model\Subcuenta();
            $subcuenta->codcuenta = $cuentas[0]->codcuenta;
            $subcuenta->codejercicio = $cuentas[0]->codejercicio;
            $subcuenta->codsubcuenta = $cuentas[0]->codcuenta . mt_rand(0, 9999);
            $subcuenta->descripcion = $this->tools->descripcion();
            $subcuenta->idcuenta = $cuentas[0]->idcuenta;
            if (!$subcuenta->save()) {
                break;
            }

            shuffle($cuentas);
        }

        return $num;
    }
}
