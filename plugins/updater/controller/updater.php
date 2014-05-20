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
			file_put_contents(sys_get_temp_dir()."/Tmpfile.zip", fopen("https://github.com/NeoRazorX/facturascripts/archive/master.zip", 'r'));
		}
	}
	
	public function getVersion()
	{
		return file_get_contents('VERSION');
	}
}
 ?>