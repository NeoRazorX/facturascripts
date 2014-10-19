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

2014-10-18  Añadidos nuevos métodos y comportamiento. Se conserva la funcionalidad original

Principales Métodos:

fs_var::loadConfiguration()
Carga los pares clave/valor en un array estático. Debería llamarse en index.php, 
para que los pares clave/valor estén disponibles en todos los controladores

fs_var::getValue($key)
Devuelve el valor de la clave $key o FALSE en caso de que no se encuentre.

fs_var::updateValue($key)
Actualiza el valor de la clave $key. Si no se encuentra la clave, se creará

NOTA:
Ciertas claves, como FS_MARGIN_METHOD y FS_COST_IS_AVERAGE deben tener valores para 
usar ciertos controladores.
FS_MARGIN_METHOD y FS_COST_IS_AVERAGE se inicializan en método install

 */

require_once 'base/fs_model.php';

/**
 * Una clase genércia para consultar o almacenar en la base de datos
 * pares clave/valor.
 */
class fs_var extends fs_model
{
   public $name; /// pkey
   public $varchar;

   /** @var array Configuration cache */
   protected static $_CONF;

   /** Allowed values for Validation **/
   // Son los valores posibles para variables. En la Vista aparecerán en un "select"
   // Por convenio, el primer valor se tomará por defecto cuando sea necesario inicializar la clave
   public static $confKeysValues = array( 'FS_MARGIN_METHOD' => array( 'PVP', 'CST' ) );
   
   public function __construct($f=FALSE)
   {
      parent::__construct('fs_vars');
      if($f)
      {
         $this->name = $f['name'];
         $this->varchar = $f['varchar'];
      }
      else
      {
         $this->name = NULL;
         $this->varchar = NULL;
      }
   }
   
   protected function install()
   {
      return "INSERT INTO `fs_vars` (`name`, `varchar`) VALUES ('FS_COST_IS_AVERAGE', '1');
              INSERT INTO `fs_vars` (`name`, `varchar`) VALUES ('FS_MARGIN_METHOD', 'PVP');";
   }
   
   /**
    * Devuelve la variable $cod o FALSE en caso de que no se encuentre.
    * @param type $cod
    * @return \fs_var|boolean
    */
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($cod).";");
      if($data)
      {
         return new fs_var($data[0]);
      }
      else
         return FALSE;
   }

  /**
    * Set TEMPORARY a single configuration value
    *
    * @param string $key Key wanted
    * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
    *
    */
  public static function set($key, $values)
  {
    if (!self::isConfigName($key))
      return FALSE;

    /* Update classic values */
    self::$_CONF[$key] = $values;
  }

  /**
    * Get a single configuration value 
    *
    * @param string $key Key wanted
    * @return string Value
    */
  public static function getValue($key)
  {
    if (isset(self::$_CONF[$key]))
      return self::$_CONF[$key];
    return false;
  }


  /**
    * Get a single configuration value 
    *
    * @param string $key Key wanted
    * @return string Value
    */
  public static function getInt($key)
  {
    $result = self::get($key);
    return isset($result) ? $result : NULL;
  }
   
   public function exists()
   {
      if( is_null($this->name) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
      }
   }
   
   public function test()
   {
      if( is_null($this->name) )
      {
         return FALSE;
      }
      else if( strlen($this->name) > 1 AND strlen($this->name) < 20  )
      {
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $comillas = '';
         if( strtolower(FS_DB_TYPE) == 'mysql' )
            $comillas = '`';
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET ".$comillas."varchar".$comillas." = ".$this->var2str($this->varchar).
                    " WHERE name = ".$this->var2str($this->name).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (name,".$comillas."varchar".$comillas.") VALUES
               (".$this->var2str($this->name).",".$this->var2str($this->varchar).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
   }

  /**
    * Delete a configuration key in database 
    *
    * @param string $key Key to delete
    * @return boolean Deletion result
    */
  public static function deleteByName($key)
  {
    /* If the key does not exist, return true (emulate a successful deletion) */
    if (!isset(self::$_CONF[$key]))
      return true;

    // If the key is invalid or if it does not exists, do nothing.
    if (!self::isConfigName($key))
      return false;   

    /* Delete the key from the main configuration table */
    $newConfig = new fs_var( array('name' => $key, 'varchar' => '') );
    if ( $newConfig->delete() )
      unset(self::$_CONF[$key]);
    else
      return false;

    return true;
  }
   
   public function all()
   {
      $vlist = array();
      $vars = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($vars)
      {
         foreach($vars as $v)
            $vlist[] = new fs_var($v);
      }
      return $vlist;
   }
   
   /**
    * Rellena un array con los resultados de la base de datos para cada clave,
    * es decir, para el array('clave1' => false, 'clave2' => false) busca
    * en la tabla las claves clave1 y clave2 y asigna los valores almacenados
    * en la base de datos.
    * 
    * Sustituye los valores por FALSE si no los encentra en la base de datos,
    * a menos que pongas FALSE en el segundo parámetro.
    * 
    * @param type $array
    */
   public function array_get($array, $replace=TRUE)
   {
      foreach($array as $i => $value)
      {
         $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($i).";");
         if($data)
         {
            $array[$i] = $data[0]['varchar'];
         }
         else if($replace)
         {
            $array[$i] = FALSE;
         }
      }
      
      return $array;
   }
   
   /**
    * Guarda en la base de datos los pares clave, valor de un array simple.
    * 
    * @param type $array
    */
   public function array_save($array)
   {
      $done = TRUE;
      
      foreach($array as $i => $value)
      {
         if($value === FALSE)
         {
            $fv0 = $this->get($i);
            if($fv0)
               $fv0->delete();
         }
         else
         {
            $fv0 = new fs_var( array('name' => $i, 'varchar' => $value) );
            
            if( !$fv0->save() )
               $done = FALSE;
         }
      }
      
      return $done;
   }

  /**
    * Insert configuration key and value into database
    *
    * @param string $key Key
    * @param string $value Value
    * @eturn boolean Insert result
    */
  protected static function _addConfiguration($key, $value = null)
  {
    $newConfig = new fs_var();
    $newConfig->name = $key;
    $newConfig->varchar = $value;
    return $newConfig->save();
  }

  /**
    * Update configuration key and value into database (automatically insert if key does not exist)
    *
    * @param string $key Key
    * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
    * @param boolean $html Specify if html is authorized in value
    *
    * @return boolean Update result
    */
  public static function updateValue($key, $values, $html = false)
  {
    if ($key == null)
      return;

    if (!self::isConfigName($key))
      return;

    $current_value = self::getValue($key);
    $values = self::pSQL($values, $html);

      /* Update classic values */
      if ( $current_value !== false )   // $key está definido, actualizar el valor
      {
        /* Do not update the database if the current value is the same one than the new one */
        if ($values == $current_value)
          $result = true;
        else
        {
          $newConfig = new fs_var();
          $newConfig->name = $key;
          $newConfig->varchar = $values;
          $result = $newConfig->save();
          if ($result)
            self::$_CONF[$key] = stripslashes($values);
        }
      }
      else
      {
        $result = self::_addConfiguration($key, $values);
        if ($result)
        {
          self::$_CONF[$key] = stripslashes($values);
        }
      }
    
    return (bool)$result;
  }

  public static function loadConfiguration()
  {
    self::$_CONF = array();

    $newConfig = new fs_var();
    $result = $newConfig->all();

    if ($result)
      foreach ($result as $row)
      {
        self::$_CONF[$row->name] = $row->varchar;
      }
  }

  /**
  * Check for configuration key validity
  *
  * @param string $configName Configuration key to validate
  * @return boolean Validity is ok or not
  */
  static public function isConfigName($configName)
  {
    return preg_match('/^[a-z_0-9-]+$/ui', $configName);
  }

  /**
   * Sanitize data which will be injected into SQL query
   *
   * @param string $string SQL data which will be injected into SQL query
   * @param boolean $htmlOK Does data contain HTML code ? (optional)
   * @return string Sanitized data
   */
  static public function pSQL($string, $htmlOK = false)
  {
    if ( get_magic_quotes_gpc() )
      $string = stripslashes($string);
    if (!is_numeric($string))
    {
      $string = addslashes($string);
    //  if (!$htmlOK)
    //    $string = strip_tags(nl2br2($string));
    }
      
    return $string;
  }

}

