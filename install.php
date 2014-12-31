<?php

$nombre_archivo = "config.php";
error_reporting(E_ALL);
$errors = array();
$errors2 = array();

function random_string($length = 10)
{
   return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function guarda_config($nombre_archivo)
{
   $archivo = fopen($nombre_archivo, "w");
   fwrite($archivo, "<?php\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Configuración de la base de datos.\n");
   fwrite($archivo, " * type: postgresql o mysql (mysql está en fase experimental).\n");
   fwrite($archivo, " * host: la ip del ordenador donde está la base de datos.\n");
   fwrite($archivo, " * port: el puerto de la base de datos.\n");
   fwrite($archivo, " * name: el nombre de la base de datos.\n");
   fwrite($archivo, " * user: el usuario para conectar a la base de datos\n");
   fwrite($archivo, " * pass: la contraseña del usuario.\n");
   fwrite($archivo, " * history: TRUE si quieres ver todas las consultas que se hacen en cada página.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_DB_TYPE', '".$_REQUEST['db_type']."'); /// MYSQL o POSTGRESQL\n");
   fwrite($archivo, "define('FS_DB_HOST', '".$_REQUEST['db_host']."');\n");
   fwrite($archivo, "define('FS_DB_PORT', '".$_REQUEST['db_port']."'); /// MYSQL -> 3306, POSTGRESQL -> 5432\n");
   fwrite($archivo, "define('FS_DB_NAME', '".$_REQUEST['db_name']."');\n");
   fwrite($archivo, "define('FS_DB_USER', '".$_REQUEST['db_user']."'); /// MYSQL -> root, POSTGRESQL -> postgres\n");
   fwrite($archivo, "define('FS_DB_PASS', '".$_REQUEST['db_pass']."');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Un directorio de nombre aleatorio para mejorar la seguridad del directorio temporal.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_TMP_NAME', '".random_string()."/');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * En cada ejecución muestra todas las sentencias SQL utilizadas.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_DB_HISTORY', FALSE);\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Habilita el modo demo, para pruebas.\n");
   fwrite($archivo, " * Este modo permite hacer login con cualquier usuario y la contraseña demo,\n");
   fwrite($archivo, " * además deshabilita el límite de una conexión por usuario.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_DEMO', FALSE);\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Configuración de memcache.\n");
   fwrite($archivo, " * Host: la ip del servidor donde está memcached.\n");
   fwrite($archivo, " * port: el puerto en el que se ejecuta memcached.\n");
   fwrite($archivo, " * prefix: prefijo para las claves, por si tienes varias instancias de\n");
   fwrite($archivo, " * FacturaScripts conectadas al mismo servidor memcache.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "define('FS_CACHE_HOST', '".$_REQUEST['cache_host']."');\n");
   fwrite($archivo, "define('FS_CACHE_PORT', '".$_REQUEST['cache_port']."');\n");
   fwrite($archivo, "define('FS_CACHE_PREFIX', '".$_REQUEST['cache_prefix']."');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/// caducidad (en segundos) de todas las cookies\n");
   fwrite($archivo, "define('FS_COOKIES_EXPIRE', 315360000);\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/// el número de elementos a mostrar en pantalla\n");
   fwrite($archivo, "define('FS_ITEM_LIMIT', 50);\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Un número identificador para esta instancia de FacturaScripts.\n");
   fwrite($archivo, " * Necesario para identificar cada caja en el TPV.\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_ID', 1);\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Nombre o dirección de la impresora de tickets.\n");
   fwrite($archivo, " * '' -> impresora predefinida.\n");
   fwrite($archivo, " * 'epson234' -> impresora con nombre epson234.\n");
   fwrite($archivo, " * '/dev/usb/lp0' -> escribir diectamente sobre ese archivo.\n");
   fwrite($archivo, " * 'remote-printer' -> permite imprimir mediante el programa fs_remote_printer.py\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_PRINTER', 'remote-printer');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * Nombre o dirección de la impresora que representa el dispositivo\n");
   fwrite($archivo, " * LCD del terminal POS.\n");
   fwrite($archivo, " * El LCD tiene que ser de dos líneas de 20 caracteres cada una (GLANCETRON 8035).\n");
   fwrite($archivo, " * Si tienes alguna duda, escríbela\n");
   fwrite($archivo, " * aquí -> http://www.facturascripts.com\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_LCD', '');\n");
   fwrite($archivo, "\n");
   fwrite($archivo, "/*\n");
   fwrite($archivo, " * ¿Cuantos decimales quieres usar?\n");
   fwrite($archivo, " * ¿Qué separador usar para los decimales?\n");
   fwrite($archivo, " * ¿Qué separador usar para miles?\n");
   fwrite($archivo, " * ¿A qué lado quieres el símbolo de la divisa?\n");
   fwrite($archivo, " */\n");
   fwrite($archivo, "define('FS_NF0', ".$_REQUEST['num_nf0'].");\n");
   fwrite($archivo, "define('FS_NF1', '".$_REQUEST['num_nf1']."');\n");
   fwrite($archivo, "define('FS_NF2', '".$_REQUEST['num_nf2']."');\n");
   fwrite($archivo, "define('FS_POS_DIVISA', '".$_REQUEST['num_nf3']."');\n");
   fclose($archivo);
   
   header("Location: index.php");
   exit();
}

if( file_exists('config.php') )
{
   header('Location: index.php');
}
else if( floatval( substr(phpversion(), 0, 3) ) < 5.3 )
{
   $errors[] = 'php';
}
else if( !function_exists('mb_substr') )
{
   $errors[] = "mb_substr";
}
else if( !function_exists('bccomp') )
{
   $errors[] = "bccomp";
}
else if( !is_writable( getcwd() ) )
{
   $errors[] = "permisos";
}
else if( isset($_REQUEST['db_type']) )
{
   if($_REQUEST['db_type'] == 'MYSQL')
   {
      if( class_exists('mysqli') )
      {
         $connection = new mysqli($_REQUEST['db_host'], $_REQUEST['db_user'], $_REQUEST['db_pass'], $_REQUEST['db_name'], intval($_REQUEST['db_port']));
         if($connection->connect_error)
         {
            $errors[] = "db_mysql";
            $errors2[] = $connection->connect_error;
         }
         else
            guarda_config($nombre_archivo);
      }
      else
      {
         $errors[] = "db_mysql";
         $errors2[] = 'No tienes instalada la extensión de PHP para MySQL.';
      }
   }
   else if($_REQUEST['db_type'] == 'POSTGRESQL')
   {
      if( function_exists('pg_connect') )
      {
         $connection = pg_connect('host='.$_REQUEST['db_host'].' dbname='.$_REQUEST['db_name'].' port='.$_REQUEST['db_port'].
                 ' user='.$_REQUEST['db_user'].' password='.$_REQUEST['db_pass'] );
         if($connection)
         {
            guarda_config($nombre_archivo);
         }
         else
         {
            $errors[] = "db_postgresql";
            $errors2[] = 'No se puede conectar a la base de datos. Revisa los datos de usuario y contraseña.';
         }
      }
      else
      {
         $errors[] = "db_postgresql";
         $errors2[] = 'No tienes instalada la extensión de PHP para PostgreSQL.';
      }
   }
}

$system_info = 'facturascripts: '.file_get_contents('VERSION')."\n";
$system_info .= 'os: '.php_uname()."\n";
$system_info .= 'php: '.phpversion()."\n";

if( isset($_SERVER['REQUEST_URI']) )
{
   $system_info .= 'url: '.$_SERVER['REQUEST_URI']."\n------";
}
foreach($errors as $e)
{
   $system_info .= "\n" . $e;
}

$system_info = str_replace('"', "'", $system_info);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>FacturaScripts</title>
   <meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/AGPL." />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="shortcut icon" href="view/img/favicon.ico" />
   <link rel="stylesheet" href="view/css/bootstrap-yeti.min.css" />
   <link rel="stylesheet" href="view/css/datepicker.css" />
   <link rel="stylesheet" href="view/css/custom.css" />
   <script type="text/javascript" src="view/js/jquery-2.1.1.min.js"></script>
   <script type="text/javascript" src="view/js/bootstrap.min.js"></script>
   <script type="text/javascript" src="view/js/bootstrap-datepicker.js" charset="UTF-8"></script>
   <script type="text/javascript" src="view/js/jquery.autocomplete.min.js"></script>
   <script type="text/javascript" src="view/js/base.js"></script>
   <script type="text/javascript" src="view/js/jquery.validate.min.js"></script>
</head>
<body>
   <nav class="navbar navbar-default" role="navigation" style="margin: 0px;">
      <div class="container-fluid">
         <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">FacturaScripts</a>
         </div>
         
         <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
               <li>
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Ayuda">
                     <span class="glyphicon glyphicon-question-sign hidden-xs"></span>
                     <span class="visible-xs">Ayuda</span>
                  </a>
                  <ul class="dropdown-menu">
                     <li><a href="//www.facturascripts.com/community/questions.php" target="_blank">Preguntas</a></li>
                     <li><a href="//www.facturascripts.com/community/errors.php" target="_blank">Errores</a></li>
                     <li><a href="//www.facturascripts.com/community/ideas.php" target="_blank">Sugerencias</a></li>
                     <li><a href="//www.facturascripts.com/community/all.php" target="_blank">Todo</a></li>
                     <li class="divider"></li>
                     <li><a href="#" id="b_feedback">Informar...</a></li>
                  </ul>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <form name="f_feedback" action="//www.facturascripts.com/community/feedback.php" method="post" target="_blank" class="form" role="form">
      <input type="hidden" name="feedback_info" value="<?php echo $system_info; ?>"/>
      <input type="hidden" name="feedback_type" value="error"/>
      <div class="modal" id="modal_feedback">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title">¿Necesitas ayuda?</h4>
               </div>
               <div class="modal-body">
                  <div class="form-group">
                     <label for="feedback_textarea">Detalla tu duda o problema:</label>
                     <textarea id="feedback_textarea" class="form-control" name="feedback_text" rows="6"></textarea>
                  </div>
                  <div class="form-group">
                     <label for="exampleInputEmail1">Tu email</label>
                     <input type="email" class="form-control" id="exampleInputEmail1" name="feedback_email" placeholder="Introduce tu email"/>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="submit" class="btn btn-sm btn-primary">
                     <span class="glyphicon glyphicon-send"></span> &nbsp; Enviar
                  </button>
               </div>
            </div>
         </div>
      </div>
   </form>

   <script type="text/javascript">
      function change_db_type() {
         if(document.f_configuracion_inicial.db_type.value == 'POSTGRESQL')
         {
            document.f_configuracion_inicial.db_port.value = '5432';
         }
         else
            document.f_configuracion_inicial.db_port.value = '3306';
      }
      $(document).ready(function() {
         $("#f_configuracion_inicial").validate({
            rules: {
               db_type: { required: false},
               db_host: { required: true, minlength: 2},
               db_port: { required: true, minlength: 2},
               db_name: { required: true, minlength: 2},
               db_user: { required: true, minlength: 2},
               db_pass: { required: false},
               num_nf0: { required: false},
               num_nf1: { required: false},
               num_nf2: { required: false},
               num_nf3: { required: false},
               cache_host: { required: true, minlength: 2},
               cache_port: { required: true, minlength: 2},
               cache_prefix: { required: false, minlength: 2}
            },
            messages: {
               db_host: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_port: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_name: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               db_user: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               cache_host: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
               cache_port: {
                           required: "El campo es obligatorio.",
                           minlength: jQuery.format("Requiere mínimo {0} carácteres!")
                        },
            }
         });
      });
   </script>
   
   <div class="container">
      <div class="row">
         <div class="col-lg-12 text-center" style="margin-top: 20px; margin-bottom: 20px;">
            <h1>Bienvenido al instalador de FacturaScripts</h1>
         </div>
      </div>
      
      <div class="row">
         <div class="col-lg-12">
            <?php
            foreach($errors as $err)
            {
               if($err == 'permisos')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Permisos de escritura:
               </div>
               <div class="panel-body">
                  <p>
                     La carpeta de FacturaScripts no tiene permisos de escritura. Sin esos
                     permisos, no funcionará FacturaScripts.
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (si usas Linux):</h4>
                  <pre>sudo chmod -R o+w <?php echo dirname(__FILE__); ?></pre>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (instalación en hosting):</h4>
                  <p>Intenta dar permisos de escritura desde el cliente FTP o desde el cPanel.</p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'php')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Versión de PHP obsoleta:
               </div>
               <div class="panel-body">
                  <p>
                     FacturaScripts necesita PHP 5.3 o superior, y tú estás usando <?php echo phpversion() ?>.
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución:</h4>
                  <p>
                     Muchos hostings ofrecen PHP 5.1, 5.2 y 5.3. Pero hay que seleccionar PHP 5.3
                     desde el panel de control.
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'mb_substr')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  No se encuentra la función mb_substr():
               </div>
               <div class="panel-body">
                  <p>
                     FacturaScripts necesita la extensión mbstring para poder trabajar con caracteres
                     no europeos (chinos, coreanos, japonenes y rusos).
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (en Linux):</h4>
                  <p>Instala el paquete php-mbstring.</p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Hosting:</h4>
                  <p>
                     Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                     Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                     Si lo deseas, <a href="//www.facturascripts.com/community/premium.php#hosting" target="_blank">nosotros
                     te podemos ofrecer una versión de FacturaScripts ya instalada y funcionando</a>.
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'bccomp')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  No se encuentra la función bccomp():
               </div>
               <div class="panel-body">
                  <p>
                     FacturaScripts necesita la extensión BC Math para poder comparar grandes números decimales.
                     Aunque es posible que pronto se sustituya por una función más compatible.
                  </p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Solución (en Linux):</h4>
                  <p>Instala el paquete php-bcmath.</p>
                  <h4 style="margin-top: 20px; margin-bottom: 5px;">Hosting:</h4>
                  <p>
                     Algunos proveedores de hosting ofrecen versiones de PHP demasiado recortadas.
                     Es mejor que busques un proveedor de hosting más completo, que son la mayoría.
                     Si lo deseas, <a href="//www.facturascripts.com/community/premium.php#hosting" target="_blank">nosotros
                     te podemos ofrecer una versión de FacturaScripts ya instalada y funcionando</a>.
                  </p>
               </div>
            </div>
                  <?php
               }
               else if($err == 'db_mysql')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Acceso a base de datos MySQL:
               </div>
               <div class="panel-body">
                  <ul>
                   <?php
                   foreach($errors2 as $err2)
                      echo "<li>".$err2."</li>";
                   ?>
                  </ul>
               </div>
            </div>
                  <?php
               }
               else if($err == 'db_postgresql')
               {
                  ?>
            <div class="panel panel-danger">
               <div class="panel-heading">
                  Acceso a base de datos PostgreSQL:
               </div>
               <div class="panel-body">
                  <ul>
                   <?php
                   foreach($errors2 as $err2)
                      echo "<li>".$err2."</li>";
                   ?>
                  </ul>
               </div>
            </div>
                  <?php
               }
            }
            ?>
         </div>
      </div>
      
      <div class="row">
         <div class="col-lg-10">
            <h3>Antes de empezar...</h3>
            <p>
               Recuerda que tienes el menú de ayuda arriba a la derecha. Si encuentras cualquier problema,
               haz clic en <b>informar...</b> y describe tu duda, sugerencia o el error que has encontrado.
            </p>
            <p>
               No sabemos hacer software perfecto, pero con tu ayuda nos podemos acercar cada vez más ;-)
            </p>
         </div>
         <div class="col-lg-2">
            <div class="thumbnail">
               <img src="view/img/help-menu.png" alt="ayuda"/>
            </div>
         </div>
      </div>
      
      <div class="row">
         <div class="col-lg-12">
            <form name="f_configuracion_inicial" id="f_configuracion_inicial" action="install.php" class="form" role="form" method="post">
               <div class="panel panel-primary">
                  <div class="panel-heading">
                     <h3 class="panel-title">
                        <span class="badge">1</span> Configuración de la base de datos
                     </h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Tipo de servidor SQL:
                        <select name="db_type" class="form-control" onchange="change_db_type()">
                           <option value="MYSQL" selected="selected">MySQL</option>
                           <option value="POSTGRESQL">PostgreSQL</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Servidor:
                        <input class="form-control" type="text" name="db_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto:
                        <input class="form-control" type="number" name="db_port" value="3306" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Nombre base de datos:
                        <input class="form-control" type="text" name="db_name" value="facturascripts" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Usuario:
                        <input class="form-control" type="text" name="db_user" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Contraseña:
                        <input class="form-control" type="password" name="db_pass" value="" autocomplete="off"/>
                     </div>
                  </div>
               </div>
                  
               <div class="panel panel-primary" id="panel_configuracion_inicial_num">
                  <div class="panel-heading">
                     <h3 class="panel-title">
                        <span class="badge">2</span> Formato Numérico
                     </h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Decimales:
                        <select name="num_nf0" class="form-control">
                           <option value="0">0</option>
                           <option value="1">1</option>
                           <option value="2" selected="selected">2</option>
                           <option value="3">3</option>
                           <option value="4">4</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Separador para los Decimales:
                        <select name="num_nf1" class="form-control">
                           <option value=",">Coma</option>
                           <option value="." selected="selected">Punto</option>
                           <option value=" ">(Espacio en Blanco)</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Separador para los Millares:
                        <select name="num_nf2" class="form-control">
                           <option value=",">Coma</option>
                           <option value=".">Punto</option>
                           <option value="">(Ninguno)</option>
                           <option value=" " selected="selected">(Espacio en Blanco)</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Símbolo Divisa:
                        <select name="num_nf3" class="form-control">
                           <option value="right" selected="selected">A la Derecha del Número</option>
                           <option value="left">A la Izquierda del Número</option>
                        </select>
                     </div>
                  </div>
               </div>
                
               <div class="panel panel-info" id="panel_configuracion_inicial_cache">
                  <div class="panel-heading">
                     <h3 class="panel-title">
                        <span class="badge">3</span> Configuración Memcache (opcional)
                     </h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Servidor:
                        <input class="form-control" type="text" name="cache_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto:
                        <input class="form-control" type="number" name="cache_port" value="11211" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Prefijo:
                        <input class="form-control" type="text" name="cache_prefix" value="<?php echo random_string(8) ?>_" autocomplete="off"/>
                     </div>
                  </div>
               </div>
               
               <div class="text-right">
                  <button id="submit_button" class="btn btn-sm btn-primary" type="submit">
                     <span class="glyphicon glyphicon-floppy-disk"></span>
                     &nbsp; Guardar y empezar
                  </button>
               </div>
            </form>
         </div>
      </div>
      
      <div class="row" style="margin-bottom: 20px;">
         <div class="col-lg-12 col-md-12 col-sm-12 text-center">
            <hr/>
            <small>
               Creado con <a target="_blank" href="//www.facturascripts.com">FacturaScripts</a>
            </small>
         </div>
      </div>
   </div>
</body>
</html>