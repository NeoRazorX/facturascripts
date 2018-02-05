<?php 
namespace FacturaScripts\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AgentController extends Controller
{
    /**
     * Matches /agent exactly
     *
     * Equivalent to /index.php?page=ListAgente
     *
     * @Route("/agent", name="agent_list")
     */
    public function list()
    {
        // ...
        return new Response('Hello AgentController', Response::HTTP_OK);
    }
}