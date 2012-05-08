<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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
   private $cache;
   private $connected;

   public function __construct()
   {
      $this->cache = new Memcache();
      try
      {
         $this->cache->connect(FS_CACHE_HOST, FS_CACHE_PORT);
         $this->connected = TRUE;
      }
      catch (Exception $e)
      {
         $this->connected = FALSE;
      }
   }
   
   public function __destruct()
   {
      $this->cache->close();
   }
   
   public function set($key, $object)
   {
      if($this->connected)
         $this->cache->set($key, $object, FALSE, FS_COOKIES_EXPIRE);
   }
   
   public function get($key)
   {
      if($this->connected)
         return $this->cache->get($key);
      else
         return FALSE;
   }
   
   public function get_array($key)
   {
      $aa = array();
      if($this->connected)
      {
         $a = $this->cache->get($key);
         if($a)
            $aa = $a;
      }
      return $aa;
   }

   public function delete($key)
   {
      if($this->connected)
         $this->cache->delete($key);
   }
}

?>
