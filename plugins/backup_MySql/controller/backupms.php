<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Valentín González   	valengon@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez      neorazorx@gmail.com
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

 
/// Ruta para las Copias de Respaldo
if( !defined('FS_TMP_NAME') ) { define('FS_TMP_NAME', ''); }
define('CAR_BK_MS', 'tmp'. DIRECTORY_SEPARATOR . FS_TMP_NAME . 'bkms___' . FS_DB_NAME . DIRECTORY_SEPARATOR );

class backupms extends fs_controller
{
	public function __construct()
	{
		parent::__construct(__CLASS__, 'Backup MySQL', 'admin', TRUE, TRUE);
	}
 
    protected function process()
    {   
		$this->buttons[] = new fs_button('b_backupms', 'Respaldar por Completo la BD', $this->url().'&tipo_backup=todo');
		$this->buttons[] = new fs_button('be_backupms', 'Respaldar por Completo Solo la Estructura de la BD', $this->url().'&tipo_backup=estructura');
		
		if( strtolower(FS_DB_TYPE) == 'mysql' )
		{
			if( isset($_GET['take_bk']) )
			{
				// Descargar Archivo del Backup
				$this->baja_backup($_GET['take_bk']);
			}
			$this->cerrado = false;		
			$this->backup_mysql_ms();
		} else {
			$this->cerrado = true;
			$this->new_error_msg('ERROR de ACCESO: Con este Plugin solo se contempla la Gestión de Bases de Datos del Tipo MySQL.');
		}
    }
	
	public function backup_mysql_ms()
	{
      if( isset($_GET['tipo_backup']) )
      {
		$this->cerrado = true;
		if ($_GET['tipo_backup'] == 'selestructura' || $_GET['tipo_backup'] == 'seltodo')
		{		
			// Gestion de la/s Tabla/s Seleccionada/s
			if( isset($_POST['enabled']) )
			{
				if ($_GET['tipo_backup'] == 'selestructura')
				{
					if ($salida = $this->accion_copiaseg($_POST['enabled'], true, 'SE')) // SE =  Tablas Seleccionadas+Estructura
					{
						$salida = str_replace( CAR_BK_MS ,'',$salida);
						$this->new_message('(Backup Selectivo de Tablas): Respaldo de la Estructura de la Bases de Datos MySQL ('. FS_DB_NAME .') realizado correctamente.');
						$this->new_advice('<a href="'.$this->url().'&take_bk='.$salida.'">CLICK para Descargar COPIA DE RESPALDO del Archivo --> '.$salida.'</a>.');
					} else {
						$this->new_error_msg('ERROR con Backup Selectivo de Tablas: No se ha podido realizar el Respaldo de la Estructura de la Bases de Datos MySQL ('. FS_DB_NAME .').');
					}
				}
				if ($_GET['tipo_backup'] == 'seltodo')
				{
					if ($salida = $this->accion_copiaseg($_POST['enabled'], false, 'SED')) // SED =  Tablas Seleccionadas+Estructura+Datos
					{
						$salida = str_replace( CAR_BK_MS ,'',$salida);				
						$this->new_message('(Backup Selectivo de Tablas): Respaldo de la Bases de Datos MySQL ('. FS_DB_NAME .') realizado correctamente.');
						$this->new_advice('<a href="'.$this->url().'&take_bk='.$salida.'">CLICK para Descargar COPIA DE RESPALDO del Archivo --> '.$salida.'</a>.');
					} else {
						$this->new_error_msg('ERROR con Backup Selectivo de Tablas: No se ha podido realizar el Respaldo de la Bases de Datos MySQL ('. FS_DB_NAME .').');
					}
				}
			} else {
				$this->new_error_msg('ERROR: No se ha seleccionado ninguna tabla para realizar el Respaldo Selectivo de la Bases de Datos MySQL ('. FS_DB_NAME .').');
			}

		} else {		

			// Gestion de la Base de Datos Completa
			if ($_GET['tipo_backup'] == 'estructura')
			{
				if ($salida = $this->accion_copiaseg(array(), true, 'CE')) // CE =  BD Completa+Estructura
				{
					$salida = str_replace( CAR_BK_MS ,'',$salida);				
					$this->new_message('(Base de Datos Estructura): Respaldo COMPLETO de la Estructura de la Bases de Datos MySQL ('. FS_DB_NAME .') realizado correctamente.');
					$this->new_advice('<a href="'.$this->url().'&take_bk='.$salida.'">CLICK para Descargar COPIA DE RESPALDO del Archivo --> '.$salida.'</a>.');
				} else {
					$this->new_error_msg('ERROR con Backup de Estructura de la Base de Datos: No se ha podido realizar el Respaldo COMPLETO DE LA ESTRUCTURA de la Bases de Datos MySQL ('. FS_DB_NAME .').');
				}
			}		
			if ($_GET['tipo_backup'] == 'todo')
			{
				if ($salida = $this->accion_copiaseg(array(), false, 'CED')) // CED =  BD Completa+Estructura+Datos
				{
					$salida = str_replace( CAR_BK_MS ,'',$salida);				
					$this->new_message('(Base de Datos Completa): Respaldo COMPLETO de la Bases de Datos MySQL ('. FS_DB_NAME .') realizado correctamente.');
					$this->new_advice('<a href="'.$this->url().'&take_bk='.$salida.'">CLICK para Descargar COPIA DE RESPALDO del Archivo --> '.$salida.'</a>.');
				} else {
					$this->new_error_msg('ERROR con Backup Completo de la Base de Datos: No se ha podido realizar el Respaldo COMPLETO de la Bases de Datos MySQL ('. FS_DB_NAME .').');
				}
			}
		}
		
	  } else {
	  
		$tabla = $this->db->list_tables();
		for($ii=0; $ii<count($tabla); $ii++)
		{
			$row = $this->db->get_columns($tabla[$ii]['name']);
			$cadena = null;
			for($i=0; $i<count($row); $i++)
			{
				$cadena .= $row[$i]['column_name'].', ';
			}
			$tablas[$tabla[$ii]['name']] = substr($cadena, 0,-2);
		}	
		$this->tabla = $tablas;	
		$this->new_message('Se han cargado correctamente las Tablas de la Base de Datos MySQL ('. FS_DB_NAME .')');
	  }
	}
	
	// Proceso de BACKUP
	public function accion_copiaseg($datos = array(), $estruc = false, $version = 'CED')
	{
		require_once 'plugins/backup_MySql/class/mysql_backup.class.php';
		
		$backup_obj = new MySQL_Backup();
		//----------------------- EDIT - REQUIRED SETUP VARIABLES -----------------------
				
				$backup_obj->server 	= FS_DB_HOST;
				$backup_obj->port 	= FS_DB_PORT;
				$backup_obj->username 	= FS_DB_USER;
				$backup_obj->password 	= FS_DB_PASS;
				$backup_obj->database 	= FS_DB_NAME;
				
				//Tables you wish to backup. All tables in the database will be backed up if this array is null.
				$backup_obj->tables = $datos;

		//------------------------ END - REQUIRED SETUP VARIABLES -----------------------
		
		//-------------------- OPTIONAL PREFERENCE VARIABLES ---------------------
				
				//Add DROP TABLE IF EXISTS queries before CREATE TABLE in backup file.
				$backup_obj->drop_tables = true;
				
				//Only structure of the tables will be backed up if true.
				$backup_obj->struct_only = $estruc;
				
				//Include comments in backup file if true.
				$backup_obj->comments = true;
				
				//Directory on the server where the backup file will be placed. Used only if task parameter equals MSB_SAVE.
				$backup_obj->backup_dir = $this->crear_dir();
				
				//Default file name format.
				$backup_obj->fname_format = "d-m-Y_H-i-s";

		//--------------------- END - OPTIONAL PREFERENCE VARIABLES ---------------------
				
		//---------------------- EDIT - REQUIRED EXECUTE VARIABLES ----------------------		
					
				/*
					Task: 
						MSB_STRING - Return SQL commands as a single output string.
						MSB_SAVE - Create the backup file on the server.
						MSB_DOWNLOAD - Download backup file to the user's computer.
						
				*/
				$task = MSB_SAVE;
				
				//Optional name of backup file if using 'MSB_SAVE' or 'MSB_DOWNLOAD'. If nothing is passed, the default file name format will be used.
				$filename = "_" . $version . "_FacturaSctipts_V" . $this->version();
				
				//Use GZip compression if using 'MSB_SAVE' or 'MSB_DOWNLOAD'?
				$use_gzip = true;

		//--------------------- END - REQUIRED EXECUTE VARIABLES ----------------------
		
		//-------------------- NO NEED TO ANYTHING BELOW THIS LINE -------------------- 
		return $backup_obj->Execute($task, $filename, $use_gzip);
	}
	
	// Crear Carpeta y proteger Acceso directo desde el Navegador
	public function crear_dir($carpetabk = CAR_BK_MS)
	{
		// Si no existe la carpeta, la creamos.
		if(!file_exists($carpetabk)) { @mkdir($carpetabk, 0755); }

		// Creamos el archivo .htaccess para proteger el acceso externo a la carpeta
		$htacces = $carpetabk.'.htaccess';
		if (!file_exists($htacces))
		{	
			$fp = fopen($htacces,"w");
			fwrite($fp,'<Files *>'."\r\n");
			fwrite($fp,'Order Allow,Deny'."\r\n");
			fwrite($fp,'Deny from All'."\r\n");
			fwrite($fp,'</Files>'."\r\n");
			fwrite($fp,'Options -Indexes'."\r\n");
			fclose($fp);
		}
		// Creamos un archivo index.html vacio
		$hindex = $carpetabk.'index.html';
		if (!file_exists($hindex))
		{
			$fp = fopen($hindex,"w");
			fwrite($fp,'<html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></head><body bgcolor="#000000" text="#FFFFFF">Acceso No Permitido !!!</body></html>'."\r\n");
			fclose($fp);
		}
		return $carpetabk;
	}
	
	// Descargar Backup
	public function baja_backup($fname)
	{
		// Salida del Backup forzando la descarga
		if(file_exists( CAR_BK_MS . $fname))
		{
			header ("Content-Disposition: attachment; filename=".$fname);
			header ("Content-Type: application/octet-stream");
			header ("Content-Length: ".filesize( CAR_BK_MS . $fname ));
			readfile( CAR_BK_MS . $fname );
		}
	}
}