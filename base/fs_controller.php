<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of fs_controller
 *
 * @author carlos
 */
class fs_controller {
    public $template;
    private static $_fsPath;
    
    public function __construct($folder = '') {
        if(!isset(self::$_fsPath)) {
            self::$_fsPath = $folder;
        }
        
        $this->template = 'template_not_found.html.twig';
    }
}
