<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;

class Down extends Controller
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->setTemplate(false);

        if (false === $this->user->admin) {
            throw new KernelException('AccessDenied', Tools::lang()->trans('access-denied'));
        }

        Kernel::down();

        $this->redirect('/');
    }
}
