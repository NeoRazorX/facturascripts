<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of fs_plugin_manager
 *
 * @author carlos
 */
class fs_plugin_manager {

    public $enabledPluggins;
    private static $_fsFolder;

    public function __construct($folder = '') {
        if (!isset(self::$_fsFolder)) {
            self::$_fsFolder = $folder;
        }

        if (file_exists(self::$_fsFolder . '/plugin.list')) {
            $plugFileData = file_get_contents(self::$_fsFolder . '/plugin.list');
            if ($plugFileData) {
                $this->enabledPluggins = explode(',', $plugFileData);
            }
        } else {
            $this->enabledPluggins = [];
        }
    }

}
