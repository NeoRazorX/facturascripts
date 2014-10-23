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

class ad_plugins
{

    public function __construct() { }

    // Cargar/Actualizar Archivos del Repositorio
    public function actualizarRepo()
    {
        $error = FALSE;
        if( !file_exists(CAR_GE_PLU) )
        {
            mkdir(CAR_GE_PLU);
            if( file_exists('VERSION') )
            {
                copy( 'VERSION', CAR_GE_PLU . '/VERSION' );
                return TRUE;
            }
            $error = TRUE;
        }

        if( !$oficial = @file_get_contents('VERSION') )
        {
            $error = TRUE;
        }
        else if( !$delrepo = @file_get_contents( CAR_GE_PLU . '/VERSION' ) )
        {
            $error = TRUE;
        }
        else if (strcmp($oficial, $delrepo) !== 0)
        {
            // Vaciamos el Directorio y copiamos la nueva version
            $this->EliminarDir(CAR_GE_PLU);
            copy( 'VERSION', CAR_GE_PLU . '/VERSION' );
            return TRUE;
        }

        // Algo ha fallado, activamos el Temporizador como segunda opcion
        if ($error)
        {
            if ($this->Temporizar())
            {
                // Vaciamos el Directorio y copiamos la nueva version
                $this->EliminarDir(CAR_GE_PLU);
                copy( 'VERSION', CAR_GE_PLU . '/VERSION' );
                return TRUE;
            }
        }
        return FALSE;
    }

    // Temporizador
    public function Temporizar()
    {
        session_start();
        if(!isset($_SESSION['time']))
        {
            $_SESSION['time'] = time();
            return TRUE;
        }
        else
        {
            if((time() - $_SESSION['time']) > 6000) // 6000 = Segundos para racargar el repositorio por completo
            {
                $_SESSION['time'] = time();
                return TRUE;
            }
        }
        return FALSE;
    }

    // Método que devuelve el Archivo ini con la lista NO oficial de Plugins de facturascripts
    public function getPluginsIni()
    {
        // Archivo ini hospedado en el servidor de descargas de facturascripts
        $url_lista = "http://s247039322.mialojamiento.es/repositorio_plugins.ini";   // Este es de ejemplo... Obvio... ¿no?....

        // Copiamos el archivo ini
        if( ($datos = @file_get_contents($url_lista)) && file_put_contents(CAR_GE_PLU.'/config.ini', $datos) )
        {
            return TRUE;
        }
        return FALSE;
    }

    // Copiamos los archivos ZIP relacionados en la lista NO oficial de Plugins de facturascripts
    // El Archivo que no sea ZIP se descarta
    public function getFilesZip()
    {
        $error = array();
        $nombre = array();
        // Cargamos el contenido del Archivo ini
        $todos = @parse_ini_file(CAR_GE_PLU.'/config.ini');

        foreach($todos['plugin_no_oficial'] as $key => $value)
        {
            $filezip = pathinfo($value);
            if( $filezip['extension'] == 'zip' )
            {
                // Copiamos el archivo
                if( ($datos = @file_get_contents($value)) && file_put_contents(CAR_GE_PLU . '/'. $filezip['basename'], $datos) )
                {
                    $nombre[] = $filezip['basename'];
                }
                else
                {
                    $error [] = 'ERROR: No se ha podido copiar el archivo '. $filezip['basename'];
                }
            }
            else
            {
                $error [] = 'ERROR: Se omite el archivo '. $filezip['basename'].'. Extension NO VALIDA.';
            }
        }

        ini_set('max_execution_time', 600);
        for($i=0; $i<count($nombre); $i++)
        {
            if ($salida = $this->getOneZip($nombre[$i]))
            {
                $error [] = $salida;
            }
        }
        return $error;
    }

    // Verificamos el archivo ZIP y lo Descomprimimos
    public function getOneZip($archivo)
    {
        require_once 'plugins/admin_plugins/pclzip/pclzip.lib.php';
        $error = FALSE;
        // Abrimos el archivo ZIP
        $zip = new PclZip(CAR_GE_PLU . '/'. $archivo);

        // ¿El ZIP no contiene nada...?
        if (($list = $zip->listContent()) == 0)
        {
            // Lo eliminamos
            unlink(CAR_GE_PLU . '/'. $archivo);
            $error = 'ERROR: Se omite el archivo '.$archivo.'. El Archivo esta Vacio.';
        }
        else
        {
            // Limitamos el tamaño de cada archivo ZIP a 1MB, si es mayor se elimina directamente
            if (filesize(CAR_GE_PLU . '/'. $archivo) > (1*1024*1024))
            {
                // Lo eliminamos
                unlink(CAR_GE_PLU . '/'. $archivo);
                $error = 'ERROR: Se omite el archivo '.$archivo.'. El Archivo es Mayor de 1 MB.';
            }
            else
            {
                $plugin = str_replace( '.zip' ,'',$archivo);

                // Chequeamos la RAIZ del archivo ZIP
                $pos = strpos($list[0]['filename'], $plugin.'/');
                if (($pos === false) || $list[0]['folder'] != 1)
                {
                    // Lo eliminamos
                    unlink(CAR_GE_PLU . '/'. $archivo);
                    $error = 'ERROR: Se omite el archivo '.$archivo.'. La Raiz del Archivo no coincide con el nombre del Archivo ZIP.';
                }
                else
                {
                    // Raiz sin espacios en blanco
                    $pos = strpos($list[0]['filename'], ' ');
                    if ($pos !== false)
                    {
                        // Lo eliminamos
                        unlink(CAR_GE_PLU . '/'. $archivo);
                        $error = 'ERROR: Se omite el archivo '.$archivo.'. La Raiz del Archivo contiene espacios en blanco.';
                    }
                    else
                    {
                        // Descomprimimos el archivo ZIP
                        if ($zip->extract(PCLZIP_OPT_PATH, CAR_GE_PLU . '/') == 0)
                        {
                            $error = 'ERROR: Se omite el archivo '.$archivo.'. No se ha podido descomprimir el Archivo.';
                        }
                        // Lo eliminamos
                        unlink(CAR_GE_PLU . '/'. $archivo);
                    }
                }
            }
        }
        return $error;
    }

    // Chequear estructura del Plugin
    public function ChequearArchivos($archivo)
    {
        // Definimos los valores por defecto a variables para chequeo del estado de la estructura de archivos
        $resultado = array();
        $lisdir = array('controller' => '.php', 'model' => '.php', 'view' => '.html');
        foreach($lisdir as $key => $value)
        {
            $resultado[$key] = 0;
            $resultado[$key.'_no'] = 0;
            $resultado[$key.'_si'] = 0;
            $carpeta = CAR_GE_PLU.'/'.$archivo.'/'.$key;
            if (is_dir ($carpeta))
            {
                $resultado[$key] = 1;
                $ArrFicheros = scandir ($carpeta);
                for ($i = 0; $i < count ($ArrFicheros); $i++)
                {
                    if ($ArrFicheros[$i] != "." && $ArrFicheros[$i] != "..")
                    {
                        if (is_file ($carpeta . "/" . $ArrFicheros[$i]))
                        {
                            $pos = strpos($ArrFicheros[$i], $value);
                            if ($pos === false)
                            {
                                $resultado[$key.'_no'] = 1;
                            } else {
                                $resultado[$key.'_si'] = 1;
                            }
                        }
                    }
                }
            }
        }
        return $resultado;
    }

    // (Mensajes) -- Chequear estructura del Plugin
    public function MensajesArchivos($datos)
    {
        require_once 'plugins/admin_plugins/model/textos.php';
        $resultado = array();
        foreach($datos as $key => $value)
        {
            $valores = new stdClass;
            $valores->texto = $textos[$key]['texto'];
            if ($value == 0)
            {
                $valores->estado = $textos[$key]['no']['estado'];
                $valores->color  = $textos[$key]['no']['color'];
            }
            else
            {
                $valores->estado = $textos[$key]['si']['estado'];
                $valores->color  = $textos[$key]['si']['color'];
            }
            $resultado[] = $valores;
        }
        return $resultado;
    }

    // Chequeamos si existe el plugin o si esta habilitado
    public function chequear_plugin($ruta, $localizar)
    {
        foreach( scandir(getcwd().$ruta) as $f )
        {
            if( $f == $localizar ) { return TRUE; }
        }
        return FALSE;
    }

    // Eliminar Directorio por completo aunque NO este vacio
    // $dir = el directorio de destino
    // $DeleteMe = si es cierto también elimina $dir, si es falso no lo toca
    public function EliminarDir($dir = null, $DeleteMe = FALSE)
    {
        if(!$dh = @opendir($dir)) return;
        while (false !== ($obj = readdir($dh))) {
            if($obj=='.' || $obj=='..') continue;
            if (!@unlink($dir.'/'.$obj)) $this->EliminarDir($dir.'/'.$obj, true);
        }
        if ($DeleteMe){
            closedir($dh);
            @rmdir($dir);
        }
    }

    // Copiar una carpeta completa
    public function CopiaDir( $source, $target )
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
