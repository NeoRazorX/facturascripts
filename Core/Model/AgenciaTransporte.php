<?php
/*
 * This file is part of facturacion_base
 * Copyright (C) 2015         Pablo Peralta
 * Copyright (C) 2015-2016    Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\Model;
use FacturaScripts\Core\Base\ContactInformation;

/**
 * Agencia de transporte de mercancías.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class AgenciaTransporte
{

    use Model;
    use ContactInformation;

    /**
     * Clave primaria. Varchar(8).
     * @var string
     */
    public $codtrans;

    /**
     * Nombre de la agencia.
     * @var string
     */
    public $nombre;

    /**
     * TRUE => activo.
     * @var boolean
     */
    public $activo;

    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'agenciastrans', 'codtrans');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve el comando SQL que crea los datos iniciales tras la instalación
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codtrans, nombre, web, activo) VALUES ' .
            "('ASM', 'ASM', 'http://es.asmred.com/', 1)," .
            "('TIPSA', 'TIPSA', 'http://www.tip-sa.com/', 1)," .
            "('SEUR', 'SEUR', 'http://www.seur.com', 1);";
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        $result = 'index.php?page=AgenciaTransporte';
        if ($this->codtrans != NULL) {
            $result .= '_card&cod=' . $this->codtrans;
        }

        return $result;
    }
}
