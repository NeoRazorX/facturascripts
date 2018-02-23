<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Generates random data in the product families (familias) file
 *
 * @author Rafael San José <info@rsanjoseo.com>
 */
class Familias extends AbstractRandom
{

    public function __construct()
    {
        parent::__construct(new Model\Familia());
    }

    public function generate($num = 50)
    {
        $fam = $this->model;
        $codfamilia = null;

        for ($generated = 0; $generated < $num; ++$generated) {
            $fam->clear();
            $fam->descripcion = $this->familia();
            $fam->codfamilia = $this->txt2codigo($fam->descripcion);
            $fam->madre = (mt_rand(0, 4) == 0 && $fam->codfamilia != $codfamilia) ? $codfamilia : null;
            if (!$fam->save()) {
                break;
            }

            $codfamilia = $fam->codfamilia;
        }

        return $generated;
    }
}
