<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Gisbel Jose Pena Gomez   gpg841@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez         neorazorx@gmail.com
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


class backup extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Backup', 'admin', FALSE, TRUE);
      
      if( strtolower(FS_DB_TYPE) == 'mysql' AND isset($_GET['backup']) ) /// ES ISSET() SIRVE PARA SABER SI UNA VARIABLE ESTÁ DEFINIDA
      {
         $this->backup_tables();
      }
   }
   
   public function backup_tables()
   {
      $link = mysql_connect(FS_DB_HOST, FS_DB_USER, FS_DB_PASS);
      mysql_select_db(FS_DB_NAME,$link);
      
		$tables = array();
		$result = mysql_query('SHOW TABLES;');
		while($row = mysql_fetch_row($result))
		{
			$tables[] = $row[0];
		}
      
      $return = ''; /// TE FALTABA INICIALIZAR ESTA VARIABLE
      foreach($tables as $table)
      {
         $result = mysql_query('SELECT * FROM '.$table.';'); /// HE AÑADIDO LOS ;
         $num_fields = mysql_num_fields($result);
         
         $return .= 'DROP TABLE '.$table.';';
         $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table.';'));
         $return .= "\n\n".$row2[1].";\n\n";
         
         for ($i = 0; $i < $num_fields; $i++) 
         {
            while($row = mysql_fetch_row($result))
            {
               $return.= 'INSERT INTO '.$table.' VALUES(';
               for($j=0; $j<$num_fields; $j++) 
               {
                  $row[$j] = addslashes($row[$j]);
                  $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                  
                  if(isset($row[$j]))
                  {
                     $return.= '"'.$row[$j].'"' ;
                  }
                  else
                  {
                     $return.= '""';
                  }
                  
                  if($j < ($num_fields-1))
                  {
                     $return.= ',';
                  }
   				}
               
               $return.= ");\n";
            }
         }
         
         $return.="\n\n\n";
      }
      
      /// GUARDA LOS ARCHIVOS TEMPORALES EN TMP
      $gfile = 'tmp/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
      $handle = fopen($gfile,'w+');
      if($handle)
      {
         fwrite($handle, $return);
         fclose($handle);
         
         /// ESTO ES PARA MOSTRAR EL MENSAJE PARA DESCARGAR EL ARCHIVO
         $this->new_message('<a href="'.$gfile.'">Aquí</a> tienes el backup.');
      }
      
      return $gfile;
   }
}
