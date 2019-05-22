<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single registrer of EmailSent
 *
 * @author Raul <raljopa@gmail.com>
 */
class EditEmailSent extends EditController {

    /**
     *
     * @return string
     */
    public function getModelClassName() {
        return 'EmailSent';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData() {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'email-sent';
        $pagedata['icon'] = 'fas fa-envelope-open';
        $pagedata['menu'] = 'admin';
        $pagedata['showonmenu'] = false;
        return $pagedata;
    }

}
