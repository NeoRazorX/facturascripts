<?php
/*
 * This file is a plugin developed for the software FacturaSctipts
 * Copyright (C) 2014  Valentín González    valengon@hotmail.com
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

if( !defined('FS_TMP_NAME') ) { define('FS_TMP_NAME', ''); }
define('CAR_GE_PLU', 'tmp/' . FS_TMP_NAME . 'ges_plu_work/' );

class gestion_plugins extends fs_controller
{
    private $oficiales;
    private $tablas = array();

    public function __construct()
    {
        $this->oficiales = $this->getPlugins();
        parent::__construct(__CLASS__, 'Gestión de Plugins', 'admin', TRUE, TRUE);

        $this->buttons[] = new fs_button('b_backupms', 'Plugins', 'index.php?page=admin_plugins');
    }

    protected function process()
    {
        $this->portada = true;
        $this->titulo = 'Panel de Gestión de PLUGINS';

        // Mostramos mensajes
        if( isset($_GET['mensaje']) )
        {
            $this->new_message($_GET['mensaje']);
        }

        if( isset($_GET['error']) )
        {
            $this->new_error_msg($_GET['error']);
        }

        // ELIMINAR del Servidor un Plugin que está presente en el REPOSITORIO
        if( isset($_GET['destruir']) )
        {
            if (file_exists(getcwd().'/plugins/'.$_GET['destruir']) && $this->chequear_plugin('/tmp/enabled_plugins', $_GET['destruir']) == FALSE)
            {
                $this->EliminarDir(getcwd().'/plugins/'.$_GET['destruir']);
                header( 'Location: '.$this->url().'&mensaje=Se ha eliminado correctamente del Servidor el Plugin :: ' . $_GET['destruir'] );
                die();
            }
            else
            {
                header( 'Location: '.$this->url().'&error=ERROR al Eliminar el Plugin :: ' . $_GET['destruir'] . '. No se puede Eliminar un Plugin que no existe o que está previamente Activado.' );
                die();
            }
        }

        // Instalar en el Servidor un Plugin desde el REPOSITORIO
        if( isset($_GET['instalar']) )
        {
            if (file_exists(getcwd().'/plugins/'.$_GET['instalar']) || $this->chequear_plugin('/tmp/enabled_plugins', $_GET['instalar']) == TRUE)
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin :: ' . $_GET['instalar'] . '. Ya existe en el Servidor.' );
                die();
            }

            $filezip = pathinfo($this->getPlugins($_GET['instalar']));
            if( $filezip['extension'] != 'zip' )
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin :: ' . $_GET['instalar'] .'.'. $filezip['extension'] . '. Solo se Admiten archivos con formato ZIP.' );
                die();
            }

            $this->CreaDir(CAR_GE_PLU);
            ini_set('max_execution_time', 600);

            // Copiamos el archivo remoto
            if( ($datos = file_get_contents($this->getPlugins($_GET['instalar']))) && file_put_contents(CAR_GE_PLU . $filezip['basename'], $datos) )
            {
                $this->Chequea_Zip($filezip['basename']);
            }
            else
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin :: ' . $_GET['instalar'] .'.'. $filezip['extension'] . '. No se pudo transferir el archivo ZIP desde la URL remota.' );
                die();
            }
        }

        // Instalar en el Servidor un Plugin desde una URL Externa
        if( isset($_POST['pluginexterno']) )
        {
            $filezip = pathinfo($_POST['pluginexterno']);
            if( $filezip['extension'] != 'zip' )
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $filezip['filename'] .'.'. $filezip['extension'] . '. Solo se Admiten archivos con formato ZIP.' );
                die();
            }

            if (file_exists(getcwd().'/plugins/'.$filezip['filename']) || $this->chequear_plugin('/tmp/enabled_plugins', $filezip['filename']) == TRUE)
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $filezip['filename'] . '. Ya existe en el Servidor.' );
                die();
            }

            $this->CreaDir(CAR_GE_PLU);
            ini_set('max_execution_time', 600);

            // Copiamos el archivo remoto
            if( ($datos = file_get_contents($_POST['pluginexterno'])) && file_put_contents(CAR_GE_PLU . $filezip['basename'], $datos) )
            {
                $this->Chequea_Zip($filezip['basename']);
            }
            else
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $_GET['instalar'] .'.'. $filezip['extension'] . '. No se pudo transferir el archivo ZIP desde la URL remota.' );
                die();
            }
        }

        // Instalar en el Servidor un Plugin desde el CLIENTE
        if( isset($_POST['subirlocal']) )
        {
            if ($_FILES['pluginlocal']['name'] == '')
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: No se ha seleccionado ningún Archivo.' );
                die();
            }

            $pos = strpos($_FILES['pluginlocal']['type'], 'zip');
            if ($pos === false)
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: ' . $_FILES['pluginlocal']['name'] . '. Solo se Admiten archivos con formato ZIP.' );
                die();
            }
            $plugin = str_replace( '.zip' ,'', $_FILES['pluginlocal']['name']);

            if (file_exists(getcwd().'/plugins/'.$plugin) || $this->chequear_plugin('/tmp/enabled_plugins', $plugin) == TRUE)
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: ' . $plugin . '. Ya existe en el Servidor.' );
                die();
            }

            $this->CreaDir(CAR_GE_PLU);
            ini_set('max_execution_time', 600);

            // Copiamos el archivo remoto
            if (move_uploaded_file($_FILES['pluginlocal']['tmp_name'], CAR_GE_PLU.$_FILES['pluginlocal']['name']))
            {
                $this->Chequea_Zip($_FILES['pluginlocal']['name']);
            }
            else
            {
                header( 'Location: '.$this->url().'&error=ERROR al instalar el Plugin :: ' . $_FILES['pluginlocal']['name'] . '. No se pudo transferir el archivo ZIP desde Nuestro Ordenador.' );
                die();
            }
        }

        // Cargamos la lista oficial de Plugins de facturascripts
        if($this->oficiales)
        {
            $this->repositorio = true;
            $this->tabla = $this->oficiales;
        }
        else
        {
            $this->repositorio = false;
        }

    }

    /* Método que devuelve la lista oficial de Plugins de facturascripts */
    public function getPlugins($purl = FALSE)
    {
        // Archivo ini hospedado en el servidor de descargas de facturascripts
        $url_lista = "http://s247039322.mialojamiento.es/config.ini";   // Este es de ejemplo... Obvio... ¿no?....

        /// Ñapa para solventar bug aleatorio de parse_ini_file() dependiendo del hosting donde se encuentre el archivo ini
        // Copiamos el archivo ini
        if( ($datos = @file_get_contents($url_lista)) && file_put_contents('tmp/gesplug.ini', $datos) )
        {
            // Cargamos el contenido del Archivo ini y eliminamos el archivo
            $todos = @parse_ini_file('tmp/gesplug.ini');
            @sort($todos['plugin_estable']);
            @sort($todos['plugin_inestable']);
            unlink('tmp/gesplug.ini');
        }
        else
        {
            $todos['plugin_estable'] = array();
            $todos['plugin_inestable'] = array();
        }

        if($purl)
        {
            if ($this->getDatPlugins($todos['plugin_estable'], TRUE, $purl)) { return $this->getDatPlugins($todos['plugin_estable'], TRUE, $purl); }

            if ($this->getDatPlugins($todos['plugin_inestable'], TRUE, $purl)) { return $this->getDatPlugins($todos['plugin_inestable'], TRUE, $purl); }
            header( 'Location: '.$this->url().'&error=Error Inesperado: No se puede localizar el enlace de Descarga del Plugin :: '.$purl );
            die();
        }
        else
        {
            $this->getDatPlugins($todos['plugin_estable'], TRUE, FALSE);
            $this->getDatPlugins($todos['plugin_inestable'], FALSE, FALSE);
            return $this->tablas;
        }
    }

    // Recopilamos informacion de los Plugin a partir del archivo ini
    // Si definimos $purl en la llamada, devuelve la URL de descarga del Plugin
    public function getDatPlugins($datos, $estable, $purl)
    {
        for($ii=0; $ii<count($datos); $ii++)
        {
            $partes = pathinfo($datos[$ii]);
            if($purl)
            {
                if($partes['filename'] == $purl)
                {
                    return $datos[$ii];
                }
            }
            $valores = new stdClass;
            $valores->nombre        = $partes['filename'];
            $valores->estable       = $estable;
            $valores->instalado     = $this->chequear_plugin('/plugins', $partes['filename']);
            $valores->habilitado    = $this->chequear_plugin('/tmp/enabled_plugins', $partes['filename']);
            if($valores->instalado == TRUE || $valores->habilitado == TRUE)
            {
                $valores->instalar  = FALSE;
            }
            else
            {
                $valores->instalar  = TRUE;
            }
            if($valores->instalado == TRUE && $valores->habilitado == FALSE)
            {
                $valores->eliminar  = TRUE;
            }
            else
            {
                $valores->eliminar  = FALSE;
            }
            $this->tablas[]         = $valores;
        }

        if($purl) { return NULL; } else { return $this->tablas; }
    }

    // Chequear estructura del Plugin
    private function Chequea_Zip($archivo)
    {
        require_once 'plugins/gestion_plugins/pclzip/pclzip.lib.php';
        $this->portada = false;
        $this->titulo = 'Estructura del Plugin :: '. $archivo;

        // Abrimos el archivo ZIP
        $zip = new PclZip(CAR_GE_PLU . $archivo);

        // ¿El ZIP no contiene nada...?
        if (($list = $zip->listContent()) == 0)
        {
            header( 'Location: '.$this->url().'&error=Error con Archivo ZIP: El Archivo ZIP del Plugin ('.$archivo.') no contiene datos.' );
            die();
        }

        $plugin = str_replace( '.zip' ,'',$archivo);

        // Definimos los valores por defecto a variables para chequeo del estado de la estructura de archivos
        $resultado['raiz'] = 0;
        $resultado['controller'] = 0;
        $resultado['controller_php'] = 0;
        $resultado['controller_nophp'] = 0;
        $resultado['model'] = 0;
        $resultado['model_php'] = 0;
        $resultado['model_nophp'] = 0;
        $resultado['view'] = 0;
        $resultado['view_html'] = 0;
        $resultado['view_nohtml'] = 0;
        $resultado['description'] = 0;

        // Chequeamos el estado de la estructura de archivos a partir del archivo ZIP (Antes de Descomprimir)
        $pos = strpos($list[0]['filename'], $plugin.'/');
        if (($pos !== false) && $list[0]['folder'] == 1)
        {
            $resultado['raiz'] = 1;
        }

        for ($i=0; $i< count($list); $i++)
        {
            $pos = strpos($list[$i]['filename'], $plugin.'/controller/');
            if ($pos !== false)
            {
                $resultado['controller'] = 1;
                $filesphp = pathinfo($list[$i]['filename']);
                if (isset($filesphp['extension']))
                {
                    if ($filesphp['extension'] == 'php')
                    {
                        $resultado['controller_php'] = 1;
                    }
                    if ($filesphp['extension'] != 'php')
                    {
                        $resultado['controller_nophp'] = 1;
                    }
                }
            }

            $pos = strpos($list[$i]['filename'], $plugin.'/model/');
            if ($pos !== false)
            {
                $resultado['model'] = 1;
                $filesphp = pathinfo($list[$i]['filename']);
                if (isset($filesphp['extension']))
                {
                    if ($filesphp['extension'] == 'php')
                    {
                        $resultado['model_php'] = 1;
                    }
                    if ($filesphp['extension'] != 'php')
                    {
                        $resultado['model_nophp'] = 1;
                    }
                }
            }

            $pos = strpos($list[$i]['filename'], $plugin.'/view/');
            if ($pos !== false)
            {
                $resultado['view'] = 1;
                $filesphp = pathinfo($list[$i]['filename']);
                if (isset($filesphp['extension']))
                {
                    if ($filesphp['extension'] == 'html')
                    {
                        $resultado['view_html'] = 1;
                    }
                    if ($filesphp['extension'] != 'html')
                    {
                        $resultado['view_nohtml'] = 1;
                    }
                }
            }

            $pos = strpos($list[$i]['filename'], $plugin.'/description');
            if ($pos !== false)
            {
                $resultado['description'] = 1;
            }
        }

        // Asignamos mensajes con relacion al estado de la estructura de archivos
        foreach($resultado as $key => $value)
        {
            $valores = new stdClass;
            if ($key == 'raiz')
            {
                $valores->texto = 'Carpeta Raiz del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ERROR: No existe la carpeta Raiz del Plugin o no tiene el mismo nombre que el archivo ZIP.';
                    $valores->color  = 'bg-danger';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'controller')
            {
                $valores->texto = 'Carpeta Controller del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existe la carpeta Controller del Plugin. Se generará una sin contenido.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'controller_php')
            {
                $valores->texto = 'Archivos PHP incluidos en la Carpeta Controller del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existen archivos PHP dentro de la carpeta Controller del Plugin.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'controller_nophp')
            {
                $valores->texto = 'Archivos NO PHP incluidos en la Carpeta Controller del Plugin';
                if ($value == 1)
                {
                    $valores->estado = 'ERROR: EXISTEN archivos que NO tienen extension PHP dentro de la carpeta Controller del Plugin.';
                    $valores->color  = 'bg-danger';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'model')
            {
                $valores->texto = 'Carpeta Model del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existe la carpeta Model del Plugin. Se generará una sin contenido.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'model_php')
            {
                $valores->texto = 'Archivos PHP incluidos en la Carpeta Model del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existen archivos PHP dentro de la carpeta Model del Plugin.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'model_nophp')
            {
                $valores->texto = 'Archivos NO PHP incluidos en la Carpeta Model del Plugin';
                if ($value == 1)
                {
                    $valores->estado = 'ERROR: EXISTEN archivos que NO tienen extension PHP dentro de la carpeta Model del Plugin.';
                    $valores->color  = 'bg-danger';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'view')
            {
                $valores->texto = 'Carpeta View del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existe la carpeta View del Plugin. Se generará una sin contenido.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'view_html')
            {
                $valores->texto = 'Archivos HTML incluidos en la Carpeta View del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ADVERTENCIA: No existen archivos HTML dentro de la carpeta View del Plugin.';
                    $valores->color  = 'bg-warning';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'view_nohtml')
            {
                $valores->texto = 'Archivos NO HTML incluidos en la Carpeta View del Plugin';
                if ($value == 1)
                {
                    $valores->estado = 'ERROR: EXISTEN archivos que NO tienen extension HTML dentro de la carpeta View del Plugin.';
                    $valores->color  = 'bg-danger';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            if ($key == 'description')
            {
                $valores->texto = 'Archivo con la Descripcion del Plugin';
                if ($value == 0)
                {
                    $valores->estado = 'ERROR: No existe el Archivo con la Descripcion del Plugin.';
                    $valores->color  = 'bg-danger';
                }
                else
                {
                    $valores->estado = 'CORRECTO';
                    $valores->color  = 'bg-success';
                }
            }
            $table[] = $valores;
        }
        $this->table = $table;

        // Verificamos si existen errores que impidan instalar el Plugin
        if ( $resultado['raiz'] == 0 || $resultado['controller_nophp'] == 1 || $resultado['model_nophp'] == 1 || $resultado['view_nohtml'] == 1 || $resultado['description'] == 0 )
        {
            $this->new_error_msg('ERROR DE INSTALACION: El Archivo ZIP del Plugin ('.$archivo.') no contiene una estructura de datos Correcta.');
            $this->new_error_msg('(Ver más abajo detalle de los Errores localizados). --- NO SE HA INSTALADO EL PLUGIN ---');

            // Eliminamos el contenido temporal
            $this->EliminarDir(CAR_GE_PLU);
        }
        else
        {
            // Descomprimimos el archivo y lo copiamos en la carpeta plugins
            if ($zip->extract(PCLZIP_OPT_PATH, CAR_GE_PLU) == 0)
            {
                $this->new_error_msg('ERROR DE INSTALACION: Hubo un error al descomprimir el Archivo ZIP del Plugin: '.$zip->errorInfo(true));
            }

            // Verificamos la integridad de las carpetas y creamos la que no exista
            if (!is_dir(CAR_GE_PLU . $plugin . '/controller/')) { @mkdir(CAR_GE_PLU . $plugin . '/controller/', 0755); }
            if (!is_dir(CAR_GE_PLU . $plugin . '/model/')) { @mkdir(CAR_GE_PLU . $plugin . '/model/', 0755); }
            if (!is_dir(CAR_GE_PLU . $plugin . '/view/')) { @mkdir(CAR_GE_PLU . $plugin . '/view/', 0755); }

            // Copiamos en la carpeta plugins
            $this->CopiaDir( CAR_GE_PLU . $plugin . '/', 'plugins/'.$plugin );

            // Eliminamos el contenido temporal
            $this->EliminarDir(CAR_GE_PLU);

            // Mensaje
            $this->new_message('OPERACION CONCLUIDA con EXITO: El Plugin ('.$plugin.') se ha instalado Correctamente.');
            $this->new_message('Ahora puedes Activarlo desde <a href="index.php?page=admin_plugins">AQUI</a>.');
        }
    }

    // Chequeamos si existe el plugin o si esta habilitado
    private function chequear_plugin($ruta, $localizar)
    {
        foreach( scandir(getcwd().$ruta) as $f )
        {
            if( $f == $localizar ) { return TRUE; }
        }
        return FALSE;
    }

    // Eliminar Directorio por completo aunque NO este vacio
    private function EliminarDir($carpeta)
    {
        foreach(glob($carpeta . "/*") as $archivos_carpeta)
        {
            if (is_dir($archivos_carpeta))
            {
                $this->EliminarDir($archivos_carpeta);
            }
            else
            {
                unlink($archivos_carpeta);
            }
        }
        rmdir($carpeta);
    }

    // Si el Directorio NO EXISTE, lo crea y SI EXISTE, elimina todos sus archivos
    private function CreaDir($carpeta)
    {
        if (!is_dir($carpeta))
        {
            @mkdir($carpeta, 0755);
        } else {
            $handle = opendir($carpeta);
            while ($file = readdir($handle))
            {
                if (is_file($carpeta.$file))
                {
                    unlink($carpeta.$file);
                }
            }
        }
    }

    // Copiar una carpeta completa
    private function CopiaDir( $source, $target )
    {
        if ( is_dir( $source ) ) {
            @mkdir( $target );
            $d = dir( $source );
            while ( FALSE !== ( $entry = $d->read() ) ) {
                if ( $entry == '.' || $entry == '..' ) {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if ( is_dir( $Entry ) ) {
                    $this->CopiaDir( $Entry, $target . '/' . $entry );
                    continue;
                }
                copy( $Entry, $target . '/' . $entry );
            }

            $d->close();
        } else {
            copy( $source, $target );
        }
    }

}
?>