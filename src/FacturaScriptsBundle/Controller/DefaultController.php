<?php

namespace FacturaScriptsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FacturaScriptsBundle:Default:index.html.twig');
    }
}
