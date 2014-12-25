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
var all_series = [];
var cliente = false;
var nueva_venta_url = '';
var fs_community_url = '';
var fin_busqueda1 = true;
var fin_busqueda2 = true;
var siniva = false;
var irpf = 0;

function usar_cliente(codcliente)
{
   if(nueva_venta_url !== '')
   {
      $.getJSON(nueva_venta_url, 'datoscliente='+codcliente, function(json) {
         cliente = json;
         document.f_buscar_articulos.codcliente.value = cliente.codcliente;
         if(cliente.regimeniva == 'Exento')
         {
            irpf = 0;
            for(var j=0; j<numlineas; j++)
            {
               if($("#linea_"+j).length > 0)
               {
                  $("#iva_"+j).val(0);
                  $("#recargo_"+j).val(0);
                  $("#irpf_"+j).html( show_numero(irpf) );
               }
            }
         }
         recalcular();
      });
   }
}

function usar_serie()
{
   for(var i=0; i<all_series.length; i++)
   {
      if(all_series[i].codserie == $("#codserie").val())
      {
         siniva = all_series[i].siniva;
         irpf = all_series[i].irpf;
         
         for(var j=0; j<numlineas; j++)
         {
            if($("#linea_"+j).length > 0)
            {
               $("#irpf_"+j).html( show_numero(irpf) );
               
               if(siniva)
               {
                  $("#iva_"+j).val(0);
                  $("#recargo_"+j).val(0);
               }
            }
         }
         
         break;
      }
   }
}

function recalcular()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_neto = 0;
   var l_iva = 0;
   var l_irpf = 0;
   var l_recargo = 0;
   var neto = 0;
   var total_iva = 0;
   var total_irpf = 0;
   var total_recargo = 0;
   
   for(var i=0; i<numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_neto = l_uds*l_pvp*(100-l_dto)/100;
         l_iva = parseFloat( $("#iva_"+i).val() );
         l_irpf = irpf;
         
         if(cliente.recargo)
         {
            l_recargo = parseFloat( $("#recargo_"+i).val() );
         }
         else
         {
            l_recargo = 0;
            $("#recargo_"+i).val(0);
         }
         
         $("#neto_"+i).val( l_neto );
         $("#total_"+i).val( number_format(l_neto + (l_neto*(l_iva-l_irpf+l_recargo)/100), fs_nf0, '.', '') );
         
         neto += l_neto;
         total_iva += l_neto * l_iva/100;
         total_irpf += l_neto * l_irpf/100;
         total_recargo += l_neto * l_recargo/100;
      }
   }
   
   neto = fs_round(neto, fs_nf0);
   total_iva = fs_round(total_iva, fs_nf0);
   total_irpf = fs_round(total_irpf, fs_nf0);
   total_recargo = fs_round(total_recargo, fs_nf0);
   $("#aneto").html( show_numero(neto) );
   $("#aiva").html( show_numero(total_iva) );
   $("#are").html( show_numero(total_recargo) );
   $("#airpf").html( '-'+show_numero(total_irpf) );
   $("#atotal").val( neto + total_iva - total_irpf + total_recargo );
   
   if(total_recargo == 0)
   {
      $(".recargo").hide();
   }
   else
   {
      $(".recargo").show();
   }
   
   if(total_irpf == 0)
   {
      $(".irpf").hide();
   }
   else
   {
      $(".irpf").show();
   }
}

function ajustar_neto()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_neto = 0;
   
   for(var i=0; i<numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_neto = parseFloat( $("#neto_"+i).val() );
         if( isNaN(l_neto) )
            l_neto = 0;
         
         if( l_neto <= l_pvp*l_uds )
         {
            l_dto = 100 - 100*l_neto/(l_pvp*l_uds);
            if( isNaN(l_dto) )
               l_dto = 0;
         }
         else
         {
            l_dto = 0;
            l_pvp = 100*l_neto/(l_uds*(100-l_dto));
            if( isNaN(l_pvp) )
               l_pvp = 0;
         }
         
         $("#pvp_"+i).val(l_pvp);
         $("#dto_"+i).val(l_dto);
      }
   }
   
   recalcular();
}

function ajustar_total()
{
   var l_uds = 0;
   var l_pvp = 0;
   var l_dto = 0;
   var l_iva = 0;
   var l_irpf = 0;
   var l_recargo = 0;
   var l_neto = 0;
   var l_total = 0;
   
   for(var i=0; i<numlineas; i++)
   {
      if($("#linea_"+i).length > 0)
      {
         l_uds = parseFloat( $("#cantidad_"+i).val() );
         l_pvp = parseFloat( $("#pvp_"+i).val() );
         l_dto = parseFloat( $("#dto_"+i).val() );
         l_iva = parseFloat( $("#iva_"+i).val() );
         l_recargo = parseFloat( $("#recargo_"+i).val() );
         
         l_irpf = irpf;
         if(l_iva <= 0)
            l_irpf = 0;
         
         l_total = parseFloat( $("#total_"+i).val() );
         if( isNaN(l_total) )
            l_total = 0;
         
         if( l_total <= l_pvp*l_uds + (l_pvp*l_uds*(l_iva-l_irpf+l_recargo)/100) )
         {
            l_neto = 100*l_total/(100+l_iva-l_irpf+l_recargo);
            l_dto = 100 - 100*l_neto/(l_pvp*l_uds);
            if( isNaN(l_dto) )
               l_dto = 0;
         }
         else
         {
            l_dto = 0;
            l_neto = 100*l_total/(100+l_iva-l_irpf+l_recargo);
            l_pvp = l_neto/l_uds;
         }
         
         $("#pvp_"+i).val(l_pvp);
         $("#dto_"+i).val(l_dto);
      }
   }
   
   recalcular();
}

function ajustar_iva(num)
{
   if($("#linea_"+num).length > 0)
   {
      if(siniva && $("#iva_"+num).val() != 0)
      {
         $("#iva_"+num).val(0);
         $("#recargo_"+num).val(0);
         
         alert('La serie selecciona es sin IVA.');
      }
      else if(cliente.recargo)
      {
         for(var i=0; i<all_impuestos.length; i++)
         {
            if($("#iva_"+num).val() == all_impuestos[i].iva)
            {
               $("#recargo_"+num).val(all_impuestos[i].recargo);
            }
         }
      }
   }
   
   recalcular();
}

function aux_all_impuestos(num,codimpuesto)
{
   var iva = 0;
   var recargo = 0;
   if(cliente.regimeniva != 'Exento' && !siniva)
   {
      for(var i=0; i<all_impuestos.length; i++)
      {
         if(all_impuestos[i].codimpuesto == codimpuesto)
         {
            iva = all_impuestos[i].iva;
            if(cliente.recargo)
            {
              recargo = all_impuestos[i].recargo;
            }
            break;
         }
      }
   }
   
   var html = "<td><select id=\"iva_"+num+"\" class=\"form-control\" name=\"iva_"+num+"\" onchange=\"ajustar_iva('"+num+"')\">";
   for(var i=0; i<all_impuestos.length; i++)
   {
      if(iva == all_impuestos[i].iva)
      {
         html += "<option value=\""+all_impuestos[i].iva+"\" selected=\"selected\">"+all_impuestos[i].descripcion+"</option>";
      }
      else
         html += "<option value=\""+all_impuestos[i].iva+"\">"+all_impuestos[i].descripcion+"</option>";
   }
   html += "</select></td>";
   
   html += "<td class=\"recargo\"><input type=\"text\" class=\"form-control text-right\" id=\"recargo_"+num+"\" name=\"recargo_"+num+
           "\" value=\""+recargo+"\" onclick=\"this.select()\" onkeyup=\"recalcular()\" autocomplete=\"off\"/></td>";
   
   html += "<td class=\"irpf\"><div class=\"form-control text-right\" id=\"irpf_"+num+"\">"+show_numero(irpf)+"</div></td>";
   
   return html;
}

function add_articulo(ref,desc,pvp,dto,codimpuesto)
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
      <td><input type=\"text\" class=\"form-control text-right\" id=\"neto_"+numlineas+"\" name=\"neto_"+numlineas+
         "\" onchange=\"ajustar_neto()\" onclick=\"this.select()\" autocomplete=\"off\"/></td>\n\
      "+aux_all_impuestos(numlineas,codimpuesto)+"\n\
      <td><input type=\"text\" class=\"form-control text-right\" id=\"total_"+numlineas+"\" name=\"total_"+numlineas+
         "\" onchange=\"ajustar_total()\" onclick=\"this.select()\" autocomplete=\"off\"/></td></tr>");
   numlineas += 1;
   $("#numlineas").val(numlineas);
   recalcular();
   
   $("#nav_articulos").hide();
   $("#search_results").html('');
   $("#kiwimaru_results").html('');
   $("#nuevo_articulo").hide();
   $("#modal_articulos").modal('hide');
   
   $("#pvp_"+(numlineas-1)).select();
}

function get_precios(ref)
{
   if(nueva_venta_url !== '')
   {
      $.ajax({
         type: 'POST',
         url: nueva_venta_url,
         dataType: 'html',
         data: "referencia4precios="+ref+"&codcliente="+cliente.codcliente,
         success: function(datos) {
            $("#nav_articulos").hide();
            $("#search_results").html(datos);
         }
      });
   }
}

function new_articulo()
{
   if( nueva_venta_url != '' )
   {
      $.ajax({
         type: 'POST',
         url: nueva_venta_url+'&new_articulo=TRUE',
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
            
            add_articulo(datos[0].referencia, datos[0].descripcion, datos[0].pvp, 0, datos[0].codimpuesto);
         }
      });
   }
}

function buscar_articulos()
{
   if(document.f_buscar_articulos.query.value === '')
   {
      $("#nav_articulos").hide();
      $("#search_results").html('');
      $("#kiwimaru_results").html('');
      $("#nuevo_articulo").hide();
      
      fin_busqueda1 = true;
      fin_busqueda2 = true;
   }
   else
   {
      $("#nav_articulos").show();
      
      if(nueva_venta_url !== '')
      {
         fin_busqueda1 = false;
         $.getJSON(nueva_venta_url, $("form[name=f_buscar_articulos]").serialize(), function(json) {
            var items = [];
            var insertar = false;
            $.each(json, function(key, val) {
               var tr_aux = '<tr>';
               if(val.bloqueado)
               {
                  tr_aux = "<tr class=\"bg-danger\">";
               }
               else if(val.stockfis < val.stockmin)
               {
                  tr_aux = "<tr class=\"bg-warning\">";
               }
               else if(val.stockfis > val.stockmax)
               {
                  tr_aux = "<tr class=\"bg-info\">";
               }
               
               if(val.sevende)
               {
                  items.push(tr_aux+"<td><a href=\"#\" onclick=\"get_precios('"+val.referencia+"')\" title=\"más detalles\"><span class=\"glyphicon glyphicon-eye-open\"></span></a>\n\
                     &nbsp; <a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.pvp+"','"+val.dtopor+"','"+val.codimpuesto+"')\">"+val.referencia+'</a> '+val.descripcion+"</td>\n\
                     <td class=\"text-right\"><a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.pvp+"','"+val.dtopor+"','"+val.codimpuesto+"')\">"+show_precio(val.pvp*(100-val.dtopor)/100)+"</a></td>\n\
                     <td class=\"text-right\"><a href=\"#\" onclick=\"add_articulo('"+val.referencia+"','"+val.descripcion+"','"+val.pvp+"','"+val.dtopor+"','"+val.codimpuesto+"')\">"+show_pvp_iva(val.pvp*(100-val.dtopor)/100,val.codimpuesto)+"</a></td>\n\
                     <td class=\"text-right\">"+val.stockfis+"</td></tr>");
               }
               
               if(val.query == document.f_buscar_articulos.query.value)
               {
                  insertar = true;
                  fin_busqueda1 = true;
               }
            });
            
            if(items.length == 0 && !fin_busqueda1)
            {
               items.push("<tr><td colspan=\"4\" class=\"bg-warning\">Sin resultados. Usa la pestaña <b>Nuevo</b> para crear uno.</td></tr>");
               document.f_nuevo_articulo.referencia.value = document.f_buscar_articulos.query.value;
               insertar = true;
            }
            
            if(insertar)
            {
               $("#search_results").html("<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>\n\
                  <th class=\"text-left\">Referencia + descripción</th><th class=\"text-right\">PVP</th><th class=\"text-right\">PVP+IVA</th>\n\
                  <th class=\"text-right\">Stock</th></tr></thead>"+items.join('')+"</table></div>");
            }
         });
      }
      
      if(fs_community_url !== '')
      {
         fin_busqueda2 = false;
         $.getJSON(fs_community_url+'/kiwimaru.php', $("form[name=f_buscar_articulos]").serialize(), function(json) {
            var items = [];
            var insertar = false;
            $.each(json, function(key, val) {
               items.push( "<tr><td>"+val.sector+" / <a href=\""+val.link+"\" target=\"_blank\">"+val.tienda+"</a> / "+val.familia+"</td>\n\
                  <td><a href=\"#\" onclick=\"kiwi_import('"+val.referencia+"','"+val.descripcion+"')\">"+val.referencia+'</a> '+val.descripcion+"</td>\n\
                  <td class=\"text-right\"><span title=\"última comprobación "+val.fcomprobado+"\">"+show_numero(val.precio)+" €</span></td></tr>" );
               
               if(val.query == document.f_buscar_articulos.query.value)
               {
                  insertar = true;
                  fin_busqueda2 = true;
               }
            });
            
            if(items.length == 0 && !fin_busqueda2)
            {
               items.push("<tr><td colspan=\"3\" class=\"bg-warning\">Sin resultados.</td></tr>");
               insertar = true;
            }
            
            if(insertar)
            {
               $("#kiwimaru_results").html("<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>\n\
                  <th class=\"text-left\">Sector / Tienda / Familia</th><th class=\"text-left\">Referencia + descripción</th>\n\
                  <th class=\"text-right\">PVP+IVA</th></tr></thead>"+items.join('')+"</table></div>");
            }
         });
      }
   }
}

function show_pvp_iva(pvp,codimpuesto)
{
   var iva = 0;
   if(cliente.regimeniva != 'Exento' && !siniva)
   {
      for(var i=0; i<all_impuestos.length; i++)
      {
         if(all_impuestos[i].codimpuesto == codimpuesto)
         {
            iva = all_impuestos[i].iva;
            break;
         }
      }
   }
   
   return show_precio(pvp + pvp*iva/100);
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
      buscar_articulos();
   });
   
   $("#f_buscar_articulos").submit(function(event) {
      event.preventDefault();
      buscar_articulos();
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