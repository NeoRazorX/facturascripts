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

if(strtolower(FS_DB_TYPE) == 'mysql')
   require_once 'base/fs_mysql.php';
else
   require_once 'base/fs_postgresql.php';

require_once 'base/fs_button.php';
require_once 'base/fs_cache.php';
require_once 'base/fs_default_items.php';
require_once 'model/fs_access.php';
require_once 'model/fs_page.php';
require_once 'model/fs_user.php';

require_model('agente.php');
require_model('divisa.php');
require_model('empresa.php');

class fs_controller
{
   protected $db;
   private $uptime;
   private $errors;
   private $messages;
   private $advices;
   private $simbolo_divisas;
   public $user;
   public $page;
   public $ppage;
   private $admin_page;
   protected $menu;
   public $template;
   public $css_file;
   public $custom_search;
   public $query;
   public $buttons;
   public $empresa;
   public $default_items;
   protected $cache;
   
   public function __construct($name='', $title='home', $folder='', $admin=FALSE, $shmenu=TRUE, $important=FALSE)
   {
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      $this->admin_page = $admin;
      $this->errors = array();
      $this->messages = array();
      $this->advices = array();
      $this->simbolo_divisas = array();
      
      if(strtolower(FS_DB_TYPE) == 'mysql')
         $this->db = new fs_mysql();
      else
         $this->db = new fs_postgresql();
      
      $this->cache = new fs_cache();
      $this->set_css_file();
      
      if( $this->db->connect() )
      {
         $this->user = new fs_user();
         $this->page = new fs_page( array('name'=>$name, 'title'=>$title, 'folder'=>$folder,
             'version'=>$this->version(), 'show_on_menu'=>$shmenu, 'important'=>$important) );
         $this->ppage = FALSE;
         $this->empresa = new empresa();
         $this->default_items = new fs_default_items();
         
         if( isset($_GET['logout']) )
         {
            $this->template = 'login/default';
            $this->log_out();
         }
         else if( isset($_POST['new_password']) AND isset($_POST['new_password2']) )
         {
            $ips = array();
            
            if($_POST['new_password'] != $_POST['new_password2'])
               $this->new_error_msg('Las contraseñas no coinciden.');
            else if($_POST['new_password'] == '')
               $this->new_error_msg('Tienes que escribir una contraseña nueva.');
            else if($_POST['db_password'] != FS_DB_PASS)
               $this->new_error_msg('La contraseña de la base de datos es incorrecta.');
            else if( $this->ip_baneada($ips) )
            {
               $this->banear_ip($ips);
               $this->new_error_msg('Tu IP ha sido baneada. Tendrás que esperar
                  10 minutos antes de volver a intentar entrar.');
            }
            else
            {
               $suser = $this->user->get($_POST['user']);
               if($suser)
               {
                  $suser->set_password($_POST['new_password']);
                  if( $suser->save() )
                     $this->new_message('Contraseña cambiada correctamente.');
                  else
                     $this->new_error_msg('Imposible cambiar la contraseña del usuario.');
               }
            }
            
            $this->template = 'login/default';
         }
         else if( !$this->log_in() )
         {
            $this->template = 'login/default';
         }
         else if( $this->user->have_access_to($this->page->name, $this->admin_page) )
         {
            if($name == '')
            {
               $this->template = 'index';
            }
            else
            {
               $this->set_default_items();
               
               $this->template = $name;
               $this->buttons = array();
               
               $this->custom_search = FALSE;
               if( isset($_POST['query']) )
                  $this->query = $_POST['query'];
               else if( isset($_GET['query']) )
                  $this->query = $_GET['query'];
               else
                  $this->query = '';
               
               $this->process();
            }
         }
         else if($name == '')
         {
            $this->template = 'index';
         }
         else
         {
            $this->template = 'access_denied';
            $this->user->clean_cache(TRUE);
            $this->empresa->clean_cache();
         }
      }
      else
      {
         $this->template = 'no_db';
         $this->new_error_msg('¡Imposible conectar con la base de datos!');
      }
   }
   
   public function version()
   {
      return '2014.3c';
   }
   
   public function close()
   {
      $this->db->close();
   }
   
   public function new_error_msg($msg=FALSE)
   {
      if( $msg )
         $this->errors[] = $msg;
   }
   
   public function get_errors()
   {
      $full = array_merge( $this->errors, $this->db->get_errors() );
      
      if( isset($this->empresa) )
         $full = array_merge( $full, $this->empresa->get_errors() );
      
      return $full;
   }
   
   public function new_message($msg=FALSE)
   {
      if( $msg )
         $this->messages[] = $msg;
   }
   
   public function get_messages()
   {
      return $this->messages;
   }
   
   public function new_advice($msg=FALSE)
   {
      if( $msg )
         $this->advices[] = $msg;
   }
   
   public function get_advices()
   {
      return $this->advices;
   }
   
   public function url()
   {
      return $this->page->url();
   }
   
   /*
    * Una IP será baneada si falla más de 5 intentos de login en menos de 10 minutos
    */
   private function ip_baneada(&$ips)
   {
      $baneada = FALSE;
      
      if( file_exists('tmp/ip.log') )
      {
         $file = fopen('tmp/ip.log', 'r');
         if($file)
         {
            /// leemos las líneas
            while( !feof($file) )
            {
               $linea = explode(';', trim(fgets($file)));
               
               if( intval($linea[2]) > time() )
               {
                  if($linea[0] == $_SERVER['REMOTE_ADDR'] AND intval($linea[1]) > 5)
                     $baneada = TRUE;
                  
                  $ips[] = $linea;
               }
            }
            
            fclose($file);
         }
      }
      
      return $baneada;
   }
   
   /*
    * Baneamos las IPs que fallan más de 5 intentos de login en 10 minutos
    */
   private function banear_ip(&$ips)
   {
      $file = fopen('tmp/ip.log', 'w');
      if($file)
      {
         $encontrada = FALSE;
         
         foreach($ips as $ip)
         {
            if($ip[0] == $_SERVER['REMOTE_ADDR'])
            {
               fwrite( $file, $ip[0].';'.( 1+intval($ip[1]) ).';'.( time()+600 ) );
               $encontrada = TRUE;
            }
            else
               fwrite( $file, join(';', $ip) );
         }
         
         if(!$encontrada)
            fwrite( $file, $_SERVER['REMOTE_ADDR'].';1;'.( time()+600 ) );
         
         fclose($file);
      }
   }
   
   private function log_in()
   {
      $ips = array();
      
      if( $this->ip_baneada($ips) )
      {
         $this->banear_ip($ips);
         $this->new_error_msg('Tu IP ha sido baneada. Tendrás que esperar
            10 minutos antes de volver a intentar entrar.');
      }
      else if( isset($_POST['user']) AND isset($_POST['password']) )
      {
         if( FS_DEMO ) /// en el modo demo nos olvidamos de la contraseña
         {
            $user = $this->user->get($_POST['user']);
            if( !$user )
            {
               $user = new fs_user();
               $user->nick = $_POST['user'];
               $user->password = 'demo';
               $user->admin = TRUE;
               
               /// creamos un agente para asociarlo
               $agente = new agente();
               $agente->codagente = $agente->get_new_codigo();
               $agente->nombre = $_POST['user'];
               $agente->apellidos = 'Demo';
               if( $agente->save() )
                  $user->codagente = $agente->codagente;
            }
            
            $user->new_logkey();
            if( $user->save() )
            {
               setcookie('user', $user->nick, time()+FS_COOKIES_EXPIRE);
               setcookie('logkey', $user->log_key, time()+FS_COOKIES_EXPIRE);
               $this->user = $user;
               $this->load_menu();
            }
         }
         else
         {
            $user = $this->user->get($_POST['user']);
            $password = strtolower($_POST['password']);
            if($user)
            {
               if( $user->password == sha1($password) )
               {
                  $user->new_logkey();
                  if( $user->save() )
                  {
                     setcookie('user', $user->nick, time()+FS_COOKIES_EXPIRE);
                     setcookie('logkey', $user->log_key, time()+FS_COOKIES_EXPIRE);
                     $this->user = $user;
                     $this->load_menu();
                  }
                  else
                     $this->new_error_msg('Imposible guardar los datos de usuario.');
               }
               else
               {
                  $this->new_error_msg('¡Contraseña incorrecta!');
                  $this->banear_ip($ips);
               }
            }
            else
            {
               $this->new_error_msg('El usuario no existe!');
               $this->user->clean_cache(TRUE);
               $this->cache->clean();
            }
         }
      }
      else if( isset($_COOKIE['user']) AND isset($_COOKIE['logkey']) )
      {
         $user = $this->user->get($_COOKIE['user']);
         if($user)
         {
            if($user->log_key == $_COOKIE['logkey'])
            {
               $user->logged_on = TRUE;
               $user->update_login();
               $this->user = $user;
               $this->load_menu();
            }
            else
            {
               $this->new_message('¡Cookie no válida! Tú o alguien ha accedido
                  a esta cuenta desde otro PC.');
               $this->log_out();
            }
         }
         else
         {
            $this->new_message('¡El usuario no existe!');
            $this->log_out();
            $this->user->clean_cache(TRUE);
            $this->cache->clean();
         }
      }
      
      return $this->user->logged_on;
   }
   
   private function log_out()
   {
      setcookie('logkey', '', time()-FS_COOKIES_EXPIRE);
   }
   
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
   
   public function selects()
   {
      return $this->db->get_selects();
   }
   
   public function transactions()
   {
      return $this->db->get_transactions();
   }
   
   public function get_db_history()
   {
      return $this->db->get_history();
   }
   
   protected function load_menu($reload=FALSE)
   {
      $this->menu = $this->user->get_menu($reload);
      
      /// actualizamos los datos de la página
      foreach($this->menu as $m)
      {
         if($m->name == $this->page->name AND $m->version != $this->page->version)
         {
            if( !$this->page->save() )
               $this->new_error_msg('Imposible actualizar los datos de esta página.');
            
            break;
         }
      }
   }
   
   public function folders()
   {
      $folders = array();
      foreach($this->menu as $m)
      {
         if($m->folder!='' AND $m->show_on_menu AND !in_array($m->folder, $folders) )
            $folders[] = $m->folder;
      }
      return $folders;
   }
   
   public function pages($f='')
   {
      $pages = array();
      foreach($this->menu as $p)
      {
         if($f == $p->folder AND $p->show_on_menu AND !in_array($p, $pages) )
            $pages[] = $p;
      }
      return $pages;
   }
   
   protected function process()
   {
      
   }
   
   public function select_default_page()
   {
      if( $this->db->connected() )
      {
         if( $this->user->logged_on )
         {
            $url = FALSE;
            
            if( is_null($this->user->fs_page) )
            {
               $url = 'index.php?page=admin_pages';
               
               /*
                * Cuando un usuario no tiene asignada una página por defecto,
                * se selecciona la primera página importante a la que tiene acceso.
                */
               foreach($this->menu as $p)
               {
                  if($p->important)
                  {
                     $url = $p->url();
                     break;
                  }
                  else if($p->show_on_menu)
                     $url = $p->url();
               }
            }
            else
               $url = 'index.php?page=' . $this->user->fs_page;
            
            Header('location: '.$url);
         }
      }
   }
   
   private function set_css_file()
   {
      if( isset($_GET['css_file']) )
      {
         if( file_exists('view/css/'.$_GET['css_file']) )
         {
            $this->css_file = $_GET['css_file'];
            setcookie('css_file', $_GET['css_file'], time()+FS_COOKIES_EXPIRE);
         }
         else
         {
            $this->new_error_msg("Archivo CSS no encontrado.");
            $this->css_file = 'base.css';
         }
      }
      else if( isset($_COOKIE['css_file']) )
      {
         if( file_exists('view/css/'.$_COOKIE['css_file']) )
            $this->css_file = $_COOKIE['css_file'];
         else
         {
            $this->new_error_msg("Archivo CSS no encontrado.");
            $this->css_file = 'base.css';
            setcookie('css_file', $this->css_file, time()+FS_COOKIES_EXPIRE);
         }
      }
      else
         $this->css_file = 'base.css';
   }
   
   public function is_admin_page()
   {
      return $this->admin_page;
   }
   
   private function set_default_items()
   {
      /// gestionamos la página de inicio
      if( isset($_GET['default_page']) )
      {
         $this->default_items->set_default_page( $this->page->name );
         $this->user->fs_page = $this->page->name;
         $this->user->save();
      }
      else if( is_null($this->default_items->default_page()) )
         $this->default_items->set_default_page( $this->user->fs_page );
      
      if( is_null($this->default_items->showing_page()) )
         $this->default_items->set_showing_page( $this->page->name );
      
      /*
       * Establecemos los elementos por defecto, pero no se guardan.
       * Para guardarlos hay que usar las funciones fs_controller::save_lo_que_sea().
       * La clase fs_default_items sólo se usa para indicar valores
       * por defecto a los modelos.
       */
      $this->default_items->set_codejercicio( $this->user->codejercicio );
      
      if( isset($_COOKIE['default_almacen']) )
         $this->default_items->set_codalmacen( $_COOKIE['default_almacen'] );
      else
         $this->default_items->set_codalmacen( $this->empresa->codalmacen );
      
      if( isset($_COOKIE['default_cliente']) )
         $this->default_items->set_codcliente( $_COOKIE['default_cliente'] );
      
      if( isset($_COOKIE['default_divisa']) )
         $this->default_items->set_coddivisa( $_COOKIE['default_divisa'] );
      else
         $this->default_items->set_coddivisa( $this->empresa->coddivisa );
      
      if( isset($_COOKIE['default_familia']) )
         $this->default_items->set_codfamilia( $_COOKIE['default_familia'] );
      
      if( isset($_COOKIE['default_formapago']) )
         $this->default_items->set_codpago( $_COOKIE['default_formapago'] );
      else
         $this->default_items->set_codpago( $this->empresa->codpago );
      
      if( isset($_COOKIE['default_impuesto']) )
         $this->default_items->set_codimpuesto( $_COOKIE['default_impuesto'] );
      
      if( isset($_COOKIE['default_pais']) )
         $this->default_items->set_codpais( $_COOKIE['default_pais'] );
      else
         $this->default_items->set_codpais( $this->empresa->codpais );
      
      if( isset($_COOKIE['default_proveedor']) )
         $this->default_items->set_codproveedor( $_COOKIE['default_proveedor'] );
      
      if( isset($_COOKIE['default_serie']) )
         $this->default_items->set_codserie( $_COOKIE['default_serie'] );
      else
         $this->default_items->set_codserie( $this->empresa->codserie );
   }
   
   protected function save_codejercicio($cod)
   {
      if($cod != $this->user->codejercicio)
      {
         $this->default_items->set_codejercicio($cod);
         $this->user->codejercicio = $cod;
         if( !$this->user->save() )
         {
            $this->new_error_msg('Error al establecer el ejercicio '.$cod.
               ' como ejercicio predeterminado para este usuario.');
         }
      }
   }
   
   protected function save_codalmacen($cod)
   {
      setcookie('default_almacen', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codalmacen($cod);
   }
   
   protected function save_codcliente($cod)
   {
      setcookie('default_cliente', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codcliente($cod);
   }
   
   protected function save_coddivisa($cod)
   {
      setcookie('default_divisa', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_coddivisa($cod);
   }
   
   protected function save_codfamilia($cod)
   {
      setcookie('default_familia', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codfamilia($cod);
   }
   
   protected function save_codpago($cod)
   {
      setcookie('default_formapago', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codpago($cod);
   }
   
   protected function save_codimpuesto($cod)
   {
      setcookie('default_impuesto', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codimpuesto($cod);
   }
   
   protected function save_codpais($cod)
   {
      setcookie('default_pais', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codpais($cod);
   }
   
   protected function save_codproveedor($cod)
   {
      setcookie('default_proveedor', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codproveedor($cod);
   }
   
   protected function save_codserie($cod)
   {
      setcookie('default_serie', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codserie($cod);
   }
   
   public function today()
   {
      return date('d-m-Y');
   }
   
   public function hour()
   {
      return Date('H:i:s');
   }
   
   public function random_string($length = 30)
   {
      return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
              0, $length);
   }
   
   /*
    * He detectado que algunos navegadores, en algunos casos, envían varias veces la
    * misma petición del formulario. En consecuencia se crean varios modelos (asientos,
    * albaranes, etc...) con los mismos datos, es decir, duplicados.
    * Para solucionarlo añado al formulario un campo petition_id con una cadena
    * de texto aleatoria. Al llamar a esta función se comprueba si esa cadena
    * ya ha sido almacenada, de ser así devuelve TRUE, así no hay que gabar los datos,
    * si no, se almacena el ID y se devuelve FALSE.
    */
   protected function duplicated_petition($id)
   {
      $ids = $this->cache->get_array('petition_ids');
      if( in_array($id, $ids) )
         return TRUE;
      else
      {
         $ids[] = $id;
         $this->cache->set('petition_ids', $ids, 300);
         return FALSE;
      }
   }
   
   public function system_info()
   {
      $txt = 'facturascripts: '.$this->version()."\n";
      $txt .= 'os: '.php_uname()."\n";
      $txt .= 'php: '.phpversion()."\n";
      $txt .= 'database type: '.FS_DB_TYPE."\n";
      $txt .= 'database version: '.$this->db->version()."\n";
      
      if( $this->cache->connected() )
         $txt .= "memcache: YES\n";
      else
         $txt .= "memcache: NO\n";
      
      $txt .= 'memcache version: '.$this->cache->version()."\n";
      
      if( isset($_SERVER['REQUEST_URI']) )
         $txt .= 'url: '.$_SERVER['REQUEST_URI']."\n------";
      
      foreach($this->get_errors() as $e)
         $txt .= "\n" . $e;
      
      return str_replace('"', "'", $txt);
   }
   
   public function simbolo_divisa($coddivisa = FALSE)
   {
      if(!$coddivisa)
         $coddivisa = $this->empresa->coddivisa;
      
      if( isset($this->simbolo_divisas[$coddivisa]) )
         return $this->simbolo_divisas[$coddivisa];
      else
      {
         $divisa = new divisa();
         $divi0 = $divisa->get($coddivisa);
         if($divi0)
         {
            $this->simbolo_divisas[$coddivisa] = $divi0->simbolo;
            return $divi0->simbolo;
         }
         else
            return '?';
      }
   }
   
   public function show_precio($precio=0, $coddivisa=FALSE, $simbolo=TRUE)
   {
      if($coddivisa === FALSE)
         $coddivisa = $this->empresa->coddivisa;
      
      if(FS_POS_DIVISA == 'right')
      {
         if($simbolo)
            return number_format($precio, FS_NF0, FS_NF1, FS_NF2).' '.$this->simbolo_divisa($coddivisa);
         else
            return number_format($precio, FS_NF0, FS_NF1, FS_NF2).' '.$coddivisa;
      }
      else
      {
         if($simbolo)
            return $this->simbolo_divisa($coddivisa).number_format($precio, FS_NF0, FS_NF1, FS_NF2);
         else
            return $coddivisa.' '.number_format($precio, FS_NF0, FS_NF1, FS_NF2);
      }
   }
   
   public function show_numero($num=0, $decimales=FS_NF0, $js=FALSE)
   {
      if($js)
         return number_format($num, $decimales, '.', '');
      else
         return number_format($num, $decimales, FS_NF1, FS_NF2);
   }
}

?>