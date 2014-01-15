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

class fs_cache
{
   private static $memcache;
   private static $connected;
   private static $error;
   private static $error_msg;
   
   public function __construct()
   {
      if( !isset(self::$memcache) )
      {
         if( class_exists('Memcache') )
         {
            self::$memcache = new Memcache();
            if( @self::$memcache->connect(FS_CACHE_HOST, FS_CACHE_PORT) )
            {
               self::$connected = TRUE;
               self::$error = FALSE;
               self::$error_msg = '';
            }
            else
            {
               self::$connected = FALSE;
               self::$error = TRUE;
               self::$error_msg = 'Error al conectar al servidor Memcache.';
            }
         }
         else
         {
            self::$memcache = NULL;
            self::$connected = FALSE;
            self::$error = TRUE;
            self::$error_msg = 'Clase Memcache no encontrada. Debes
               <a target="_blank" href="http://www.facturascripts.com/community/item.php?id=5215f68318c088e12e1a92f1">
               instalar Memcache</a> y activarlo en el php.ini';
         }
      }
   }
   
   public function error()
   {
      return self::$error;
   }
   
   public function error_msg()
   {
      return self::$error_msg;
   }
   
   public function close()
   {
      if( isset(self::$memcache) AND self::$connected )
         self::$memcache->close();
   }
   
   public function set($key, $object, $expire=3600)
   {
      if( self::$connected )
         self::$memcache->set(FS_CACHE_PREFIX.$key, $object, FALSE, $expire);
   }
   
   public function get($key)
   {
      if( self::$connected )
         return self::$memcache->get(FS_CACHE_PREFIX.$key);
      else
         return FALSE;
   }
   
   public function get_array($key)
   {
      $aa = array();
      if( self::$connected )
      {
         $a = self::$memcache->get(FS_CACHE_PREFIX.$key);
         if($a)
            $aa = $a;
      }
      return $aa;
   }
   
   public function get_array2($key, &$error)
   {
      $aa = array();
      $error = TRUE;
      if( self::$connected )
      {
         $a = self::$memcache->get(FS_CACHE_PREFIX.$key);
         if( is_array($a) )
         {
            $aa = $a;
            $error = FALSE;
         }
      }
      return $aa;
   }
   
   public function delete($key)
   {
      if( self::$connected )
         return self::$memcache->delete(FS_CACHE_PREFIX.$key);
      else
         return FALSE;
   }
   
   public function clean()
   {
      if( self::$connected )
         return self::$memcache->flush();
      else
         return FALSE;
   }
   
   public function version()
   {
      if( self::$connected )
         return self::$memcache->getVersion();
      else
         return '-';
   }
   
   public function connected()
   {
      return self::$connected;
   }
}

?>