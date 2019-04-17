<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of EditCronJob
 *
 * @author Raul Jimenez <raljopa@gmail.com>
 */
class EditCronJob extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'CronJob';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'cronJobs';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fas fa-retweet';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setSettings('Edit' . $this->getModelClassName(), 'btnNew', false);
    }
}
