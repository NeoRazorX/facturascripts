<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Gisbel Jose Pena Gomez   gpg841@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez         neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra     shawe.ewahs@gmail.com
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


class eneboo_fix extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Eneboo FIX', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( $this->db->table_exists('co_cuentas') AND $this->db->table_exists('co_epigrafes') AND $this->db->table_exists('co_gruposepigrafes') )
      {
         /// comprobamos las cuentas
         $problematicos = $this->db->select("select * from co_cuentas where idepigrafe not in
            (select idepigrafe from co_epigrafes);");
         foreach($problematicos as $pro)
         {
            $solucion = $this->db->select("select * from co_epigrafes where codejercicio = '".$pro['codejercicio']."'
               and codepigrafe = '".$pro['codepigrafe']."';");
            if($solucion)
            {
               /// asignamos el idepigrafe correcto
               $this->db->exec("update co_cuentas set idepigrafe = '".$solucion[0]['idepigrafe']."'
                  where idcuenta = '".$pro['idcuenta']."';");
            }
            else
            {
               /// ¿Existe el grupo de epigrafes?
               $grupos = $this->db->select("select * from co_gruposepigrafes where codejercicio = '".$pro['codejercicio']."'
                  and codgrupo = '".substr($pro['codepigrafe'], 0, 1)."';");
               if($grupos)
               {
                  /// existe el grupo
                  $this->db->exec("insert into co_epigrafes (codejercicio,codepigrafe,descripcion,idgrupo) values
                     ('".$pro['codejercicio']."','".substr($pro['codepigrafe'], 0, 1)."','Epigrafe añadido por eneboo FIX.','".$grupos[0]['idgrupo']."');");
                  /// asignamos el idepigrafe correcto
                  $this->db->exec("update co_cuentas set idepigrafe = '".$this->db->lastval()."'
                     where idcuenta = '".$pro['idcuenta']."';");
               }
               else
               {
                  /// creamos el epigrafe
                  $this->db->exec("insert into co_epigrafes (codejercicio,codepigrafe,descripcion) values
                     ('".$pro['codejercicio']."','".substr($pro['codepigrafe'], 0, 1)."','Epigrafe añadido por eneboo FIX.');");
                  /// asignamos el idepigrafe correcto
                  $this->db->exec("update co_cuentas set idepigrafe = '".$this->db->lastval()."'
                     where idcuenta = '".$pro['idcuenta']."';");
               }
            }
         }
         
         /// comprobamos los epígrafes
         foreach($this->db->get_columns('co_epigrafes') as $col)
         {
            if($col['column_name'] == 'idgrupo')
            {
               $this->db->exec("UPDATE co_epigrafes SET idgrupo = NULL WHERE idgrupo = '0';");
               break;
            }
         }
      }
   }
}