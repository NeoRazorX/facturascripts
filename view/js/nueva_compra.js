/*
 * This file is part of FacturaSctipts
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

var numlineas = 0;
var fs_nf0 = 2;
var all_impuestos = [];
var nueva_compra_url = '';
var codproveedor = '';
var fs_community_url = '';

function recalcular()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_iva = 0;
   var neto = 0;
   var total_iva = 0;
   
   for(var i=0; i<numlineas; i++)
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
}

function ajustar_total()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_iva = 0;
   var l_total = 0;
   var l_totiva = 0;
   var neto = 0;
   var total_iva = 0;
   
   for(var i=0; i<numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_iva = parseFloat( $("#iva_"+i).val() );
         l_total = parseFloat( $("#total_"+i).val() );
         if( isNaN(l_total) )
            l_total = 0;
         
         if( l_total <= l_pvp*l_uds )
         {
            l_dto = 100 - 100*l_total/(l_pvp*l_uds);
            if( isNaN(l_dto) )
               l_dto = 0;
         }
         else
         {
            l_dto = 0;
            l_pvp = 100*l_total/(l_uds*(100-l_dto));
            if( isNaN(l_pvp) )
               l_pvp = 0;
         }
         
         l_totiva = l_total*(100+l_iva)/100;
         $("#pvp_"+i).val(l_pvp);
         $("#dto_"+i).val(l_dto);
         $("#totiva_"+i).val(l_totiva);
         
         neto += l_uds*l_pvp*(100-l_dto)/100;
         total_iva += l_uds*l_pvp*(100-l_dto)/100*l_iva/100;
      }
   }
   
   neto = fs_round(neto, fs_nf0);
   total_iva = fs_round(total_iva, fs_nf0);
   $("#aneto").html( show_numero(neto) );
   $("#aiva").html( show_numero(total_iva) );
   $("#atotal").html( show_numero(neto + total_iva) );
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
   
   for(var i=0; i<numlineas; i++)
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
}

function aplicar_dto2(num)
{
   var dto1 = parseFloat( $("#dto_"+num).val() );
   var dto2 = parseFloat( prompt("Introduce el descuento adicional:") );
   $("#dto_"+num).val( 100 - (100 - dto1)*(100-dto2)/100 );
   recalcular();
}

function aux_all_impuestos(numlinea,iva)
{
   var html = "<select id=\"iva_"+numlinea+"\" class=\"form-control\" name=\"iva_"+numlinea+"\" onchange=\"recalcular()\">";
   
   for(var i=0; i<all_impuestos.length; i++)
   {
      if(iva == all_impuestos[i])
      {
         html += "<option value=\""+all_impuestos[i]+"\" selected=\"selected\">"+all_impuestos[i]+" %</option>";
      }
      else
         html += "<option value=\""+all_impuestos[i]+"\">"+all_impuestos[i]+" %</option>";
   }
   
   return html+"</select>";
}

function get_precios(ref)
{
   if( nueva_compra_url != '' )
   {
      $.ajax({
         type: 'POST',
         url: nueva_compra_url,
         dataType: 'html',
         data: "referencia4precios="+ref+"&codproveedor="+codproveedor,
         success: function(datos) {
            $("#nav_articulos").hide();
            $("#search_results").html(datos);
         }
      });
   }
}

function add_articulo(ref,desc,pvp,dto,iva)
{
   $("#lineas_albaran").append("<tr id=\"linea_"+numlineas+"\">\n\
      <td><input type=\"hidden\" name=\"idlinea_"+numlineas+"\" value=\"-1\"/>\n\
         <input type=\"hidden\" name=\"referencia_"+numlineas+"\" value=\""+ref+"\"/>\n\
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
      <td><input class=\"btn btn-sm btn-default\" type=\"button\" value=\"+%\" onclick=\"aplicar_dto2("+numlineas+")\" title=\"aplicar descuento adicional\"/></td>\n\
      <td><input type=\"text\" class=\"form-control text-right\" id=\"total_"+numlineas+"\" name=\"total_"+numlineas+
         "\" onkeyup=\"ajustar_total()\" onclick=\"this.select()\" autocomplete=\"off\"/></td>\n\
      <td>"+aux_all_impuestos(numlineas,iva)+"</td>\n\
      <td><input type=\"text\" class=\"form-control text-right\" id=\"totiva_"+numlineas+"\" name=\"totiva_"+numlineas+
         "\" onkeyup=\"ajustar_totiva()\" onclick=\"this.select()\" autocomplete=\"off\"/></td></tr>");
   numlineas += 1;
   $("#numlineas").val(numlineas);
   recalcular();
   
   $("#nav_articulos").hide();
   $("#search_results").html('');
   $("#kiwimaru_results").html('');
   $("#nuevo_articulo").hide();
   $("#modal_articulos").modal('hide');
   
   $("#pvp_"+(numlineas-1)).focus();
}

function new_articulo()
{
   if( nueva_compra_url != '' )
   {
      $.ajax({
         type: 'POST',
         url: nueva_compra_url+'&new_articulo=TRUE',
         dataType: 'json',
         data: $("form[name=f_nuevo_articulo]").serialize(),
         success: function(datos) {
            document.f_buscar_articulos.query.value = document.f_nuevo_articulo.referencia.value;
            $("#nav_articulos li").each(function() {
               $(this).removeClass("active");
            });
            $("#li_mis_articulos").addClass('active');
            $("#search_results").html('');
            $("#search_results").show('');
            $("#kiwimaru_results").hide();
            $("#nuevo_articulo").hide();
            
            add_articulo(datos[0].referencia, datos[0].descripcion, datos[0].pvp, 0, datos[0].iva);
         }
      });
   }
}

function buscar_articulos()
{
   if(document.f_buscar_articulos.query.value == '')
   {
         $("#nav_articulos").hide();
         $("#search_results").html('');
         $("#kiwimaru_results").html('');
         $("#nuevo_articulo").hide();
   }
   else
   {
      $("#nav_articulos").show();
      
      if( nueva_compra_url != '' )
      {
         $.getJSON(nueva_compra_url, $("form[name=f_buscar_articulos]").serialize(), function(json) {
            var items = [];
            $.each(json, function(key, val) {
               var tr_aux = '<tr>';
               if(val.bloqueado)
               {
                  tr_aux = "<tr class=\"bg-danger\">"
               }
               else if(val.stockfis < val.stockmin)
               {
                  tr_aux = "<tr class=\"bg-warning\">";
               }
               else if(val.stockfis > val.stockmax)
               {
                  tr_aux = "<tr class=\"bg-info\">";
               }
               items.push(tr_aux+"<td><a href=\"#\" onclick=\"get_precios('"+val.referencia+"')\" title=\"más detalles\"><span class=\"glyphicon glyphicon-eye-open\"></span></a>\n\
                  &nbsp; <a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.pvp+"','0','"+val.iva+"')\">"+val.referencia+'</a> '+val.descripcion+"</td>\n\
                  <td class=\"text-right\"><a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.costemedio+"','0','"+val.iva+"')\">"+show_precio(val.costemedio)+"</a></td>\n\
                  <td class=\"text-right\"><a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.pvp+"','0','"+val.iva+"')\">"+show_precio(val.pvp)+"</a></td>\n\
                  <td class=\"text-right\">"+val.stockfis+"</td></tr>");
            });
            
            if( items.length == 0 )
            {
               items.push("<tr><td colspan=\"4\" class=\"bg-warning\">Sin resultados. Usa la pestaña <b>Nuevo</b> para crear uno.</td></tr>");
            }
            
            $("#search_results").html("<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>\n\
               <th class=\"text-left\">Referencia + descripción</th><th class=\"text-right\">Coste</th><th class=\"text-right\">PVP</th>\n\
               <th class=\"text-right\">Stock</th></tr></thead>"+items.join('')+"</table></div>");
            
            if( json.length == 0 )
            {
               document.f_nuevo_articulo.referencia.value = document.f_buscar_articulos.query.value;
            }
         });
      }
      
      if( fs_community_url != '' )
      {
         $.getJSON(fs_community_url+'/kiwimaru.php', $("form[name=f_buscar_articulos]").serialize(), function(json) {
            var items = [];
            $.each(json, function(key, val) {
               items.push( "<tr><td>"+val.sector+" / <a href=\""+val.link+"\" target=\"_blank\">"+val.tienda+"</a> / "+val.familia+"</td>\n\
                  <td><a href=\"#\" onclick=\"kiwi_import('"+val.referencia+"','"+val.descripcion+"')\">"+val.referencia+'</a> '+val.descripcion+"</td>\n\
                  <td class=\"text-right\"><span title=\"última comprobación "+val.fcomprobado+"\">"+show_numero(val.precio)+" €</span></td></tr>" );
            });
            
            if( items.length == 0 )
            {
               items.push("<tr><td colspan=\"3\" class=\"bg-warning\">Sin resultados.</td></tr>");
            }
            
            $("#kiwimaru_results").html("<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>\n\
               <th class=\"text-left\">Sector / Tienda / Familia</th><th class=\"text-left\">Referencia + descripción</th>\n\
               <th class=\"text-right\">PVP+IVA</th></tr></thead>"+items.join('')+"</table></div>");
         });
      }
   }
}

function kiwi_import(ref, desc)
{
   $("#nav_articulos li").each(function() {
      $(this).removeClass("active");
   });
   $("#li_nuevo_articulo").addClass('active');
   $("#search_results").hide();
   $("#kiwimaru_results").hide();
   $("#nuevo_articulo").show();
   document.f_nuevo_articulo.referencia.value = ref;
   document.f_nuevo_articulo.descripcion.value = desc;
   document.f_nuevo_articulo.referencia.select();
}

var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

$(document).ready(function() {
   
   $("#i_new_line").click(function() {
      $("#i_new_line").val("");
      document.f_buscar_articulos.query.value = "";
      $("#nav_articulos li").each(function() {
         $(this).removeClass("active");
      });
      $("#li_mis_articulos").addClass('active');
      $("#nav_articulos").hide();
      $("#search_results").html('');
      $("#search_results").show('');
      $("#kiwimaru_results").html('');
      $("#kiwimaru_results").hide();
      $("#nuevo_articulo").hide();
      $("#modal_articulos").modal('show');
      document.f_buscar_articulos.query.focus();
   });
   
   $("#i_new_line").keyup(function() {
      document.f_buscar_articulos.query.value = $("#i_new_line").val();
      buscar_articulos();
   });
   
   $("#f_buscar_articulos").keyup(function() {
      delay(function() {
         buscar_articulos();
      }, 200);
   });
   
   $("#f_buscar_articulos").submit(function(event) {
      event.preventDefault();
      buscar_articulos();
   });
   
   $("#b_lineas").click(function(event) {
      event.preventDefault();
      $("#li_opciones").removeClass('active');
      $("#li_lineas").addClass('active');
      $("#div_opciones").hide();
      $("#div_lineas").show();
   });
   
   $("#b_opciones").click(function(event) {
      event.preventDefault();
      $("#li_lineas").removeClass('active');
      $("#li_opciones").addClass('active');
      $("#div_lineas").hide();
      $("#div_opciones").show();
   });
   
   $("#b_mis_articulos").click(function(event) {
      event.preventDefault();
      $("#nav_articulos li").each(function() {
         $(this).removeClass("active");
      });
      $("#li_mis_articulos").addClass('active');
      $("#kiwimaru_results").hide();
      $("#nuevo_articulo").hide();
      $("#search_results").show();
      document.f_buscar_articulos.query.focus();
   });
   
   $("#b_kiwimaru").click(function(event) {
      event.preventDefault();
      $("#nav_articulos li").each(function() {
         $(this).removeClass("active");
      });
      $("#li_kiwimaru").addClass('active');
      $("#nuevo_articulo").hide();
      $("#search_results").hide();
      $("#kiwimaru_results").show();
      document.f_buscar_articulos.query.focus();
   });
   
   $("#b_nuevo_articulo").click(function(event) {
      event.preventDefault();
      $("#nav_articulos li").each(function() {
         $(this).removeClass("active");
      });
      $("#li_nuevo_articulo").addClass('active');
      $("#search_results").hide();
      $("#kiwimaru_results").hide();
      $("#nuevo_articulo").show();
      document.f_nuevo_articulo.referencia.select();
   });
});