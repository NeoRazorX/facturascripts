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
		if( isset($_GET['buscar']) )
		{
			$tmpfile = sys_get_temp_dir().DIRECTORY_SEPARATOR."tmpfile.zip";
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
				$this->new_message("Hay una versi&oacute;n distinta disponible: tu versi&oacute;n es la "
						.$this->getVersion()." y est&aacute; disponible la ".$releaseVersion.
						"&nbsp;&nbsp;<a class='submit' style='padding: 10px;' href='index.php?page=".$this->template."&actualizar=TRUE'>ACTUALIZAR</a>");
				$this->new_message("ATENCI&Oacute;N: PROCEDE CON CUIDADO. HAZ UN BACKUP COMPLETO DE LA BASE DE DATOS ANTES DE ACTUALIZAR");
			}
		}
	}
	
	public function getVersion()
	{
		return file_get_contents('VERSION');
	}
}
 ?>