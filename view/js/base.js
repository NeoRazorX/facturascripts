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

function number_format(number, decimals, dec_point, thousands_sep)
{
   var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
   var d = dec_point == undefined ? "," : dec_point;
   var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
   var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

var Base64 = {
    // private property
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

}

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
      
      var left2 = $("#b_folder_"+folder).position().left+$("#b_folder_"+folder).outerWidth()/2-($("#folder_"+folder).outerWidth()-5)/2;
      if( left2 > 0 )
      {
         $('#folder_'+folder+'_img').css({
            'padding-left': ( $("#folder_"+folder).outerWidth()/2 ) - 10
         });
         $('#folder_'+folder).css({
            display: 'inline-block',
            position: 'absolute',
            left: left2,
            top: $("#b_folder_"+folder).position().top+25
         });
      }
      else
      {
         $('#folder_'+folder+'_img').css({
            'padding-left': ( $("#b_folder_"+folder).outerWidth()/2 ) - 5
         });
         $('#folder_'+folder).css({
            display: 'inline-block',
            position: 'absolute',
            left: 5,
            top: $("#b_folder_"+folder).position().top+25
         });
      }
   }
}

function fs_show_popup(id, top)
{
   $("#shadow").show();
   if( typeof(top) == 'undefined' )
   {
      $("#"+id).css({
         left: ($(window).width() - $("#"+id).outerWidth())/2,
         top: ($(window).height() - $("#"+id).outerHeight())/2
      });
   }
   else
   {
      $("#"+id).css({
         left: ($(window).width() - $("#"+id).outerWidth())/2,
         top: top
      });
   }
   $("#"+id).show();
}

function fs_hide_popups()
{
   $('div.popup').each(function() {
      $(this).hide();
   });
   $("#shadow").hide();
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
         
         var right2 = $(window).width() - ($("#b_user_list").position().left+$("#b_user_list").outerWidth()/2+($("#user_list").outerWidth()+5)/2);
         if(right2 > 0)
         {
            $('#user_list_img').css({
               'padding-right': ( $("#user_list").outerWidth()/2 ) - 10
            });
            $('#user_list').css({
               display: 'inline-block',
               position: 'absolute',
               'text-align': 'right',
               right: right2,
               top: $("#b_user_list").position().top+25
            });
         }
         else
         {
            $('#user_list_img').css({
               'padding-right': ( $("#b_user_list").outerWidth()/2 ) - 10
            });
            $('#user_list').css({
               display: 'inline-block',
               position: 'absolute',
               'text-align': 'right',
               right: $(window).width() - $("#b_user_list").position().left - $("#b_user_list").outerWidth() - 5,
               top: $("#b_user_list").position().top+25
            });
         }
      }
   });
   $("#header_logo, #header_buttons, #header_search, div.main_div, div.footer").click(function() {
      fs_select_folder('');
   });
   $("#shadow").click(function() {
      fs_hide_popups();
   });
});