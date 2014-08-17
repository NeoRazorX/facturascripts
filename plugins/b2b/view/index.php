<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es" >
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <title>FacturaScripts B2B</title>
   <meta name="description" content="FacturaScripts es un software de facturación y contabilidad para pymes. Es software libre bajo licencia GNU/AGPL." />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="shortcut icon" href="http://localhost/shawe/facturascripts/view/img/favicon.ico" />
   <link rel="stylesheet" href="http://localhost/shawe/facturascripts/view/css/bootstrap-yeti.min.css" />
   <script type="text/javascript" src="http://localhost/shawe/facturascripts/view/js/jquery-2.1.1.min.js"></script>
   <script type="text/javascript" src="http://localhost/shawe/facturascripts/view/js/bootstrap.min.js"></script>
</head>
<body style="background-color: #E9EAED;">
   <nav class="navbar navbar-inverse" role="navigation" style="margin: 0px;">
      <div class="container-fluid">
         <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">FacturaScripts B2B</a>
         </div>
         
         <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
               <li>
                  <a href="#" title="feedback" data-toggle="modal" data-target="#modal_feedback">
                     <span class="glyphicon glyphicon-question-sign"></span>
                  </a>
               </li>
            </ul>
         </div>
      </div>
   </nav>
   
   <div class="container">
      <div class="row">
         <div class="col-md-6 col-md-offset-3 col-xs-10 col-xs-offset-1">
            <div class="well well-lg" id="box_login">
               <form name="f_login" action="index.php?nlogin=###" method="post" role="form">
                  <div class="form-group">
                     <label>Usuario</label>
                     <input type="text" class="form-control input-xs" name="user" autocomplete="off" placeholder="Usuario" autofocus  />
                  </div>
                  <div class="form-group">
                     <label>Contraseña</label>
                     <input type="password" class="form-control input-xs" name="password" maxlength="20" placeholder="Contraseña"/>
                  </div>
                  <a href="#" data-toggle="modal" data-target="#modal_new_password" class="">¿Has olvidado la contraseña?</a>
                  <input type="submit" class="btn btn-sm btn-primary pull-right" id="login" value="Iniciar sesión"/>
                  <br/><br/>
               </form>
            </div>
         </div>
      </div>
   </div>
   
   <div class="modal fade" id="modal_new_password">
      <div class="modal-dialog">
         <div class="modal-content">
            <form name="f_new_password" action="index.php" method="post" role="form">
               <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title">¿Has olvidado la contraseña?</h4>
               </div>
               <div class="modal-body">
                  <div class="form-group">
                     <label>Usuario</label>
                     <input type="text" class="form-control" name="user" autocomplete="off" placeholder="Usuario" />
                  </div>
                  <div class="form-group">
                     <label>Nueva contraseña</label>
                     <input type="password" class="form-control" name="new_password" maxlength="20" placeholder="Nueva contraseña">
                     <input type="password" class="form-control" name="new_password2" maxlength="20" placeholder="Repite la nueva contraseña">
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="submit" class="btn btn-sm btn-primary">Cambiar</button>
               </div>
            </form>
         </div>
      </div>
   </div>
</body>
</html>
