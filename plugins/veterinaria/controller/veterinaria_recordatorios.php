<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class veterinaria_recordatorios extends fs_controller
{
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Recordatorios', 'Veterinaria');
   }
   
   protected function process()
   {
      $this->resultados = array();
      
      $sql = "SELECT m.idmascota,m.nombre as mascota,c.nombre as cliente,a.tipo,a.nombre as analisis,a.nueva_fecha
         FROM fbm_mascotas m, clientes c, fbm_analisis a
         WHERE a.nueva_fecha >= ".$this->empresa->var2str(Date('d-m-Y'))."
         AND a.nueva_fecha < ".$this->empresa->var2str(Date('d-m-Y', time()+2592000))."
         AND m.codcliente = c.codcliente AND m.idmascota = a.idmascota ORDER BY a.nueva_fecha DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $this->resultados[] = $d;
         }
      }
   }
}