<?php
/*
 * This file is a plugin developed for the software facturascripts
 * Copyright (C) 2014  César Sáez Rodríguez  NATHOO@lacalidad.es
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

require_once 'extras/pclzip/pclzip-2-8-2/pclzip.lib.php';
		
class updater extends fs_controller
{
	private $config;
	
	public function __construct()
	{
		parent::__construct(__CLASS__, 'Buscar actualizaciones', 'admin', TRUE, TRUE);
		$this->config = parse_ini_file("plugins/updater/config.ini");
	}
	
	protected function process()
	{	
		$tmpfile = sys_get_temp_dir().DIRECTORY_SEPARATOR."tmpfile.zip";
		
		if( isset($_GET['buscar']) )
		{
			try 
			{
				// descargamos la release
				$this->__downloadRelease($tmpfile);
				
				// obtenemos la versión de la release
				$releaseVersion = $this->__getReleaseVersion($tmpfile);
	
				if (strcmp($this->getVersion(), $releaseVersion) == 0)
				{
					$this->new_message("Est&aacute;s actualizado a la &uacute;ltima versi&oacute;n de facturascripts: ".$releaseVersion);				
					unlink($tmpfile);
				}
				else
				{	
					$this->new_message("Hay una versi&oacute;n distinta disponible: tu versi&oacute;n es la "
							.$this->getVersion()." y est&aacute; disponible la ".$releaseVersion.
							"&nbsp;&nbsp;&nbsp;&nbsp;<a class='submit' style='padding: 10px;' href='index.php?page=".$this->template."&actualizar=TRUE'>ACTUALIZAR</a>");
					$this->new_message("ATENCI&Oacute;N: HAZ UN BACKUP COMPLETO DE LA BASE DE DATOS ANTES DE ACTUALIZAR");
				}
			}
			catch (Exception $e)
			{
				$this->new_error_msg($e->getMessage());
			}
		}
		// actualizar
		if( isset($_GET['actualizar']) )
		{
			if (file_exists($tmpfile))
			{				
				try 
				{
					// comprobamos que tenemos permisos de escritura en el directorio
					$this->__checkForRights();
					
					// hacemos copia de seguridad en el directorio temporal del sistema
					$backupDest = sys_get_temp_dir().DIRECTORY_SEPARATOR.basename(getcwd())."-".date("dmy");
					$this->__systemBackup(getcwd(), $backupDest);
					
					// descomprimimos
					$this->__descomprime($tmpfile);		

					// permisos de escritura para tmp
					if (!chmod($parent.DIRECTORY_SEPARATOR.$basenameold.DIRECTORY_SEPARATOR."tmp",0755));
						throw new Exception("Hubo un error al dar permiso de escritura a la carpeta tmp. Por favor, hazlo a mano");
						
					// todo fue ok
					$this->new_message("FACTURASCRIPTS SE HA ACTUALIZADO. Por favor, sal y vuelve a entrar en el programa");
					$this->new_message("INFO: Ha quedado una copia de seguridad del sistema anterior en ".$backupDest);
					unlink($tmpfile);			
					
				}
				catch (Exception $e)
				{
					$this->new_error_msg($e->getMessage());
				}			
			}
			else
			{
				$this->new_error_msg("No está descargado el fichero de actualización. Pulsa el botón BUSCAR antes de actualizar");
			}
		}
	}
	
	
	/* Método que devuelve la versión actual de facturascripts */
	public function getVersion()
	{
		return file_get_contents($this->config['versionFile']);
	}	
	
	/* Método que devuelve la versión de la última release de facturascripts a partir de un fichero .ZIP */
	private function __getReleaseVersion($releaseZipFile)
	{
		$releaseZip = new PclZip($releaseZipFile);
		
		// extraemos el fichero VERSION
		if ($releaseZip->extract(PCLZIP_OPT_PATH, sys_get_temp_dir(),
								 PCLZIP_OPT_BY_NAME, $this->config['rootFolderOnRelease'].DIRECTORY_SEPARATOR.$this->config['versionFile'],
								 PCLZIP_OPT_REMOVE_ALL_PATH) == 0) 		
			throw new Exception("Hubo un error al descomprimir el fichero remoto: ".$releaseZip->errorInfo(true));
		
		
  		$version = file_get_contents(sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->config['versionFile']);
  		
  		// borramos el fichero
  		unlink(sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->config['versionFile']);
		return $version;
	}	
	
	/* Método que se descarga la última versión de facturascripts en .zip del servidor indicado en la configuración */
	private function __downloadRelease($dest)
	{		
		if(file_put_contents($dest, fopen($this->config['remoteServer'], 'r')) === 0)
			throw new Exception("Error al descargar la &uacute;ltima versi&oacute;n del servidor");
	}
	
	/* Método que descomprime un fichero .zip en el directorio de trabajo actual */
	private function __descomprime($zipFile)
	{
		$releaseZip = new PclZip($zipFile);
		if ($releaseZip->extract(PCLZIP_OPT_REMOVE_PATH, "/".$this->config['rootFolderOnRelease']) == 0)					
			throw new Exception("Hubo un error al descomprimir el fichero remoto: ".$releaseZip->errorInfo(true).
								"Se hizo una copia de seguridad del sistema anterior en ".$backupDest);				
	}
	
	/* Método que devuelve la versión actual de facturascripts */
	private function __checkForRights()
	{
		if (!is_writable(getcwd()))
			throw new Exception("El usuario con que se ejecuta el servidor web no tiene permisos de escritura en getcwd(). Imposible
									actualizar. P&oacute;ngase en contacto con el administrador de su servidor");
	}
	
	/* Método que realiza una copia de seguridad de source en dest */
	private function __systemBackup($source, $dest)
	{
		if (!mkdir($dest))
			throw new Exception("Hubo un problema al crear la copia de seguridad: error creando el directorio ".
								$dest);
					
		$dirIterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$recursiveIteratordir = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
		foreach ($recursiveIteratordir as $item)
		{
			if ($item->isDir()) 
			{
				if (!mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()))
					throw new Exception("Hubo un problema al crear la copia de seguridad: error creando el directorio ".
										$dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
			else
			{
				if (!copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()))
					throw new Exception("Hubo un problema al crear la copia de seguridad: error al copiar el fichero ".
										$item." en ".$dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
		}
	}
}
 ?>
