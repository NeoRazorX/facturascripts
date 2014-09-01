<?php
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

/**
 * Permite imprimir ticket de forma local o remota.
 */
class fs_printer
{
   private $file;
   private $filename;
   private $print_command;
   
   public function __construct($printer = FS_PRINTER)
   {
      if($printer == 'remote-printer')
      {
         $this->filename = 'tmp/'.FS_TMP_NAME.'remote-printer.txt';
         $this->file = fopen($this->filename, 'a');
         $this->print_command = 'remote-printer';
      }
      else
      {
         $this->filename = 'tmp/'.FS_TMP_NAME.'ticket_'.$this->random_string().'.txt';
         $this->file = fopen($this->filename, 'w');
         
         if($printer == '')
            $this->print_command = ' | lp';
         else if( substr($printer, 0, 5) == '/dev/' )
            $this->print_command = ' > '.$printer;
         else
            $this->print_command = ' | lp -d '.$printer;
      }
   }
   
   public function __destruct()
   {
      if( $this->file )
         fclose($this->file);
      
      if( file_exists($this->filename) AND $this->print_command != 'remote-printer' )
         unlink($this->filename);
   }
   
   public function set_printer($printer = FS_PRINTER)
   {
      if($printer == '')
         $this->print_command = ' | lp';
      else if( substr($printer, 0, 5) == '/dev/' )
         $this->print_command = ' > '.$printer;
      else if($printer == 'remote-printer')
         $this->print_command = 'remote-printer';
      else
         $this->print_command = ' | lp -d '.$printer;
   }
   
   public function add($linea)
   {
      if($this->file)
         fwrite($this->file, $linea);
   }
   
   /// añade la línea de texto en letras grandes
   public function add_big($linea)
   {
      if($this->file)
         fwrite($this->file, chr(27).chr(33).chr(56).$linea.chr(27).chr(33).chr(1));
   }
   
   public function imprimir()
   {
      if($this->print_command != 'remote-printer')
         shell_exec("cat ".$this->filename.$this->print_command);
   }
   
   public function abrir_cajon()
   {
      if($this->print_command == 'remote-printer')
         fwrite($this->file, chr(27).chr(112).chr(48).' ');
      else
         shell_exec("echo '".chr(27).chr(112).chr(48)." '".$this->print_command);
   }
   
   public function center_text($word='', $tot_width=40)
   {
      if( strlen($word) == $tot_width )
         return $word;
      else if( strlen($word) < $tot_width )
         return $this->center_text2($word, $tot_width);
      else
      {
         $result = '';
         $nword = '';
         foreach( explode(' ', $word) as $aux )
         {
            if($nword == '')
               $nword = $aux;
            else if( strlen($nword) + strlen($aux) + 1 <= $tot_width )
               $nword = $nword.' '.$aux;
            else
            {
               if($result != '')
                  $result .= "\n";
               $result .= $this->center_text2($nword, $tot_width);
               $nword = $aux;
            }
         }
         if($nword != '')
         {
            if($result != '')
               $result .= "\n";
            $result .= $this->center_text2($nword, $tot_width);
         }
         return $result;
      }
   }
   
   private function center_text2($word='', $tot_width=40)
   {
      $symbol = " ";
      $middle = round($tot_width / 2);
      $length_word = strlen($word);
      $middle_word = round($length_word / 2);
      $last_position = $middle + $middle_word;
      $number_of_spaces = $middle - $middle_word;
      $result = sprintf("%'{$symbol}{$last_position}s", $word);
      for($i = 0; $i < $number_of_spaces; $i++)
         $result .= "$symbol";
      return $result;
   }
   
   public function random_string($length = 20)
   {
      return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
              0, $length);
   }
}
