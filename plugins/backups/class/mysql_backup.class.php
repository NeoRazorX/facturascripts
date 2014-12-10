<?php

/*
  MySQL database backup class, version 1.0.1b
  Written by Vagharshak Tozalakyan <vagh@armdex.com>
  Modified by Valentín González
  Released under GNU Public license
*/


define('MSB_VERSION', '1.0.1b');

define('MSB_NL', "\r\n");

define('MSB_STRING', 0);
define('MSB_DOWNLOAD', 1);
define('MSB_SAVE', 2);

class MySQL_Backup
{

  var $server = 'localhost';
  var $port = 3306;
  var $username = 'root';
  var $password = '';
  var $database = '';
  var $link_id = -1;
  var $connected = false;
  var $tables = array();
  var $drop_tables = true;
  var $struct_only = false;
  var $comments = true;
  var $backup_dir = '';
  var $fname_format = 'd_m_y__H_i_s';
  var $error = '';


  function Execute($task = MSB_STRING, $fname = '', $compress = false)
  {
    if (!($sql = $this->_Retrieve()))
    {
      return false;
    }
    if ($task == MSB_SAVE)
    {
      if (empty($fname))
      {
        $fname = $this->backup_dir;
        $fname .= date($this->fname_format);
        $fname .= ($compress ? '.sql.gz' : '.sql');
      } else {
        $fname = $this->backup_dir . date($this->fname_format) . $fname;
        $fname .= ($compress ? '.sql.gz' : '.sql');
      }
      return $this->_SaveToFile($fname, $sql, $compress);
    }
    elseif ($task == MSB_DOWNLOAD)
    {
      if (empty($fname))
      {
        $fname = date($this->fname_format);
        $fname .= ($compress ? '.sql.gz' : '.sql');
      } else {
        $fname = date($this->fname_format) . $fname;
        $fname .= ($compress ? '.sql.gz' : '.sql');
      }
      return $this->_DownloadFile($fname, $sql, $compress);
    }
    else
    {
      return $sql;
    }
  }


  function _Connect()
  {
    $value = false;
    if (!$this->connected)
    {
      $host = $this->server . ':' . $this->port;
      $this->link_id = mysql_connect($host, $this->username, $this->password);
    }
    if ($this->link_id)
    {
      if (empty($this->database))
      {
        $value = true;
      }
      elseif ($this->link_id !== -1)
      {
        $value = mysql_select_db($this->database, $this->link_id);
      }
      else
      {
        $value = mysql_select_db($this->database);
      }
    }
    if (!$value)
    {
      $this->error = mysql_error();
    }
    return $value;
  }


  function _Query($sql)
  {
    if ($this->link_id !== -1)
    {
      $result = mysql_query($sql, $this->link_id);
    }
    else
    {
      $result = mysql_query($sql);
    }
    if (!$result)
    {
      $this->error = mysql_error();
    }
    return $result;
  }


  function _GetTables()
  {
    $value = array();
    if (!($result = $this->_Query('SHOW TABLES')))
    {
      return false;
    }
    while ($row = mysql_fetch_row($result))
    {
      if (empty($this->tables) || in_array($row[0], $this->tables))
      {
        $value[] = $row[0];
      }
    }
    if (!sizeof($value))
    {
      $this->error = 'No se encuentran tablas en la Base de Datos.';
      return false;
    }
    return $value;
  }


  function _DumpTable($table)
  {
    $value = '';
    $this->_Query('LOCK TABLES ' . $table . ' WRITE');
    if ($this->comments)
    {
      $value .= '#' . MSB_NL;
      $value .= '# Estructura de la tabla `' . $table . '`' . MSB_NL;
      $value .= '#' . MSB_NL . MSB_NL;
    }
    if ($this->drop_tables)
    {
      $value .= 'DROP TABLE IF EXISTS `' . $table . '`;' . MSB_NL;
    }
    if (!($result = $this->_Query('SHOW CREATE TABLE ' . $table)))
    {
      return false;
    }
    $row = mysql_fetch_assoc($result);
    $value .= str_replace("\n", MSB_NL, $row['Create Table']) . ';';
    $value .= MSB_NL . MSB_NL;
    if (!$this->struct_only)
    {
      if ($this->comments)
      {
        $value .= '#' . MSB_NL;
        $value .= '# Volcado de datos de la tabla `' . $table . '`' . MSB_NL;
        $value .= '#' . MSB_NL . MSB_NL;
      }
      $value .= $this->_GetInserts($table);
    }
    $value .= MSB_NL . MSB_NL;
    $this->_Query('UNLOCK TABLES');
    return $value;
  }


  function _GetInserts($table)
  {
    $value = '';
    if (!($result = $this->_Query('SELECT * FROM ' . $table)))
    {
      return false;
    }
    while ($row = mysql_fetch_row($result))
    {
      $values = '';
      foreach ($row as $data)
      {
        $values .= '\'' . addslashes($data) . '\', ';
      }
      $values = substr($values, 0, -2);
      $value .= 'INSERT INTO ' . $table . ' VALUES (' . $values . ');' . MSB_NL;
    }
    return $value;
  }


  function _Retrieve()
  {
    $value = '';
    if (!$this->_Connect())
    {
      return false;
    }
    if ($this->comments)
    {
      $value .= '#' . MSB_NL;
      $value .= '# Respaldo de Base de Datos MySQL' . MSB_NL;
      $value .= '# Creada con la clase MySQL_Backup - Versión. ' . MSB_VERSION . MSB_NL;
      $value .= '# (Adaptada y traducida por Valentín González)' . MSB_NL;
      $value .= '#' . MSB_NL;
      $value .= '# Servidor: ' . $this->server . MSB_NL;
      $value .= '# Generada el: ' . date('d/m/Y') . ' a las ' . date('H:i:s') . MSB_NL;
      $value .= '# Versión de MySQL: ' . mysql_get_server_info() . MSB_NL;
      $value .= '# Versión de PHP: ' . phpversion() . MSB_NL;
      if (!empty($this->database))
      {
        $value .= '#' . MSB_NL;
        $value .= '# Base de Datos: `' . $this->database . '`' . MSB_NL;
      }
      $value .= '#' . MSB_NL . MSB_NL . MSB_NL;
      $value .= 'SET FOREIGN_KEY_CHECKS=0;' . MSB_NL . MSB_NL . MSB_NL;
    }
    if (!($tables = $this->_GetTables()))
    {
      return false;
    }
    foreach ($tables as $table)
    {
      if (!($table_dump = $this->_DumpTable($table)))
      {
        $this->error = mysql_error();
        return false;
      }
      $value .= $table_dump;
    }
    $value .= 'SET FOREIGN_KEY_CHECKS=1;' . MSB_NL . MSB_NL . MSB_NL;
    return $value;
  }


  function _SaveToFile($fname, $sql, $compress)
  {
    if ($compress)
    {
      if (!($zf = gzopen($fname, 'w9')))
      {
        $this->error = 'No se puede crear el archivo de salida.';
        return false;
      }
      gzwrite($zf, $sql);
      gzclose($zf);
    }
    else
    {
      if (!($f = fopen($fname, 'w')))
      {
        $this->error = 'No se puede crear el archivo de salida.';
        return false;
      }
      fwrite($f, $sql);
      fclose($f);
    }
    return $fname;
  }


  function _DownloadFile($fname, $sql, $compress)
  {
    header('Content-disposition: filename=' . $fname);
    header('Content-type: application/octetstream');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo ($compress ? gzencode($sql) : $sql);
    return true;
  }

}

?>