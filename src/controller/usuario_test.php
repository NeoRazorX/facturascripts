<?php
namespace FacturaScripts\Controller;

use FacturaScripts\Base\fs_controller;
use FacturaScripts\Base\fs_plugin_manager;
use FacturaScripts\Models\Usuario;

class usuario_test extends fs_controller {

	public $usuario;

	public function __construct($folder = '', $className = __CLASS__) {
	    parent::__construct($folder, $className);
	}

	public function run() {
	    parent::run();
	    $this->usuario = Usuario::first();

	    if ( $this->request->getMethod() == "POST" )
	    {
	    	$this->usuario->nick = $this->request->request->get('nick');
	    	$this->usuario->email = $this->request->request->get('email');

	    	if ( !$this->usuario->save() ) {
	    		$this->new_message($this->i18n->trans('user-unsaved'));
	    	}
	    	$this->new_message($this->i18n->trans('user-saved'));
	    }
	}

}