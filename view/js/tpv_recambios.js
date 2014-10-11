var fs_nf0 = 2;
var numlineas = 0;
var lcd_txt = '-1';
var tpv_url = '';

function update_lcd(saldo)
{
   if(saldo != lcd_txt)
   {
      $.ajax({
         type: 'POST',
         url: tpv_url,
         dataType: 'html',
         data: 'saldo='+saldo
      });
      
      lcd_txt = saldo;
   }
}

function recalcular()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_iva = 0;
   var neto = 0;
   var total_iva = 0;
   
   for(var i=1; i<=numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_iva = parseFloat( $("#iva_"+i).val() );
         $("#total_"+i).val( l_uds*l_pvp*(100-l_dto)/100 );
         $("#totiva_"+i).val( fs_round(l_uds*l_pvp*(100-l_dto)/100*(100+l_iva)/100, fs_nf0) );
         
         neto += l_uds*l_pvp*(100-l_dto)/100;
         total_iva += l_uds*l_pvp*(100-l_dto)/100*l_iva/100;
      }
   }
   
   neto = fs_round(neto, fs_nf0);
   total_iva = fs_round(total_iva, fs_nf0);
   $("#aneto").html( show_numero(neto) );
   $("#aiva").html( show_numero(total_iva) );
   $("#atotal").html( show_numero(neto + total_iva) );
   update_lcd(neto + total_iva);
}

function ajustar_totiva()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_iva = 0;
   var l_dto = 0;
   var l_totiva = 0;
   var neto = 0;
   var total_iva = 0;
   
   for(var i=1; i<=numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_iva = parseFloat( $("#iva_"+i).val() );
         l_totiva = parseFloat( $("#totiva_"+i).val() );
         if( isNaN(l_totiva) )
            l_totiva = 0;
         
         if( l_totiva <= l_pvp*l_uds*(100+l_iva)/100 )
         {
            l_dto = 100 - 100*l_totiva/(l_pvp*l_uds*(100+l_iva)/100);
            if( isNaN(l_dto) )
               l_dto = 0;
         }
         else
         {
            l_dto = 0;
            l_pvp = 10000*l_totiva/(l_uds*(100-l_dto)*(100+l_iva));
            if( isNaN(l_pvp) )
               l_pvp = 0;
         }
         
         $("#pvp_"+i).val(l_pvp);
         $("#dto_"+i).val(l_dto);
         $("#total_"+i).val( l_uds*l_pvp*(100-l_dto)/100 );
         
         neto += l_uds*l_pvp*(100-l_dto)/100;
         total_iva += l_uds*l_pvp*(100-l_dto)/100*l_iva/100;
      }
   }
   
   neto = fs_round(neto, fs_nf0);
   total_iva = fs_round(total_iva, fs_nf0);
   $("#aneto").html( show_numero(neto) );
   $("#aiva").html( show_numero(total_iva) );
   $("#atotal").html( show_numero(neto + total_iva) );
   update_lcd(neto + total_iva);
}

function get_precios(ref)
{
   $.ajax({
      type: 'POST',
      url: tpv_url,
      dataType: 'html',
      data: "referencia4precios="+ref+"&codcliente="+document.f_new_albaran.cliente.value,
      success: function(datos) {
         $("#search_results").html(datos);
      }
   });
}

function add_articulo(ref,desc,pvp,dto,iva)
{
   numlineas += 1;
   $("#numlineas").val(numlineas);
   desc = Base64.decode(desc);
   $("#lineas_albaran").prepend("<tr id=\"linea_"+numlineas+"\">\n\
         <td><input type=\"hidden\" name=\"referencia_"+numlineas+"\" value=\""+ref+"\"/>\n\
            <input type=\"hidden\" id=\"iva_"+numlineas+"\" name=\"iva_"+numlineas+"\" value=\""+iva+"\"/>\n\
            <input type=\"hidden\" id=\"recargo_"+numlineas+"\" name=\"recargo_"+numlineas+"\" value=\"0\"/>\n\
            <div class=\"form-control\"><a target=\"_blank\" href=\"index.php?page=ventas_articulo&ref="+ref+"\">"+ref+"</a></div></td>\n\
         <td><input type=\"text\" class=\"form-control\" name=\"desc_"+numlineas+"\" value=\""+desc+"\" onclick=\"this.select()\"/></td>\n\
         <td><input type=\"number\" step=\"any\" id=\"cantidad_"+numlineas+"\" class=\"form-control text-right\" name=\"cantidad_"+numlineas+
            "\" onchange=\"recalcular()\" onkeyup=\"recalcular()\" autocomplete=\"off\" value=\"1\"/></td>\n\
         <td><button class=\"btn btn-sm btn-danger\" type=\"button\" onclick=\"$('#linea_"+numlineas+"').remove();recalcular();\">\n\
            <span class=\"glyphicon glyphicon-trash\"></span></button></td>\n\
         <td><input type=\"text\" class=\"form-control text-right\" id=\"pvp_"+numlineas+"\" name=\"pvp_"+numlineas+"\" value=\""+pvp+
            "\" onkeyup=\"recalcular()\" onclick=\"this.select()\" autocomplete=\"off\"/></td>\n\
         <td><input type=\"text\" id=\"dto_"+numlineas+"\" name=\"dto_"+numlineas+"\" value=\""+dto+
            "\" class=\"form-control text-right\" onkeyup=\"recalcular()\" onclick=\"this.select()\" autocomplete=\"off\"/></td>\n\
         <td><input type=\"text\" class=\"form-control text-right\" id=\"total_"+numlineas+"\" name=\"total_"+numlineas+
            "\" readonly/></td>\n\
         <td class=\"text-right\"><div class=\"form-control\">"+iva+"</div></td>\n\
         <td><input type=\"text\" class=\"form-control text-right\" id=\"totiva_"+numlineas+"\" name=\"totiva_"+numlineas+
            "\" onkeyup=\"ajustar_totiva()\" onclick=\"this.select()\" autocomplete=\"off\"/></td></tr>");
   recalcular();
   $("#modal_articulos").modal('hide');
   
   $("#pvp_"+(numlineas)).focus();
}

function buscar_articulos()
{
   if(document.f_buscar_articulos.query.value == '')
   {
      $("#search_results").html('');
   }
   else
   {
      document.f_buscar_articulos.codcliente.value = document.f_new_albaran.cliente.value;
      
      $.ajax({
         type: 'POST',
         url: tpv_url,
         dataType: 'html',
         data: $("form[name=f_buscar_articulos]").serialize(),
         success: function(datos) {
            var re = /<!--(.*?)-->/g;
            var m = re.exec( datos );
            if( m[1] == document.f_buscar_articulos.query.value )
            {
               $("#search_results").html(datos);
            }
         }
      });
   }
}

$(document).ready(function() {
   $("#b_reticket").click(function() {
      window.location.href = tpv_url+"&reticket="+prompt('Introduce el'+' código del ticket (o déjalo en blanco para re-imprimir el último):');
   });
   
   $("#b_borrar_ticket").click(function() {
      window.location.href = tpv_url+"&delete="+prompt('Introduce el código del ticket:');
   });
   
   $("#b_cerrar_caja").click(function() {
      if( confirm("¿Realmente deseas cerrar la caja?") )
         window.location.href = tpv_url+"&cerrar_caja=TRUE";
   });
   
   $("#i_new_line").click(function() {
      $("#i_new_line").val("");
      document.f_buscar_articulos.query.value = "";
      $("#search_results").html("");
      $("#modal_articulos").modal('show');
      document.f_buscar_articulos.query.focus();
   });
   
   $("#i_new_line").keyup(function() {
      document.f_buscar_articulos.query.value = $("#i_new_line").val();
      buscar_articulos();
   });
   
   $("#f_buscar_articulos").keyup(function() {
      buscar_articulos();
   });
   
   $("#f_buscar_articulos").submit(function(event) {
      event.preventDefault();
      buscar_articulos();
   });
   
   update_lcd(0);
});