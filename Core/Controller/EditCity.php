<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Edit City
 */
class EditCity extends EditController
{
    /**
     * Get model class name
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'City';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'cities';
        $data['icon'] = 'fas fa-city';

        return $data;
    }
}
