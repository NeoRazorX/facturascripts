<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Valentín González    valengon@gmail.com
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

define('CAR_GE_PLU', 'tmp/repositorio_plugins' );
require_model('ad_plugins.php');

if(class_exists('admin_plugins') != true)
{
   
class admin_plugins extends fs_controller
{
    public $unstables;
    public $ad_plugin;
    public $seccion;
    public $cargando;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Plugins', 'admin', TRUE, TRUE);

        $this->buttons[] = new fs_button('b_plugins', 'Actualizar Contenido del Repositorio', $this->url().'&cargar=repositorio');
    }

    protected function process()
    {
        $this->ad_plugin = new ad_plugins();

        $this->unstables = FALSE;
        $this->seccion = 1;

        // Mostramos mensajes
        if( isset($_GET['mensaje']) )
        {
            $this->new_message($_GET['mensaje']);
        }

        if( isset($_GET['error']) )
        {
            $this->new_error_msg($_GET['error']);
        }

        if( isset($_GET['errores']) )
        {
            $a = stripslashes($_GET['errores']);
            $errores = unserialize($a);
            foreach ($errores AS $clave => $valor)
            $this->new_error_msg($valor);
        }

        if(FS_DEMO)
        {
            $this->new_error_msg('En el modo demo no se pueden activar/desactivar plugins.
            Sería muy molesto para los demás visitantes.');
        }
        else
        {
            if( isset($_GET['seccion']) )
            {
                $this->seccion = $_GET['seccion'];

                if($this->seccion == 2)
                {
                    $this->unstables = TRUE;
                }
                if($this->seccion == 3)
                {
                    // Llamamos al proceso de Actualizacion de Archivos del Repositorio
                    if( $this->ad_plugin->actualizarRepo() )
                    {
                        header( 'Location: '.$this->url().'&cargando=previa&seccion=3' );
                        die();
                    }

                    if( isset($_GET['cargando']) )
                    {
                        if( $_GET['cargando'] == 'previa' )
                        {
                            $this->cargando = TRUE;
                        }
                        if( $_GET['cargando'] == 'activada' )
                        {
                            // Copiamos el Archivo ini
                            if( $this->ad_plugin->getPluginsIni() )
                            {
                                // Copiar Archivos ZIP del Repositorio
                                $errores = $this->ad_plugin->getFilesZip();
                                if (count($errores) > 0)
                                {
                                    $compactar = serialize($errores);
                                    $compactada = urlencode($compactar);
                                    header( 'Location: '.$this->url().'&seccion=3&errores='.$compactada );
                                    die();
                                }
                            }
                        }
                    }
                }
            }

            if( isset($_GET['enable']) )
            {
                $this->enable_plugin($_GET['enable']);
            }

            if( isset($_GET['disable']) )
            {
                $this->disable_plugin($_GET['disable']);
            }

            if( isset($_GET['cargar']) )
            {
                if( $_GET['cargar'] == 'repositorio' )
                {
                    if( file_exists(CAR_GE_PLU) )
                    {
                        // Vaciamos el Directorio y copiamos la nueva version
                        $this->ad_plugin->EliminarDir(CAR_GE_PLU);
                        copy( 'VERSION', CAR_GE_PLU . '/VERSION' );
                    } else {
                        // Creamos el Directorio y copiamos la nueva version
                        mkdir(CAR_GE_PLU);
                        if( file_exists('VERSION') ) { copy( 'VERSION', CAR_GE_PLU . '/VERSION' ); }
                    }
                    header( 'Location: '.$this->url().'&cargando=previa&seccion=3' );
                    die();
                }
            }

            // ELIMINAR del Servidor un Plugin que está presente en el REPOSITORIO
            if( isset($_GET['destruir']) )
            {
                if (file_exists(getcwd().'/plugins/'.$_GET['destruir']) && $this->ad_plugin->chequear_plugin('/tmp/enabled_plugins', $_GET['destruir']) == FALSE)
                {
                    $this->ad_plugin->EliminarDir(getcwd().'/plugins/'.$_GET['destruir'], true);
                    header( 'Location: '.$this->url().'&seccion=3&mensaje=Se ha eliminado correctamente del Servidor el Plugin :: ' . $_GET['destruir'] );
                    die();
                }
                else
                {
                    header( 'Location: '.$this->url().'&seccion=3&error=ERROR al Eliminar el Plugin :: ' . $_GET['destruir'] . '. No se puede Eliminar un Plugin que no existe o que está previamente Activado.' );
                    die();
                }
            }

            // Instalar en el Servidor un Plugin desde el REPOSITORIO
            if( isset($_GET['instalar']) )
            {
                if (file_exists(getcwd().'/plugins/'.$_GET['instalar']) || $this->ad_plugin->chequear_plugin('/tmp/enabled_plugins', $_GET['instalar']) == TRUE)
                {
                    header( 'Location: '.$this->url().'&seccion=3&error=ERROR al instalar el Plugin :: ' . $_GET['instalar'] . '. Ya existe en el Servidor.' );
                    die();
                }
                $this->Copia_Plugin($_GET['instalar']);
            }

            // Instalar en el Servidor un Plugin desde una URL Externa
            if( isset($_POST['pluginexterno']) )
            {
                $this->seccion = 4;
                $filezip = pathinfo($_POST['pluginexterno']);
                if( $filezip['extension'] != 'zip' )
                {
                    header( 'Location: '.$this->url().'&seccion=4&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $filezip['filename'] .'.'. $filezip['extension'] . '. Solo se Admiten archivos con formato ZIP.' );
                    die();
                }

                if (file_exists(getcwd().'/plugins/'.$filezip['filename']) || file_exists(CAR_GE_PLU . '/' . $filezip['filename']) || $this->ad_plugin->chequear_plugin('/tmp/enabled_plugins', $filezip['filename']) == TRUE)
                {
                    header( 'Location: '.$this->url().'&seccion=4&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $filezip['filename'] . '. Ya existe en el Servidor o en el Repositorio.' );
                    die();
                }

                ini_set('max_execution_time', 600);

                // Copiamos el archivo remoto
                if( ($datos = file_get_contents($_POST['pluginexterno'])) && file_put_contents(CAR_GE_PLU . '/' . $filezip['basename'], $datos) )
                {
                    // Verificamos el archivo ZIP y lo Descomprimimos
                    if ( $error = $this->ad_plugin->getOneZip($filezip['basename']) )
                    {
                        header( 'Location: '.$this->url().'&seccion=4&error='.$error );
                        die();
                    }
                    else
                    {
                        $this->Copia_Plugin($filezip['filename']);
                        $this->ad_plugin->EliminarDir(CAR_GE_PLU . '/' . $filezip['filename'], true);
                    }
                }
                else
                {
                    header( 'Location: '.$this->url().'&seccion=4&error=ERROR al instalar el Plugin desde una URL Externa :: ' . $_POST['pluginexterno'] . '. No se pudo transferir el archivo ZIP desde la URL remota.' );
                    die();
                }
            }

            // Instalar en el Servidor un Plugin desde el CLIENTE
            if( isset($_POST['subirlocal']) )
            {
               if( !file_exists(CAR_GE_PLU) )
                  mkdir(CAR_GE_PLU);
               
                if ($_FILES['pluginlocal']['name'] == '')
                {
                    header( 'Location: '.$this->url().'&seccion=5&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: No se ha seleccionado ningún Archivo.' );
                    die();
                }

                $pos = strpos($_FILES['pluginlocal']['type'], 'zip');
                if ($pos === false)
                {
                    header( 'Location: '.$this->url().'&seccion=5&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: ' . $_FILES['pluginlocal']['name'] . '. Solo se Admiten archivos con formato ZIP.' );
                    die();
                }
                $plugin = str_replace( '.zip' ,'', $_FILES['pluginlocal']['name']);

                if (file_exists(getcwd().'/plugins/'.$plugin) || file_exists(CAR_GE_PLU . '/' . $plugin) || $this->ad_plugin->chequear_plugin('/tmp/enabled_plugins', $plugin) == TRUE)
                {
                    header( 'Location: '.$this->url().'&seccion=5&error=ERROR al instalar el Plugin desde Nuestro Ordenador :: ' . $plugin . '. Ya existe en el Servidor o en el Repositorio.' );
                    die();
                }

                ini_set('max_execution_time', 600);

                // Copiamos el archivo remoto
                if (move_uploaded_file($_FILES['pluginlocal']['tmp_name'], CAR_GE_PLU . '/' . $_FILES['pluginlocal']['name']))
                {
                    // Verificamos el archivo ZIP y lo Descomprimimos
                    if ( $error = $this->ad_plugin->getOneZip($_FILES['pluginlocal']['name']) )
                    {
                        header( 'Location: '.$this->url().'&seccion=5&error='.$error );
                        die();
                    }
                    else
                    {
                        $this->Copia_Plugin($plugin);
                        $this->ad_plugin->EliminarDir(CAR_GE_PLU . '/' . $plugin, true);
                    }
                }
                else
                {
                    header( 'Location: '.$this->url().'&seccion=5&error=ERROR al instalar el Plugin :: ' . $plugin . '. No se pudo transferir el archivo ZIP desde Nuestro Ordenador.' );
                    die();
                }
            }
        }
    }

    // Chequear estructura del Plugin e instalar
    public function Copia_Plugin($archivo)
    {
        $this->seccion = 6;
        $this->titulo = 'Estructura del Plugin :: '. $archivo;
        $this->table = array();
        $resultado = $this->ad_plugin->ChequearArchivos($archivo);
        $this->table = $this->ad_plugin->MensajesArchivos($resultado);

        // Verificamos si existen errores que impidan instalar el Plugin
        if ( $resultado['controller_no'] == 1 || $resultado['model_no'] == 1 || $resultado['view_no'] == 1 )
        {
            $this->new_error_msg('ERROR DE INSTALACION: El Archivo ZIP del Plugin ('.$archivo.') no contiene una estructura de datos Correcta.');
            $this->new_error_msg('(Ver más abajo detalle de los Errores localizados). --- NO SE HA INSTALADO EL PLUGIN ---');
        }
        else
        {
            // Verificamos la integridad de las carpetas y creamos la que no exista
            if (!is_dir(CAR_GE_PLU . '/' . $archivo . '/controller/')) { @mkdir(CAR_GE_PLU . '/' . $archivo . '/controller/', 0755); }
            if (!is_dir(CAR_GE_PLU . '/' . $archivo . '/model/')) { @mkdir(CAR_GE_PLU . '/' . $archivo . '/model/', 0755); }
            if (!is_dir(CAR_GE_PLU . '/' . $archivo . '/view/')) { @mkdir(CAR_GE_PLU . '/' . $archivo . '/view/', 0755); }

            // Copiamos en la carpeta plugins
            $this->ad_plugin->CopiaDir( CAR_GE_PLU . '/' . $archivo . '/', 'plugins/'.$archivo );

            // Mensaje
            $unesurl = (file_exists('plugins/'.$archivo.'/unstable'))?'&seccion=2':null;
            $this->new_message('OPERACION CONCLUIDA con EXITO: El Plugin ('.$archivo.') se ha instalado Correctamente.');
            $this->new_message('Ahora puedes Activarlo desde <a href="'.$this->url().$unesurl.'">AQUI</a>.');
        }
    }

    public function plugins()
    {
        $plugins = array();

        if( !file_exists('tmp/enabled_plugins') )
        mkdir('tmp/enabled_plugins');

        foreach( scandir(getcwd().'/plugins') as $f)
        {
            if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
            {
                $plugin = array(
                'name' => $f,
                'enabled' => file_exists('tmp/enabled_plugins/'.$f),
                'description' => 'Sin descripción'
                );

                if( file_exists('plugins/'.$f.'/description') )
                    $plugin['description'] = file_get_contents('plugins/'.$f.'/description');

                if( $this->unstables == file_exists('plugins/'.$f.'/unstable') )
                    $plugins[] = $plugin;
            }
        }
        return $plugins;
    }

    public function plugins_rep()
    {
        $plugins_rep = array();

        foreach( scandir(getcwd().'/'.CAR_GE_PLU) as $f)
        {
            if( is_dir(getcwd().'/'.CAR_GE_PLU.'/'.$f) )
            {
                if($f == '.' || $f == '..') { continue; }
                $plugin = array(
                'name' => $f,
                'inestable' => file_exists(CAR_GE_PLU.'/'.$f.'/unstable'),
                'instalado' => file_exists('plugins/'.$f),
                'enabled' => file_exists('tmp/enabled_plugins/'.$f),
                'description' => 'Sin descripción'
                );
                if( file_exists(CAR_GE_PLU.'/'.$f.'/description') )
                {
                    $plugin['description'] = file_get_contents(CAR_GE_PLU.'/'.$f.'/description');
                }
                $plugins_rep[] = $plugin;
            }
        }
        return $plugins_rep;
    }

    private function enable_plugin($name)
    {
        if( !file_exists('tmp/enabled_plugins/'.$name) )
        {
            if( touch('tmp/enabled_plugins/'.$name) )
            {
                $GLOBALS['plugins'][] = $name;

                /// activamos las páginas del plugin
                $page_list = array();
                foreach( scandir(getcwd().'/plugins/'.$name.'/controller') as $f)
                {
                    if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
                    {
                        $page_name = substr($f, 0, -4);
                        $page_list[] = $page_name;

                        require_once 'plugins/'.$name.'/controller/'.$f;
                        $new_fsc = new $page_name();

                        if( !$new_fsc->page->save() )
                            $this->new_error_msg("Imposible guardar la página ".$page_name);

                        unset($new_fsc);
                    }
                }

                $this->new_message('Plugin <b>'.$name.'</b> activado correctamente.');
                $this->new_message('Se han activado automáticamente las siguientes páginas: '.join(', ', $page_list) . '.');
                $this->load_menu(TRUE);

                /// limpiamos la caché
                $this->cache->clean();
            }
            else
            $this->new_error_msg('Imposible activar el plugin <b>'.$name.'</b>.');
        }
    }

    private function disable_plugin($name)
    {
        if( file_exists('tmp/enabled_plugins/'.$name) )
        {
            if( unlink('tmp/enabled_plugins/'.$name) )
            {
                $this->new_message('Plugin <b>'.$name.'</b> desactivado correctamente.');

                foreach($GLOBALS['plugins'] as $i => $value)
                {
                    if($value == $name)
                    {
                        unset($GLOBALS['plugins'][$i]);
                        break;
                    }
                }
            }
            else
            $this->new_error_msg('Imposible desactivar el plugin <b>'.$name.'</b>.');

            /*
            * Desactivamos las páginas que ya no existen
            */
            foreach($this->page->all() as $p)
            {
                $encontrada = FALSE;

                if( file_exists(getcwd().'/controller/'.$p->name.'.php') )
                {
                    $encontrada = TRUE;
                }
                else
                {
                    foreach($GLOBALS['plugins'] as $plugin)
                    {
                        if( file_exists(getcwd().'/plugins/'.$plugin.'/controller/'.$p->name.'.php') AND $name != $plugin)
                        {
                            $encontrada = TRUE;
                            break;
                        }
                    }
                }

                if( !$encontrada )
                {
                    if( $p->delete() )
                    {
                        $this->new_message('Se ha eliminado automáticamente la página '.$p->name);
                    }
                }
            }

            /// borramos los archivos temporales del motor de plantillas
            foreach( scandir(getcwd().'/tmp') as $f)
            {
                if( substr($f, -4) == '.php' )
                unlink('tmp/'.$f);
            }

            /// limpiamos la caché
            $this->cache->clean();
        }
    }
}

}