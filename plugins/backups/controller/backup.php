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


class backup extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Backup', 'admin', FALSE, TRUE);
      
      if( isset($_GET['backup']) )
      {
         if( strtolower(FS_DB_TYPE) == 'mysql' )
         {
            $this->backup_mysql_tables();
         }
         else
         {
            $this->backup_postgresql_tables();
         }
      }
   }
   
   public function backup_mysql_tables()
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
   
   public function backup_postgresql_tables()
   {
      $host = FS_DB_HOST;
      $port = FS_DB_PORT;
      $pass = FS_DB_PASS;
      $user = FS_DB_USER;
      $db = FS_DB_NAME;
      $dbconn = pg_pconnect("host=$host port=$port dbname=$db user=$user password=$pass options='--client_encoding=UTF8'");
      $gfile = 'tmp/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
      $back = fopen($gfile, "w+");
      if($back)
      {
         $res = pg_query("select relname as tablename
            from pg_class where relkind in ('r')
            and relname not like 'pg_%' and relname not like 'sql_%' order by tablename");
         $str = "";
         while($row = pg_fetch_row($res))
         {
            $table = $row[0];
            $str .= "\n--\n";
            $str .= "-- Estrutura da tabela '$table'";
            $str .= "\n--\n";
            $str .= "\nDROP TABLE $table CASCADE;";
            $str .= "\nCREATE TABLE $table (";
            
            $res2 = pg_query("
               SELECT attnum,attname, typname, atttypmod-4, attnotnull, atthasdef, adsrc AS def
               FROM pg_attribute, pg_class, pg_type, pg_attrdef
               WHERE pg_class.oid = attrelid AND pg_type.oid=atttypid AND attnum>0 AND pg_class.oid=adrelid AND adnum=attnum
                  AND atthasdef='t' AND lower(relname)='$table' UNION
                     SELECT attnum,attname, typname, atttypmod-4, attnotnull, atthasdef, '' AS def
                     FROM pg_attribute, pg_class, pg_type WHERE pg_class.oid=attrelid
                        AND pg_type.oid=atttypid AND attnum>0 AND atthasdef='f' AND lower(relname)='$table' ");                                             
            
            while($r = pg_fetch_row($res2))
            {
               $str .= "\n" . $r[1]. " " . $r[2];
               
               if($r[2] == "varchar")
               {
                  $str .= "(".$r[3] .")";
               }
               
               if ($r[4]=="t")
               {
                  $str .= " NOT NULL";
               }
               
               if ($r[5]=="t")
               {
                  $str .= " DEFAULT ".$r[6];
               }
               
               $str .= ",";
            }
            
            $str = rtrim($str, ",");  
            $str .= "\n);\n";
            $str .= "\n--\n";
            $str .= "-- Creating data for '$table'";
            $str .= "\n--\n\n";
            
            $res3 = pg_query("SELECT * FROM $table");
            while($r = pg_fetch_row($res3))
            {
               $sql = "INSERT INTO $table VALUES ('";
               $sql .= utf8_decode(implode("','",$r));
               $sql .= "');";
               $str = str_replace("''","NULL",$str);
               $str .= $sql;  
               $str .= "\n";
            }
            
            $res1 = pg_query("SELECT pg_index.indisprimary, pg_catalog.pg_get_indexdef(pg_index.indexrelid)
               FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index AS pg_index
               WHERE c.relname = '$table' AND c.oid = pg_index.indrelid AND pg_index.indexrelid = c2.oid AND pg_index.indisprimary");
            
            while($r = pg_fetch_row($res1))
            {
               $str .= "\n\n--\n";
               $str .= "-- Creating index for '$table'";
               $str .= "\n--\n\n";
               $t = str_replace("CREATE UNIQUE INDEX", "", $r[1]);
               $t = str_replace("USING btree", "|", $t);
               
               // Next Line Can be improved!!!
               $t = str_replace("ON", "|", $t);
               $Temparray = explode("|", $t);
               $str .= "ALTER TABLE ONLY ". $Temparray[1] . " ADD CONSTRAINT " . 
               $Temparray[0] . " PRIMARY KEY " . $Temparray[2] .";\n";
            }
         }
         
         $res = pg_query(" SELECT cl.relname AS tabela,ct.conname, pg_get_constraintdef(ct.oid)
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_class cl ON (a.attrelid = cl.oid AND cl.relkind = 'r')
            JOIN pg_catalog.pg_namespace n ON (n.oid = cl.relnamespace)
            JOIN pg_catalog.pg_constraint ct ON (a.attrelid = ct.conrelid AND ct.confrelid != 0 AND ct.conkey[1] = a.attnum)
            JOIN pg_catalog.pg_class clf ON (ct.confrelid = clf.oid AND clf.relkind = 'r')
            JOIN pg_catalog.pg_namespace nf ON (nf.oid = clf.relnamespace)
            JOIN pg_catalog.pg_attribute af ON (af.attrelid = ct.confrelid AND af.attnum = ct.confkey[1]) order by cl.relname ");
         while($row = pg_fetch_row($res))
         {
            $str .= "\n\n--\n";
            $str .= "-- Creating relacionships for '".$row[0]."'";
            $str .= "\n--\n\n";
            $str .= "ALTER TABLE ONLY ".$row[0] . " ADD CONSTRAINT " . $row[1] . " " . $row[2] . ";";
         }
         
         fwrite($back, $str);
         fclose($back);
         
         $this->new_message('<a href="'.$gfile.'" target="_blank">Aquí</a> tienes el backup.');
      }
   }
}
