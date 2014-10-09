<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<div class="panel panel-danger" style="margin: 5px;">
   <div class="panel-heading">
      <h3 class="panel-title">Página no encontrada</h3>
   </div>
   <div class="panel-body">
      <div class="container-fluid">
         <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-5">
               <ul>
                  <li>No se encuentra el controlador de esta página.</li>
                  <li>Consulta con el administrador.</li>
                  <li>
                     Si crees que es un error de FacturaScripts, no dudes en notificármelo.
                     <b>Haz clic en el botón de ayuda</b>
                     <span class="glyphicon glyphicon-question-sign"></span>
                     y <b>notifícame el error</b>.
                  </li>
                  <li>
                     <b>Si eres programador</b> y estas intentando hacer un plugin, entonces te comento que
                     lo que ha pasado es que no encuentro el archivo <b><?php echo $_GET['page'];?>.php</b>,
                     que debería estar en el directorio controller de tu plugin.
                  </li>
               </ul>
            </div>
            <div class="col-lg-7 col-md-7 col-sm-7" style="text-align: right;">
               <img src="view/img/fuuu_face.png" alt="fuuuuu"/>
            </div>
         </div>
      </div>
   </div>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>