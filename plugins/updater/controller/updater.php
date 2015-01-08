<?php
/*
 * This file is a plugin developed for the software facturascripts
 * Copyright (C) 2014  César Sáez Rodríguez  NATHOO@lacalidad.es
 * Copyright (C) 2014  Carlos García Gómez   neorazorx@gmail.com
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

require_once 'plugins/updater/pclzip/pclzip.lib.php';
		
class updater extends fs_controller
{
	private $config;
   	public $hay_update;
	
	public function __construct()
	{
		$this->config = parse_ini_file("plugins/updater/config.ini");
		parent::__construct(__CLASS__, 'Actualizador', 'admin', TRUE, TRUE);		
	}
	
	protected function process()
	{	
      	$this->hay_update = FALSE;
		$tmpfile = getcwd().'/update.zip';
		
		if( isset($_GET['todo_ok']) )
		{
			$this->new_message("FACTURASCRIPTS SE HA ACTUALIZADO.");
			$this->new_message("Recuerda dar permisos de escritura a la carpeta tmp si no los tiene");
			$this->new_message("INFO: Ha quedado una copia de seguridad del sistema anterior en ".$_GET['todo_ok']);
		}
		else if( isset($_GET['buscar']) )
		{
			try 
			{
				// obtenemos la versión de la release
				$releaseVersion = $this->__getReleaseVersion();
            
				if (strcmp($this->getVersion(), $releaseVersion) >= 0)
				{ 
					$this->new_message("Est&aacute;s actualizado a la &uacute;ltima versi&oacute;n de facturascripts: ".$this->getVersion());
				}
				else
				{
               		$this->hay_update = TRUE;
               
					$this->new_message("La versión <b>".$releaseVersion."</b> de FacturaScripts está disponible.");
					$this->new_message("ATENCIÓN: HAZ UN BACKUP COMPLETO DE LA BASE DE DATOS ANTES DE ACTUALIZAR.");
				}
			}
			catch (Exception $e)
			{
				$this->new_error_msg($e->getMessage());
			}
		}
		else if( isset($_GET['actualizar']) ) // actualizar
		{
			try
			{
				$dirs = $this->__getAllSubDirectories(".",DIRECTORY_SEPARATOR);
				$nowr = $this->__areWritable($dirs);
				if (count($nowr) > 0)
				{
					$possixuid = posix_getuid();
					$possixpwuid = posix_getpwuid ( $possixuid );
					$message = "Los siguientes directorios no son escribibles: </br>";
					foreach ($nowr as $dir)
						$message .= $dir."</br>";
					$message .= "Por favor, conceda permisos de escritura al usuario '".$possixpwuid['name']."' para estos directorios antes de continuar";
					throw new Exception($message);
				}
				// descargamos la release
				$this->__downloadRelease($tmpfile);
				
				if( file_exists($tmpfile) )
				{					
						$path = getcwd();					
							
						// hacemos copia de seguridad en el directorio temporal del sistema
						$backupDest = getcwd().DIRECTORY_SEPARATOR.'backup-'.date("dmy-Hi");
						$this->__systemBackup(getcwd(), $backupDest);

						// llegados a este punto se creó correctamente la copia de seguridad (si no, habrían saltado excepciones)
						// borramos todo salvo config.php, los directorios de backup, el fichero descargado y tmp
						// Si algo falla, es importante mostrar la localización del fichero de backup !!
						try 
						{
							$this->__delete(".", $tmpfile);
						}
						catch (Exception $e)
						{
							$msg = "Ha habido un problema al borrar el sistema antiguo:</br>";
							$msg.= $e->getMessage();
							$msg.= "</br>Por favor, restablezca la copia de seguridad localizada en ".$backupDest;
							throw new Exception($msg);
						}						
				
						// descomprimimos
						$this->__descomprime($tmpfile);
				
						// permisos de escritura para tmp
						$mode = 775;
						chmod("tmp", octdec($mode));
				
						/// borramos el zip de la actualización
						unlink($tmpfile);
				
						/// borramos los archivos temporales del motor de plantillas
						foreach( scandir($path.'/tmp') as $f )
						{
							if( substr($f, -4) == '.php' )
								unlink($path.'/tmp/'.$f);
						}
				
						/// borramos la caché
						$this->cache->clean();
				
						/// recargamos
						header( 'Location: '.$this->url().'&todo_ok='.$backupDest );					
					
				}
				else
				{
					throw new Exception("No se ha podido descargado el fichero de actualización. "
							. "Vuelve a intentarlo en unos minutos.");
				}
			}
			catch (Exception $e)
			{
				$this->new_error_msg($e->getMessage());
			}
		}
	}
	
	/* Método que devuelve la versión actual de facturascripts */
	public function getVersion()
	{
      if( file_exists($this->config['versionFile']) )
         return file_get_contents($this->config['versionFile']);
      else
         return '0';
	}
	
	/* Método que devuelve la versión de la última release de facturascripts a partir de un fichero .ZIP */
	private function __getReleaseVersion()
	{
		return file_get_contents($this->config['remoteVersionFile']);
	}
	
	/* Método que se descarga la última versión de facturascripts en .zip del servidor indicado en la configuración */
	private function __downloadRelease($dest)
	{
      if( file_exists($dest) )
         unlink($dest);
      
		if( file_put_contents($dest, fopen($this->config['remoteServer'], 'r')) === 0 )
			throw new Exception("Error al descargar la &uacute;ltima versi&oacute;n del servidor");
	}
	
	/* Método que descomprime un fichero .zip en el directorio de trabajo actual */
	private function __descomprime($zipFile)
	{
		$releaseZip = new PclZip($zipFile);
		if ($releaseZip->extract(PCLZIP_OPT_REMOVE_PATH, $this->config['rootFolderOnRelease'], PCLZIP_OPT_REPLACE_NEWER) == 0)					
			throw new Exception("Hubo un error al descomprimir el fichero remoto: ".$releaseZip->errorInfo(true).
								"Se hizo una copia de seguridad del sistema anterior en ".$backupDest);				
	}
	
	/* Método que devuelve la versión actual de facturascripts */
	private function __checkForRights()
	{
		if( !is_writable(getcwd()) )
			throw new Exception("El usuario con que se ejecuta el servidor web no tiene permisos de escritura en ".getcwd().". Imposible
									actualizar. P&oacute;ngase en contacto con el administrador de su servidor.");
	}
	
	/* Método que realiza una copia de seguridad de source en dest */
	private function __systemBackup($source, $dest)
	{
      /// creamos el directorio destino
		if ( !mkdir($dest) )
			throw new Exception("Hubo un problema al crear la copia de seguridad: error creando el directorio ".$dest);
      
      /*
       * Hacemos una copia de todos los archivos, excepto el tmp, doc y los backups
       */
      foreach( scandir($source) as $f )
      {
         if( !in_array($f, array('.', '..', 'tmp', 'doc', 'update.zip')) AND strcmp(substr($f, 0, 6) , 'backup') != 0)
         {
            if( is_dir($f) )
            {
               if ( !mkdir($dest.DIRECTORY_SEPARATOR.$f))
               		throw new Exception("Hubo un problema al crear la copia de seguridad: error creando el directorio ".$dest.DIRECTORY_SEPARATOR.$f);
               $this->__copyr($source.DIRECTORY_SEPARATOR.$f, $dest.DIRECTORY_SEPARATOR.$f);
            }
            else
               copy($source.DIRECTORY_SEPARATOR.$f, $dest.DIRECTORY_SEPARATOR.$f);
         }
      }
	}
   
   /*
    * Copia recursiva de archivos
    */
   private function __copyr($source, $dest)
   {
      foreach ( $iterator = new RecursiveIteratorIterator(
                      			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                      			RecursiveIteratorIterator::SELF_FIRST) as $item )
      {
         if ($item->isDir())
         {
            if ( !mkdir( $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName() ))
            	throw new Exception("Hubo un problema al crear la copia de seguridad: error creando el directorio ".
            			$dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
         } 
         else
         {
            if ( !copy( $item, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName() ))
	            throw new Exception("Hubo un problema al crear la copia de seguridad: error copiando el fichero ".$item." en el directorio ".
	            		$dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
         }
      } // foreach
   }
   

   /*
    * Get Subdirectorios
   */
   private function __getAllSubDirectories( $directory, $directory_seperator )
   {
   		$dirs = array_map( function($item)use($directory_seperator){ return $item . $directory_seperator;}, array_filter( glob( $directory . '*' ), 'is_dir') );
   
	   	foreach( $dirs AS $dir )
	   	{
	   		if (strcmp ($dir, "../") != 0)
	   			$dirs = array_merge( $dirs, $this->__getAllSubDirectories( $dir, $directory_seperator ) );
	   	}
   
   		return $dirs;
   }
   
   /*
    * Comprueba que un listado de subdirectorios es escribible
    * Devuelve una lista con los no escribibles 
    *
    */
   private function __areWritable( $dirlist )
   { 
   		$notwritable = array();  	 
	   	foreach( $dirlist as $dir )
	   	{
		   	if (!is_writable($dir) && strcmp ($dir, "../") != 0)
		   		$notwritable[] = $dir;
	   	}   	 
   		return $notwritable;
   }
   
   /*
    * Borra el contenido de un directorio excluyendo config.php, tmp y directorios de backup
    *  Se le pueden pasar un fichero adicional (normalmente el de la release)
   *
   */
   private function __delete( $dir, $tmpfile )
   {
   	    // listamos los ficheros
   		$list = glob( $dir . '*' );
   		foreach ($list as $l)
   		{
   			if( !in_array($l, array('.', '..', 'tmp', 'config.php', $tmpfile)) AND strcmp(substr($l, 0, 6) , 'backup') != 0)
   			{
   				// si es directorio borramos recursivamente
   				if (is_dir($l))
   				{
	   				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path)
	   				{
	   					if($path->isDir() && !$path->isLink())
	   					{
	   						if (!rmdir($path->getPathname()))
	   							throw new Exception("Hubo un problema al borrar el directorio ".$path->getPathname());
	   					} 
	   					else
	   					{
	   						if (!unlink($path->getPathname()))
	   							throw new Exception("Hubo un problema al borrar el fichero ".$path->getPathname());
	   					}
	   				}
	   				if (!rmdir($dirPath))
	   					throw new Exception("Hubo un problema al borrar el directorio ".$dirPath);
   				}
   				// si no, borramos
   				else
   				{
   					if (!unlink($l))
	   					throw new Exception("Hubo un problema al borrar el fichero ".$l);
   				}
   			} // not in array
   		} // foreach
   }
}
