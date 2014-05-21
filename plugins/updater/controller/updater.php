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
	public function __construct()
	{
		parent::__construct(__CLASS__, 'Buscar actualizaciones', 'admin', TRUE, TRUE);
	}
	
	protected function process()
	{	
		$tmpfile = sys_get_temp_dir().DIRECTORY_SEPARATOR."tmpfile.zip";
		
		if( isset($_GET['buscar']) )
		{
			file_put_contents($tmpfile, fopen("https://github.com/NeoRazorX/facturascripts/archive/master.zip", 'r'));
			$releaseZip = new PclZip($tmpfile);
			if ($releaseZip->extract(PCLZIP_OPT_PATH, sys_get_temp_dir(),PCLZIP_OPT_BY_NAME, 
									'facturascripts-master'.DIRECTORY_SEPARATOR.'VERSION',
									PCLZIP_OPT_REMOVE_ALL_PATH) == 0) 
			{
				$this->new_error_msg("Hubo un error al descomprimir el fichero remoto: ".$releaseZip->errorInfo(true));
  			}
			$releaseVersion = file_get_contents(sys_get_temp_dir().DIRECTORY_SEPARATOR.'VERSION');
			if (strcmp($this->getVersion(), $releaseVersion) == 0)
			{
				$this->new_message("Est&aacute;s actualizado a la &uacute;ltima versi&oacute;n de facturascripts: ".$releaseVersion);
				unlink(sys_get_temp_dir().DIRECTORY_SEPARATOR.'VERSION');
				unlink($tmpfile);
			}
			else
			{	
				unlink(sys_get_temp_dir().DIRECTORY_SEPARATOR.'VERSION');
				$this->new_message("Hay una versi&oacute;n distinta disponible: tu versi&oacute;n es la "
						.$this->getVersion()." y est&aacute; disponible la ".$releaseVersion.
						"&nbsp;&nbsp;&nbsp;&nbsp;<a class='submit' style='padding: 10px;' href='index.php?page=".$this->template."&actualizar=TRUE'>ACTUALIZAR</a>");
				$this->new_message("ATENCI&Oacute;N: HAZ UN BACKUP COMPLETO DE LA BASE DE DATOS ANTES DE ACTUALIZAR");
			}
		}
		// actualizar
		if( isset($_GET['actualizar']) )
		{
			if (file_exists($tmpfile))
			{
				$releaseZip = new PclZip($tmpfile);
				
				try {
					// en primer lugar hacemos copia de seguridad en el directorio temporal del sistema
					$backupDest = sys_get_temp_dir().DIRECTORY_SEPARATOR.basename(getcwd())."-".date("dmy");
					$this->__systemBackup(getcwd(), $backupDest);
					
					// descomprimimos
					if ($releaseZip->extract(PCLZIP_OPT_REMOVE_PATH, "/facturascripts-master") == 0)					
						throw new Exception("Hubo un error al descomprimir el fichero remoto: ".$releaseZip->errorInfo(true));					
					
					// si no hubo errores al descomprimir, copiamos todo lo descomprimido en 
					// el directorio del apache al que apunta actualmente facturascripts y renombramos el "viejo"
					$basenameold = basename(getcwd());
					$parent = dirname (getcwd());
					$newnameold = $basenameold."-".date("dmy");
					
					if (!rename(getcwd(), $parent.DIRECTORY_SEPARATOR.$newnameold))
						throw new Exception("Hubo un error al renombrar el directorio de trabajo actual: ".getcwd());
					
					if (!rename(sys_get_temp_dir().DIRECTORY_SEPARATOR."tmpfctscrpts".DIRECTORY_SEPARATOR."facturascripts-master",
						$parent.DIRECTORY_SEPARATOR.$basenameold))
						throw new Exception("Hubo un error al actualizar. Hay una copia de los antiguos ficheros en: ".
											$parent.DIRECTORY_SEPARATOR.$newnameold);
					// copiamos config
					if (!copy($parent.DIRECTORY_SEPARATOR.$newnameold.DIRECTORY_SEPARATOR."config.php", 
						  $parent.DIRECTORY_SEPARATOR.$basenameold.DIRECTORY_SEPARATOR."config.php"));
						throw new Exception("Hubo un error al copiar la configuración antigua en la actualización.".
											"Hay una copia de los antiguos ficheros en: ".
											$parent.DIRECTORY_SEPARATOR.$newnameold);
					// permisos de escritura para tmp
					if (!chmod($parent.DIRECTORY_SEPARATOR.$basenameold.DIRECTORY_SEPARATOR."tmp",0755));
						throw new Exception("Hubo un error al dar permiso de escritura a la carpeta tmp. Por favor, hazlo a mano");
						
					// todo fue ok
					$this->new_message("ENHORABUENA, FACTURASCRIPTS SE HA ACTUALIZADO. Por favor, sal y vuelve a entrar en el programa");
					rmdir(sys_get_temp_dir().DIRECTORY_SEPARATOR."tmpfctscrpts");
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
	
	public function getVersion()
	{
		return file_get_contents('VERSION');
	}
	
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
