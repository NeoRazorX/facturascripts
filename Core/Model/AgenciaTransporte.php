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

/**
 * Agencia de transporte de mercancías.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AgenciaTransporte
{
	use Model;

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
     * Teléfono de la agencia.
     * @var string
     */
    public $telefono;

    /**
     * Página web de la empresa de transporte
     * @var string
     */
    public $web;

    /**
     * TRUE => activo.
     * @var boolean
     */
    public $activo;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'agenciatransporte', 'codtrans');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
			$this->clear();
        }
    }
	
    /**
     * Limpia los registros del registro en curso
     */
    public function clear()
    {
        $this->codtrans = NULL;
        $this->nombre = NULL;
        $this->telefono = NULL;
        $this->web = NULL;
        $this->activo = TRUE;
    }

    /**
     * Devuelve el comando SQL que crea los datos iniciales tras la instalación
     * @return string
     */
    public function install() {
        return 'INSERT INTO ' . $this->tableName() . ' (codtrans, nombre, web, activo) VALUES '.
            "('ASM', 'ASM', 'http://es.asmred.com/', 1),".
            "('TIPSA', 'TIPSA', 'http://www.tip-sa.com/', 1),".
            "('SEUR', 'SEUR', 'http://www.seur.com', 1);";
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url() {
        return "index.php?page=admin_transportes&cod=" . $this->codtrans;
    }

    /**
     * Devuelve la agencia de transporte con codtrans = $cod
     * @param string $cod
     * @return \FacturaScripts\model\agencia_transporte|boolean
     */
    public function get($cod) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->tableName() . " WHERE codtrans = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \agencia_transporte($data[0]);
        } else
            return FALSE;
    }

}
