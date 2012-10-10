/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

function fs_select_folder(folder)
{
   if( folder == '' )
   {
      $('#user_list').hide();
      $('div.pages div').each(function() {
         $(this).hide();
      });
   }
   else if( $('#folder_'+folder).is(":visible") )
   {
      $('#folder_'+folder).hide();
   }
   else
   {
      $('#user_list').hide();
      $('div.pages div').each(function() {
         $(this).hide();
      });
      $('#folder_'+folder).css({
         display: 'inline-block',
         position: 'fixed',
         left: $("#b_folder_"+folder).position().left+5,
         top: $("#b_folder_"+folder).position().top+32
      });
      $('#folder_'+folder).show();
   }
}

function fs_show_popup(id)
{
   $("#shadow").show();
   $("#"+id).css({
      left: ($(window).width() - $("#"+id).outerWidth())/2,
      top: ($(window).height() - $("#"+id).outerHeight())/2
   });
   $("#"+id).show();
}

$(document).ready(function() {
   $("#b_user_list").click(function(event) {
      event.preventDefault();
      if( $('#user_list').is(":visible") )
      {
         $('#user_list').hide();
      }
      else
      {
         $('div.pages div').each(function() {
            $(this).hide();
         });
         $('#user_list').css({
            display: 'inline-block',
            position: 'fixed',
            left: $("#b_user_list").position().left,
            top: $("#b_user_list").position().top+32
         });
         $('#user_list').show();
      }
   });
   $("#header_logo, #header_buttons, #header_search, div.main_div, div.footer").click(function() {
      fs_select_folder('');
   });
   $("#shadow").click(function() {
      $('div.popup').each(function() {
         $(this).hide();
      });
      $("#shadow").hide();
   });
});