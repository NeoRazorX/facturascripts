<?php

$nombre_archivo = "config.php";
error_reporting(E_ALL);
$errors = array();

function random_string($length = 10)
{
   return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

if( file_exists('config.php') )
{
   header('Location: index.php');
}
else if( floatval( substr(phpversion(), 0, 3) ) < 5.3 )
{
   $errors[] = 'FacturaScripts necesita PHP 5.3 o superior, y tú tienes PHP '.phpversion().'.';
}
else if( !function_exists('mb_substr') )
{
   $errors[] = "No se encuentra la función mb_substr(). Instala el paquete php-mbstring.";
}
else if( !function_exists('bccomp') )
{
   $errors[] = "No se encuentra la función bccomp(). Instala el paquete php-bcmath.";
}
else if( !is_writable( getcwd() ) )
{
   $errors[] = "La carpeta no tiene permisos de escritura. ¿<a href='http://www.facturascripts.com' target='_blank'>Necesitas ayuda</a>?";
}
else if ( isset($_REQUEST['db_name']) AND isset($_REQUEST['db_user']) AND isset($_REQUEST['db_pass']) )
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

$system_info = 'facturascripts: '.file_get_contents('VERSION')."\n";
$system_info .= 'os: '.php_uname()."\n";
$system_info .= 'php: '.phpversion()."\n";

if( isset($_SERVER['REQUEST_URI']) )
   $system_info .= 'url: '.$_SERVER['REQUEST_URI']."\n------";

foreach($errors as $e)
   $system_info .= "\n" . $e;

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
                     <li><a href="http://www.facturascripts.com/community/questions.php" target="_blank">Preguntas</a></li>
                     <li><a href="http://www.facturascripts.com/community/errors.php" target="_blank">Errores</a></li>
                     <li><a href="http://www.facturascripts.com/community/ideas.php" target="_blank">Sugerencias</a></li>
                     <li><a href="http://www.facturascripts.com/community/all.php" target="_blank">Todo</a></li>
                     <li class="divider"></li>
                     <li><a href="#" id="b_feedback">Informar...</a></li>
                  </ul>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <form name="f_feedback" action="http://www.facturascripts.com/community/feedback.php" method="post" target="_blank" class="form" role="form">
      <input type="hidden" name="feedback_info" value="<?php echo $system_info; ?>"/>
      <div class="modal" id="modal_feedback">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title">¿Necesitas ayuda?</h4>
               </div>
               <div class="modal-body">
                  <p>
                     La <a href="http://www.facturascripts.com/community" target="_blank">comunidad FacturaScripts</a>
                     está para ayudarte. Escribe tus preguntas o sugerencias y te contestaremos
                     lo antes posible.
                  </p>
                  <div class="form-group">
                     <select class="form-control" id="feedback_type" name="feedback_type">
                        <option value="question">Preguntar</option>
                        <option value="error">Informar de un error</option>
                        <option value="idea">Aportar una idea</option>
                     </select>
                  </div>
                  <div class="form-group">
                     <label for="feedback_textarea">Observaciones</label>
                     <textarea id="feedback_textarea" class="form-control" name="feedback_text" rows="6"></textarea>
                  </div>
                  <div class="form-group">
                     <label for="exampleInputEmail1">Tu email</label>
                     <input type="email" class="form-control" id="exampleInputEmail1" name="feedback_email" placeholder="Introduce tu email"/>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="submit" class="btn btn-sm btn-primary">
                     <span class="glyphicon glyphicon-send"></span>
                     &nbsp; Enviar
                  </button>
               </div>
            </div>
         </div>
      </div>
   </form>

   <script type="text/javascript">
      function comprobar_url()
      {
         $("#panel_bienvenido").hide();
         $("#panel_configuracion_inicial_bd").hide();
         $("#panel_configuracion_inicial_num").hide();
         $("#panel_configuracion_inicial_cache").hide();
         $("#warning_config").hide();
         $("#submit_button").hide();
         
         $("#b_bienvenido").removeClass('active');
         $("#b_configuracion_inicial").removeClass('active');

         
         if(window.location.hash.substring(1) == 'bienvenido')
         {
            $("#b_bienvenido").addClass('active');
            $("#panel_bienvenido").show();
         }
         else if(window.location.hash.substring(1) == 'configuracion_inicial')
         {
            $("#b_configuracion_inicial").addClass('active');
            $("#panel_configuracion_inicial_bd").show();
            $("#panel_configuracion_inicial_num").show();            
            $("#panel_configuracion_inicial_cache").show();
            $("#warning_config").show();
            $("#submit_button").show();
            document.f_configuracion_inicial.db_type.focus();
         }
         else
         {
            $("#b_bienvenido").addClass('active');
            $("#panel_bienvenido").show();
         }
      }
      
      $(document).ready(function() {
         comprobar_url();
         window.onpopstate = function(){ 
            comprobar_url();
         }
         $("#f_configuracion_inicial").validate({
            rules: {
               db_type: { required: false},
               db_host: { required: true, minlength: 2},
               db_port: { required: true, minlength: 2},
               db_name: { required: true, minlength: 2},
               db_user: { required: true, minlength: 2},
               db_pass: { required: true, minlength: 2},
               num_nf0: { required: false},
               num_nf1: { required: false},
               num_nf2: { required: false},
               num_nf3: { required: false},
               cache_host: { required: true, minlength: 2},
               cache_port: { required: true, minlength: 2},
               cache_prefix: { required: false, minlength: 2}
            },
            messages: {
               db_host: "El campo es obligatorio.",
               db_port: "El campo es obligatorio.",
               db_name: "El campo es obligatorio.",
               db_user: "El campo es obligatorio.",
               db_pass: "El campo es obligatorio.",
               cache_host: "El campo es obligatorio.",
               cache_port: "El campo es obligatorio.",
            }
         });
      });
   </script>
   
   <div class="container-fluid">
      &nbsp;
   </div>
   
   <div class="container-fluid">
      <div class="row">
         <div class="col-lg-2 col-md-2 col-sm-2">
            <div class="list-group">
               <a id="b_bienvenido" href="#bienvenido" class="list-group-item active">
                  <span class="glyphicon glyphicon-inbox"></span>
                  &nbsp; Bienvenido
               </a>
               <a id="b_configuracion_inicial" href="#configuracion_inicial" class="list-group-item">
                  <span class="glyphicon glyphicon-inbox"></span>
                  &nbsp; Configuración inicial
               </a>
            </div>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <div class="panel panel-primary" id="panel_bienvenido">
               <div class="panel-heading">
                  <h3 class="panel-title">Bienvenido a FacturaScripts</h3>
               </div>
               <div class="panel-body">
                  <div class="form-group col-lg-12 col-md-12 col-sm-12">
                     Bienvenido al asistente de configuración inicial de FacturaScripts!
                     <br /><br />
                     Este pequeño asistente te va a guiar en la configuración básica que debes completar para empezar a utilizar FacturaScripts.
                     
                  </div>
               </div>
               <div class="panel-footer text-right">
                  <a class="btn btn-sm btn-primary" href="#configuracion_inicial" title="Empezar" value="Empezar">
                     Empezar &nbsp;
                     <span class="glyphicon glyphicon-arrow-right"></span>
                  </a>
               </div>
            </div>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_configuracion_inicial" id="f_configuracion_inicial" action="install.php#configuracion_inicial" class="form" role="form" method="post">
               <div id="warning_config">
                  <?php
                  foreach($errors as $err)
                  {
                     echo "<div class='alert alert-danger'>".$err."</div>";
                  }
                  ?>
               </div>
               
               <div class="panel panel-primary" id="panel_configuracion_inicial_bd">
                  <div class="panel-heading">
                     <h3 class="panel-title">Configuración base de datos</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Tipo de servidor SQL:
                        <select name="db_type" class="form-control">
                           <option value="MYSQL" selected="selected">MySQL</option>
                           <option value="POSTGRESQL">PostgreSQL</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Equipo servidor SQL:
                        <input class="form-control" type="text" name="db_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto servidor SQL:
                        <input class="form-control" type="number" name="db_port" value="3306" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Nombre base de datos:
                        <input class="form-control" type="text" name="db_name" value="facturascripts" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Usuario base de datos:
                        <input class="form-control" type="text" name="db_user" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Contraseña base de datos:
                        <input class="form-control" type="password" name="db_pass" value="" autocomplete="off"/>
                     </div>
                  </div>
               </div>

               <div class="panel panel-primary" id="panel_configuracion_inicial_num">
                  <div class="panel-heading">
                     <h3 class="panel-title">Formato Numérico</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-3 col-md-3 col-sm-3">
                        Cantidad de Decimales:
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
                        Colocación Símbolo Divisa:
                        <select name="num_nf3" class="form-control">
                           <option value="right" selected="selected">A la Derecha del Número</option>
                           <option value="left">A la Izquierda del Número</option>
                        </select>
                     </div>
                  </div>
               </div>
                
               <div class="panel panel-info" id="panel_configuracion_inicial_cache">
                  <div class="panel-heading">
                     <h3 class="panel-title">Configuración Memcache (opcional)</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Servidor Memcache:
                        <input class="form-control" type="text" name="cache_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Puerto Memcache:
                        <input class="form-control" type="text" name="cache_port" value="11211" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-4 col-md-4 col-sm-4">
                        Prefijo Memcache:
                        <input class="form-control" type="text" name="cache_prefix" value="" autocomplete="off"/>
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
      
      <div class="row">
         <div class="col-lg-12 col-md-12 col-sm-12 text-center">
            <hr/>
            <small>
               Creado con <a target="_blank" href="http://www.facturascripts.com">FacturaScripts</a>
            </small>
         </div>
      </div>
   </div>
</body>
</html>