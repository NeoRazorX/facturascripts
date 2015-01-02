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
{
   require_once 'base/fs_mysql.php';
}
else
   require_once 'base/fs_postgresql.php';

require_once 'base/fs_button.php';
require_once 'base/fs_cache.php';
require_once 'base/fs_default_items.php';
require_once 'base/fs_model.php';

require_model('agente.php');
require_model('divisa.php');
require_model('empresa.php');
require_model('fs_access.php');
require_model('fs_page.php');
require_model('fs_user.php');
require_model('fs_extension.php');
require_model('fs_log.php');

/**
 * La clase principal de la que deben heredar todos los controladores
 * (las páginas) de FacturaScripts.
 */
class fs_controller
{
   /**
    * Este objeto permite acceso directo a la base de datos.
    * @var type es una instancia de fs_mysql o fs_postgresql
    */
   protected $db;
   private $uptime;
   private $errors;
   private $messages;
   private $advices;
   private $last_changes;
   private $simbolo_divisas;
   
   /**
    * El usuario que ha hecho login
    * @var type 
    */
   public $user;
   
   /**
    * El elemento del menú de esta página
    * @var type 
    */
   public $page;
   
   /**
    * La página previa (si está definida)
    * @var type 
    */
   public $ppage;
   private $admin_page;
   protected $menu;
   
   /**
    * Indica que archivo HTML hay que cargar
    * @var type 
    */
   public $template;
   
   /**
    * TRUE si queremos mostrar el cuadro de búsqueda.
    * @var type 
    */
   public $custom_search;
   
   /**
    * La cadena obtenida del formulario de búsqueda
    * @var type 
    */
   public $query;
   
   /**
    * Lista de botones que aparecen en la parte superior de la cabecera
    * @var type 
    */
   public $buttons;
   
   /**
    * Permite activar/desactivar la barra de herramientas:
    * botón de recarga, fs_buttons y buscador.
    * @var boolean
    */
   public $show_fs_toolbar;
   
   /**
    * La empresa
    * @var type 
    */
   public $empresa;
   public $default_items;
   
   /**
    * Este objeto permite interactuar con memcache
    * @var type 
    */
   protected $cache;
   
   /**
    * Listado de extensiones de la página
    */
   public $extensions;
   
   /**
    * @param type $name sustituir por __CLASS__
    * @param type $title es el título de la página, y el texto que aparecerá en el menú
    * @param type $folder es el menú dónde quieres colocar el acceso directo
    * @param type $admin debe ser TRUE si quieres que solamente un administrador pueda ver esta página
    * @param type $shmenu debe ser TRUE si quieres añadir el acceso directo en el menú
    * @param type $important debe ser TRUE si quieres que se la primera página que ven los nuevos usuarios
    */
   public function __construct($name='', $title='home', $folder='', $admin=FALSE, $shmenu=TRUE, $important=FALSE)
   {
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      $this->admin_page = $admin;
      $this->errors = array();
      $this->messages = array();
      $this->advices = array();
      $this->simbolo_divisas = array();
      $this->extensions = array();
      
      $this->buttons = array();
      $this->custom_search = FALSE;
      $this->show_fs_toolbar = TRUE;
      $this->ppage = FALSE;
      
      if(strtolower(FS_DB_TYPE) == 'mysql')
      {
         $this->db = new fs_mysql();
      }
      else
         $this->db = new fs_postgresql();
      
      $this->cache = new fs_cache();
      
      /// comprobamos la versión de PHP
      if( floatval( substr(phpversion(), 0, 3) ) < 5.3 )
      {
         $this->new_error_msg('FacturaScripts necesita PHP 5.3 o superior, y tú tienes PHP '.phpversion().'.');
      }
      
      if( $this->db->connect() )
      {
         $this->user = new fs_user();
         $this->page = new fs_page( array('name'=>$name,'title'=>$title,'folder'=>$folder,'version'=>$this->version(),'show_on_menu'=>$shmenu, 'important'=>$important) );
         if($name != '')
         {
            $this->page->save();
         }
         
         $this->empresa = new empresa();
         $this->default_items = new fs_default_items();
         
         /// cargamos las extensiones
         $fsext = new fs_extension();
         foreach($fsext->all() as $ext)
         {
            if($ext->to == $name OR ($ext->type == 'head' AND is_null($ext->to)) )
            {
               $this->extensions[] = $ext;
            }
         }
         
         if( isset($_GET['logout']) )
         {
            $this->template = 'login/default';
            $this->log_out();
         }
         else if( isset($_POST['new_password']) AND isset($_POST['new_password2']) )
         {
            $ips = array();
            
            if( $this->ip_baneada($ips) )
            {
               $this->banear_ip($ips);
               $this->new_error_msg('Tu IP ha sido baneada. Tendrás que esperar 10 minutos antes de volver a intentar entrar.');
            }
            else if($_POST['new_password'] != $_POST['new_password2'])
            {
               $this->new_error_msg('Las contraseñas no coinciden.');
            }
            else if($_POST['new_password'] == '')
            {
               $this->new_error_msg('Tienes que escribir una contraseña nueva.');
            }
            else if($_POST['db_password'] != FS_DB_PASS)
            {
               $this->banear_ip($ips);
               $this->new_error_msg('La contraseña de la base de datos es incorrecta.');
            }
            else
            {
               $suser = $this->user->get($_POST['user']);
               if($suser)
               {
                  $suser->set_password($_POST['new_password']);
                  if( $suser->save() )
                  {
                     $this->new_message('Contraseña cambiada correctamente.');
                  }
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
               
               $this->query = '';
               if( isset($_REQUEST['query']) )
               {
                  $this->query = $_REQUEST['query'];
               }
               
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
         $this->new_error_msg('¡Imposible conectar con la base de datos <b>'.FS_DB_NAME.'</b>!');
      }
   }
   
   /**
    * Devuelve la versión de FacturaScripts
    * @return type versión de FacturaScripts
    */
   public function version()
   {
      if( file_exists('VERSION') )
      {
         $v = file_get_contents('VERSION');
         return trim($v);
      }
      else
         return '0';
   }
   
   /**
    * Cierra la conexión con la base de datos
    */
   public function close()
   {
      $this->db->close();
   }
   
   /**
    * Muestra al usuario un mensaje de error
    * @param type $msg el mensaje a mostrar
    */
   public function new_error_msg($msg=FALSE)
   {
      if($msg)
      {
         $this->errors[] = str_replace("\n", ' ', $msg);
         
         $fslog = new fs_log();
         $fslog->tipo = 'error';
         $fslog->detalle = $msg;
         $fslog->ip = $_SERVER['REMOTE_ADDR'];
         
         if($this->user)
         {
            $fslog->usuario = $this->user->nick;
         }
         
         $fslog->save();
      }
   }
   
   /**
    * Devuelve la lista de errores
    * @return type lista de errores
    */
   public function get_errors()
   {
      $full = array_merge( $this->errors, $this->db->get_errors() );
      
      if( isset($this->empresa) )
      {
         $full = array_merge( $full, $this->empresa->get_errors() );
      }
      
      return $full;
   }
   
   /**
    * Muestra un mensaje al usuario
    * @param type $msg mensaje a mostrar
    */
   public function new_message($msg=FALSE)
   {
      if($msg)
      {
         $this->messages[] = str_replace("\n", ' ', $msg);
      }
   }
   
   /**
    * Devuelve la lista de mensajes
    * @return type lista de mensajes
    */
   public function get_messages()
   {
      return $this->messages;
   }
   
   /**
    * Muestra un consejo al usuario
    * @param type $msg el consejo a mostrar
    */
   public function new_advice($msg=FALSE)
   {
      if($msg)
      {
         $this->advices[] = str_replace("\n", ' ', $msg);
      }
   }
   
   /**
    * Devuelve la lista de consejos
    * @return type lista de consejos
    */
   public function get_advices()
   {
      return $this->advices;
   }
   
   /**
    * Devuelve la URL de esta página (index.php?page=LO-QUE-SEA)
    * @return type
    */
   public function url()
   {
      return $this->page->url();
   }
   
   /**
    * Una IP será baneada si falla más de 5 intentos de login en menos de 10 minutos
    * @param type $ips es un array de IP;intentos;hora
    * @return boolean
    */
   private function ip_baneada(&$ips)
   {
      $baneada = FALSE;
      
      if( file_exists('tmp/'.FS_TMP_NAME.'ip.log') )
      {
         $file = fopen('tmp/'.FS_TMP_NAME.'ip.log', 'r');
         if($file)
         {
            /// leemos las líneas
            while( !feof($file) )
            {
               $linea = explode(';', trim(fgets($file)));
               
               if( intval($linea[2]) > time() )
               {
                  if($linea[0] == $_SERVER['REMOTE_ADDR'] AND intval($linea[1]) > 5)
                  {
                     $baneada = TRUE;
                     
                     if( intval($linea[1]) == 6 )
                     {
                        $fslog = new fs_log();
                        
                        if( isset($_POST['user']) )
                        {
                           $fslog->usuario = $_POST['user'];
                        }
                        
                        $fslog->tipo = 'login';
                        $fslog->detalle = 'IP baneada';
                        $fslog->ip = $_SERVER['REMOTE_ADDR'];
                        $fslog->alerta = TRUE;
                        $fslog->save();
                     }
                  }
                  
                  $ips[] = $linea;
               }
            }
            
            fclose($file);
         }
      }
      
      return $baneada;
   }
   
   /**
    * Baneamos las IPs que fallan más de 5 intentos de login en 10 minutos
    * @param type $ips es un array de IP;intentos;hora
    */
   private function banear_ip(&$ips)
   {
      $file = fopen('tmp/'.FS_TMP_NAME.'ip.log', 'w');
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
   
   /**
    * Devuelve TRUE si el usuario realmente tiene acceso a esta página
    * @return type
    */
   private function log_in()
   {
      $ips = array();
      
      if( $this->ip_baneada($ips) )
      {
         $this->banear_ip($ips);
         $this->new_error_msg('Tu IP ha sido baneada. Tendrás que esperar 10 minutos antes de volver a intentar entrar.');
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
               $user->set_password('demo');
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
                  
                  $fslog = new fs_log();
                  $fslog->usuario = $user->nick;
                  $fslog->tipo = 'login';
                  $fslog->detalle = 'Login correcto.';
                  $fslog->ip = $user->last_ip;
                  $fslog->save();
               }
               else
               {
                  $this->new_error_msg('¡Contraseña incorrecta!');
                  $this->banear_ip($ips);
               }
            }
            else
            {
               $this->new_error_msg('El usuario '.$_POST['user'].' no existe!');
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
            else if( !is_null($user->log_key) )
            {
               $this->new_message('¡Cookie no válida! Alguien ha accedido a esta cuenta desde otro PC con IP: '
                       .$user->last_ip.". Si has sido tú, ignora este mensaje.");
               $this->log_out();
            }
         }
         else
         {
            $this->new_error_msg('¡El usuario '.$_COOKIE['user'].' no existe!');
            $this->log_out();
            $this->user->clean_cache(TRUE);
            $this->cache->clean();
         }
      }
      
      return $this->user->logged_on;
   }
   
   /**
    * Gestiona el cierre de sesión
    */
   private function log_out()
   {
      setcookie('logkey', '', time()-FS_COOKIES_EXPIRE);
      
      $fslog = new fs_log();
      
      if( isset($_COOKIE['user']) )
      {
         $fslog->usuario = $_COOKIE['user'];
      }
      
      $fslog->tipo = 'login';
      $fslog->detalle = 'El usuario ha cerrado la sesión.';
      $fslog->ip = $_SERVER['REMOTE_ADDR'];
      $fslog->save();
   }
   
   /**
    * Devuelve la duración de la ejecución de la página
    * @return type un string con la duración de la ejecución
    */
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
   
   /**
    * Devuelve el número de consultas SQL (SELECT) que se han ejecutado
    * @return type
    */
   public function selects()
   {
      return $this->db->get_selects();
   }
   
   /**
    * Devuleve el número de transacciones SQL que se han ejecutado
    * @return type
    */
   public function transactions()
   {
      return $this->db->get_transactions();
   }
   
   /**
    * Devuelve el listado de consultas SQL que se han ejecutados
    * @return type lista de consultas SQL
    */
   public function get_db_history()
   {
      return $this->db->get_history();
   }
   
   /**
    * Carga el menú de facturaScripts
    * @param type $reload TRUE si quieres recargar
    */
   protected function load_menu($reload=FALSE)
   {
      $this->menu = $this->user->get_menu($reload);
   }
   
   /**
    * Devuelve la lista de menús
    * @return type lista de menús
    */
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
   
   /**
    * Devuelve la lista de elementos de un menú seleccionado
    * @param type $f el menú seleccionado
    * @return type lista de elementos del menú
    */
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
   
   /**
    * Esta es la función que se ejecuta en el constructor si, y sólo si,
    * el usuario realmente tiene acceso a la página
    */
   protected function process()
   {
      
   }
   
   /**
    * Redirecciona a la página predeterminada para el usuario
    */
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
   
   /**
    * Devuelve TRUE si la página sólo es accesible para administradores
    * @return type TRUE si la página sólo es accesible para administradores
    */
   public function is_admin_page()
   {
      return $this->admin_page;
   }
   
   /**
    * Establecemos los elementos por defecto, pero no se guardan.
    * Para guardarlos hay que usar las funciones fs_controller::save_lo_que_sea().
    * La clase fs_default_items sólo se usa para indicar valores
    * por defecto a los modelos.
    */
   private function set_default_items()
   {
      /// gestionamos la página de inicio
      if( isset($_GET['default_page']) )
      {
         if($_GET['default_page'] == 'FALSE')
         {
            $this->default_items->set_default_page(NULL);
            $this->user->fs_page = NULL;
         }
         else
         {
            $this->default_items->set_default_page( $this->page->name );
            $this->user->fs_page = $this->page->name;
         }
         
         $this->user->save();
      }
      else if( is_null($this->default_items->default_page()) )
         $this->default_items->set_default_page( $this->user->fs_page );
      
      if( is_null($this->default_items->showing_page()) )
         $this->default_items->set_showing_page( $this->page->name );
      
      if( is_null($this->user->codejercicio) )
      {
         $this->default_items->set_codejercicio( $this->empresa->codejercicio );
      }
      else
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
   
   /**
    * Establece un ejercicio como predeterminado para este usuario
    * @param type $cod el código del ejercicio
    */
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
   
   /**
    * Establece un almacén como predeterminado para este usuario
    * @param type $cod el código del almacén
    */
   protected function save_codalmacen($cod)
   {
      setcookie('default_almacen', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codalmacen($cod);
   }
   
   /**
    * Establece un cliente como predeterminado para este usuario
    * @param type $cod el código del cliente
    */
   protected function save_codcliente($cod)
   {
      setcookie('default_cliente', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codcliente($cod);
   }
   
   /**
    * Establece una divisa como predeterminada para este usuario
    * @param type $cod el código de la divisa
    */
   protected function save_coddivisa($cod)
   {
      setcookie('default_divisa', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_coddivisa($cod);
   }
   
   /**
    * Establece una familia como predeterminada para este usuario
    * @param type $cod el código de la familia
    */
   protected function save_codfamilia($cod)
   {
      setcookie('default_familia', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codfamilia($cod);
   }
   
   /**
    * Establece una forma de pago como predeterminada para este usuario
    * @param type $cod el código de la forma de pago
    */
   protected function save_codpago($cod)
   {
      setcookie('default_formapago', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codpago($cod);
   }
   
   /**
    * Establece un impuesto (IVA) como predeterminado para este usuario
    * @param type $cod el código del iumpuesto
    */
   protected function save_codimpuesto($cod)
   {
      setcookie('default_impuesto', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codimpuesto($cod);
   }
   
   /**
    * Establece un código de país como predeterminado para este usuario
    * @param type $cod el código del país
    */
   protected function save_codpais($cod)
   {
      setcookie('default_pais', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codpais($cod);
   }
   
   /**
    * Establece un proveedor como predeterminado para este usuario
    * @param type $cod el código del proveedor
    */
   protected function save_codproveedor($cod)
   {
      setcookie('default_proveedor', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codproveedor($cod);
   }
   
   /**
    * Establece una serie como predeterminada para este usuario
    * @param type $cod el código de la serie
    */
   protected function save_codserie($cod)
   {
      setcookie('default_serie', $cod, time()+FS_COOKIES_EXPIRE);
      $this->default_items->set_codserie($cod);
   }
   
   /**
    * Devuelve la fecha actual
    * @return type la fecha en formato día-mes-año
    */
   public function today()
   {
      return date('d-m-Y');
   }
   
   /**
    * Devuelve la hora actual
    * @return type la hora en formato hora:minutos:segundos
    */
   public function hour()
   {
      return Date('H:i:s');
   }
   
   /**
    * Devuelve un string aleatorio de longitud $length
    * @param type $length la longitud del string
    * @return type la cadena aleatoria
    */
   public function random_string($length = 30)
   {
      return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
              0, $length);
   }
   
   /**
    * He detectado que algunos navegadores, en algunos casos, envían varias veces la
    * misma petición del formulario. En consecuencia se crean varios modelos (asientos,
    * albaranes, etc...) con los mismos datos, es decir, duplicados.
    * Para solucionarlo añado al formulario un campo petition_id con una cadena
    * de texto aleatoria. Al llamar a esta función se comprueba si esa cadena
    * ya ha sido almacenada, de ser así devuelve TRUE, así no hay que gabar los datos,
    * si no, se almacena el ID y se devuelve FALSE.
    * @param type $id el identificador de la petición
    * @return boolean TRUE si la petición está duplicada
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
   
   /**
    * Devuelve información del sistema para el informe de errores
    * @return type la información del sistema
    */
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
      
      $txt .= 'plugins: '.join(',', $GLOBALS['plugins'])."\n";
      
      if( isset($_SERVER['REQUEST_URI']) )
         $txt .= 'url: '.$_SERVER['REQUEST_URI']."\n------";
      
      foreach($this->get_errors() as $e)
         $txt .= "\n" . $e;
      
      return str_replace('"', "'", $txt);
   }
   
   /**
    * Devuelve el símbolo de divisa predeterminado
    * o bien el símbolo de la divisa seleccionada.
    * @param type $coddivisa
    * @return string
    */
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
   
   /**
    * Devuelve un string con el precio en el formato predefinido y con la
    * divisa seleccionada (o la predeterminada).
    * 
    * @param type $precio
    * @param type $coddivisa
    * @param type $simbolo
    * @return type
    */
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
   
   /**
    * Devuelve un string con el número en el formato de número predeterminado.
    * @param type $num
    * @param type $decimales
    * @param type $js
    * @return type
    */
   public function show_numero($num=0, $decimales=FS_NF0, $js=FALSE)
   {
      if($js)
         return number_format($num, $decimales, '.', '');
      else
         return number_format($num, $decimales, FS_NF1, FS_NF2);
   }
   
   /**
    * Añade un elemento a la lista de cambios del usuario.
    * @param type $txt texto descriptivo.
    * @param type $url URL del elemento (albarán, factura, artículos...).
    * @param type $nuevo TRUE si el elemento es nuevo, FALSE si se ha modificado.
    */
   public function new_change($txt, $url, $nuevo=FALSE)
   {
      $this->get_last_changes();
      if( count($this->last_changes) > 0 )
      {
         if($this->last_changes[0]['url'] == $url)
            $this->last_changes[0]['nuevo'] = $nuevo;
         else
            array_unshift($this->last_changes, array('texto' => ucfirst($txt), 'url' => $url, 'nuevo' => $nuevo, 'cambio' => date('d-m-Y H:i:s')) );
      }
      else
         array_unshift($this->last_changes, array('texto' => ucfirst($txt), 'url' => $url, 'nuevo' => $nuevo, 'cambio' => date('d-m-Y H:i:s')) );
      
      /// sólo queremos 10 elementos
      $num = 10;
      foreach($this->last_changes as $i => $value)
      {
         if($num > 0)
         {
            $num--;
         }
         else
         {
            unset($this->last_changes[$i]);
         }
      }
      
      $this->cache->set('last_changes_'.$this->user->nick, $this->last_changes);
   }
   
   /**
    * Devuelve la lista con los últimos cambios del usuario.
    */
   public function get_last_changes()
   {
      if( !isset($this->last_changes) )
      {
         $this->last_changes = $this->cache->get_array('last_changes_'.$this->user->nick);
      }
      
      return $this->last_changes;
   }
   
   /**
    * Devuelve el HTML con la primera "página" de la comunidad, es decir,
    * el índice de tutoriales.
    */
   public function get_community_html()
   {
      if( file_exists('tmp/community_index.html') AND mt_rand(0, 14) > 0 )
      {
         return file_get_contents('tmp/community_index.html');
      }
      else
      {
         $url = FS_COMMUNITY_URL.'/iframe.php?version='.$this->version();
         if( substr($url, 0, 1) == '/' )
         {
            $url = 'http:'.$url;
         }
         
         $html = file_get_contents($url);
         file_put_contents('tmp/community_index.html', $html);
         return $html;
      }
   }
}
