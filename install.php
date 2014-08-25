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
   <script type="text/javascript">
   </script>
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
                     <li><a href="{#FS_COMMUNITY_URL#}/questions.php" target="_blank">Preguntas</a></li>
                     <li><a href="{#FS_COMMUNITY_URL#}/errors.php" target="_blank">Errores</a></li>
                     <li><a href="{#FS_COMMUNITY_URL#}/ideas.php" target="_blank">Sugerencias</a></li>
                     <li><a href="{#FS_COMMUNITY_URL#}/all.php" target="_blank">Todo</a></li>
                     <li class="divider"></li>
                     <li><a href="#" id="b_feedback">Escribir...</a></li>
                  </ul>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <form name="f_feedback" action="{#FS_COMMUNITY_URL#}/feedback.php" method="post" target="_blank" class="form" role="form">
      <input type="hidden" name="feedback_info" value="{$fsc->system_info()}"/>
      <div class="modal" id="modal_feedback">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title">¿Necesitas ayuda?</h4>
               </div>
               <div class="modal-body">
                  <p>
                     La <a href="{#FS_COMMUNITY_URL#}" target="_blank">comunidad FacturaScripts</a>
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
                     <textarea id="feedback_textarea" class="form-control" name="feedback_text" rows="6">{if condition="$fsc->empresa"}email_firma}{/if}</textarea>
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

   <div style="display: none;">
      <iframe src="{#FS_COMMUNITY_URL#}/stats.php?add=TRUE&version={$fsc->version()}&corp={function="urlencode($fsc->empresa->nombre)"}" height="0"></iframe>
   </div>

   <script type="text/javascript">
      function comprobar_url()
      {
         $("#panel_bienvenido").hide();
         $("#panel_configuracion_inicial_bd").hide();
         $("#panel_configuracion_inicial_cache").hide();
         $("#panel_activar_paginas").hide();
         $("#panel_generales").hide();
         $("#panel_ejercicio").hide();
         $("#panel_nuevo_cliente").hide();
         $("#panel_nuevo_proveedor").hide();
         $("#b_bienvenido").removeClass('active');
         $("#b_configuracion_inicial").removeClass('active');
         $("#b_activar_paginas").removeClass('active');
         $("#b_generales").removeClass('active');
         $("#b_ejercicio").removeClass('active');
         $("#b_nuevo_cliente").removeClass('active');
         $("#b_nuevo_proveedor").removeClass('active');
         
         if(window.location.hash.substring(1) == 'bienvenido')
         {
            $("#b_bienvenido").addClass('active');
            $("#panel_bienvenido").show();
         }
         else if(window.location.hash.substring(1) == 'configuracion_inicial')
         {
            $("#b_configuracion_inicial").addClass('active');
            $("#panel_configuracion_inicial_bd").show();
            $("#panel_configuracion_inicial_cache").show();
         }
         else if(window.location.hash.substring(1) == 'activar_paginas')
         {
            $("#b_activar_paginas").addClass('active');
            $("#panel_activar_paginas").show();
         }
         else if(window.location.hash.substring(1) == 'generales')
         {
            $("#b_generales").addClass('active');
            $("#panel_generales").show();
         }
         else if(window.location.hash.substring(1) == 'ejercicio')
         {
            $("#b_ejercicio").addClass('active');
            $("#panel_ejercicio").show();
         }
         else if(window.location.hash.substring(1) == 'nuevo_cliente')
         {
            $("#b_nuevo_cliente").addClass('active');
            $("#panel_nuevo_cliente").show();
         }
         else if(window.location.hash.substring(1) == 'nuevo_proveedor')
         {
            $("#b_nuevo_proveedor").addClass('active');
            $("#panel_nuevo_proveedor").show();
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
               <a id="b_activar_paginas" href="#activar_paginas" class="list-group-item">
                  <span class="glyphicon glyphicon-inbox"></span>
                  &nbsp; Activación de páginas
               </a>
               <a id="b_generales" href="#generales" class="list-group-item">
                  <span class="glyphicon glyphicon-dashboard"></span>
                  &nbsp; Datos generales de la empresa
               </a>
               <a id="b_ejercicio" href="#ejercicio" class="list-group-item">
                  <span class="glyphicon glyphicon-euro"></span>
                  &nbsp; Ejercicio
               </a>
               <a id="b_nuevo_cliente" href="#nuevo_cliente" class="list-group-item">
                  <span class="glyphicon glyphicon-print"></span>
                  &nbsp; Nuevo cliente
               </a>
               <a id="b_nuevo_proveedor" href="#nuevo_proveedor" class="list-group-item">
                  <span class="glyphicon glyphicon-print"></span>
                  &nbsp; Nuevo proveedor
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
            <form name="f_configuracion_inicial" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_configuracion_inicial_bd">
                  <div class="panel-heading">
                     <h3 class="panel-title">Configuración base de datos</h3>
                  </div>
                  <div class="panel-body">
                        <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Tipo de servidor SQL:
                        <select name="db_type" class="form-control">
                           <option value="MYSQL" selected="selected">MySQL</option>
                           <option value="POSTGRESQL">PostgreSQL</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Equipo servidor SQL:
                        <input class="form-control" type="text" name="db_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Puerto servidor SQL:
                        <input class="form-control" type="text" name="db_port" value="3306" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Nombre base de datos:
                        <input class="form-control" type="text" name="db_name" value="facturascripts" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Usuario base de datos:
                        <input class="form-control" type="text" name="db_user" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Contraseña base de datos:
                        <input class="form-control" type="text" name="db_pass" value="" autocomplete="off"/>
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#activar_paginas" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
               
               <div class="panel panel-primary" id="panel_configuracion_inicial_cache">
                  <div class="panel-heading">
                     <h3 class="panel-title">Configuración Memcache</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Servidor Memcache:
                        <input class="form-control" type="text" name="cache_host" value="localhost" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Puerto Memcache:
                        <input class="form-control" type="text" name="cache_port" value="11211" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Prefijo Memcache:
                        <input class="form-control" type="text" name="cache_prefix" value="" autocomplete="off"/>
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#activar_paginas" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_activar_paginas" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_activar_paginas">
                  <div class="panel-heading">
                     <h3 class="panel-title">Activar páginas</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-12 col-md-12 col-sm-12">
                        include de admin_pages
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#generales" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_generales" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_generales">
                  <div class="panel-heading">
                     <h3 class="panel-title">Datos generales</h3>
                  </div>
                  <div class="panel-body">
                        <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Nombre:
                        <input class="form-control" type="text" name="nombre" value="" autocomplete="off" autofocus />
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        CIF/NIF:
                        <input class="form-control" type="text" name="cifnif" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Administrador:
                        <input class="form-control" type="text" name="administrador" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        País:
                        <select name="codpais" class="form-control">
                           <option value="España" selected="selected">España</option>
                        </select>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Provincia:
                        <input class="form-control" type="text" name="provincia" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Ciudad:
                        <input class="form-control" type="text" name="ciudad" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Dirección:
                        <input class="form-control" type="text" name="direccion" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Código Postal:
                        <input class="form-control" type="text" name="codpostal" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Teléfono:
                        <input class="form-control" type="text" name="telefono" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Fax:
                        <input class="form-control" type="text" name="fax" value="" autocomplete="off"/>
                     </div>
                     <div class="form-group col-lg-6 col-md-6 col-sm-6">
                        Web:
                        <input class="form-control" type="text" name="web" value="" autocomplete="off"/>
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#ejercicio" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_ejercicio" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_ejercicio">
                  <div class="panel-heading">
                     <h3 class="panel-title">Ejercicio</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-12 col-md-12 col-sm-12">
                        include de contabilidad_ejercicios
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#nuevo_cliente" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_nuevo_cliente" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_nuevo_cliente">
                  <div class="panel-heading">
                     <h3 class="panel-title">Nuevo cliente</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-12 col-md-12 col-sm-12">
                        include de ventas_clientes#nuevo
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="#nuevo_proveedor" title="Guardar y empezar" value="Guardar y empezar">
                        Guardar y empezar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
         
         <div class="col-lg-10 col-md-10 col-sm-10">
            <form name="f_nuevo_proveedor" action="" method="post" class="form" role="form">
               <div class="panel panel-primary" id="panel_nuevo_proveedor">
                  <div class="panel-heading">
                     <h3 class="panel-title">Nuevo proveedor</h3>
                  </div>
                  <div class="panel-body">
                     <div class="form-group col-lg-12 col-md-12 col-sm-12">
                        include de compras_proveedores#nuevo
                     </div>
                  </div>
                  <div class="panel-footer text-right">
                     <a class="btn btn-sm btn-primary" href="index.php" title="Guardar y continuar" value="Guardar y continuar">
                        Guardar y continuar &nbsp;
                        <span class="glyphicon glyphicon-arrow-right"></span>
                     </a>
                  </div>
               </div>
            </form>
         </div>
   
   <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 text-center">
         <small>
            Creado con <a target="_blank" href="http://www.facturascripts.com">FacturaScripts</a>
         </small>
      </div>
   </div>

</body>
</html>
