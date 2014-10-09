<?php if(!class_exists('raintpl')){exit;}?><form name="f_feedback" action="<?php  echo FS_COMMUNITY_URL;?>/feedback.php" method="post" target="_blank" class="form" role="form">
   <input type="hidden" name="feedback_info" value="<?php echo $fsc->system_info();?>"/>
   <div class="modal" id="modal_feedback">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
               <h4 class="modal-title">¿Necesitas ayuda?</h4>
            </div>
            <div class="modal-body">
               <p>
                  La <a href="<?php  echo FS_COMMUNITY_URL;?>" target="_blank">comunidad FacturaScripts</a>
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
                  <textarea id="feedback_textarea" class="form-control" name="feedback_text" rows="6"><?php if( $fsc->empresa ){ ?><?php echo $fsc->empresa->email_firma;?><?php } ?></textarea>
               </div>
               <div class="form-group">
                  <label for="exampleInputEmail1">Tu email</label>
                  <?php if( $fsc->empresa ){ ?>

                  <input type="email" class="form-control" id="exampleInputEmail1" name="feedback_email" placeholder="Introduce tu email" value="<?php echo $fsc->empresa->email;?>"/>
                  <?php }else{ ?>

                  <input type="email" class="form-control" id="exampleInputEmail1" name="feedback_email" placeholder="Introduce tu email"/>
                  <?php } ?>

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

<?php if( $fsc->empresa AND !FS_DEMO AND mt_rand(0,9)==0 ){ ?>

<div style="display: none;">
   <iframe src="<?php  echo FS_COMMUNITY_URL;?>/stats.php?add=TRUE&version=<?php echo $fsc->version();?>&corp=<?php echo urlencode($fsc->empresa->nombre); ?>" height="0"></iframe>
</div>
<?php } ?>

