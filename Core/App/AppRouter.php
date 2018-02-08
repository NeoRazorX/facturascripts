<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\App;

/**
 * Description of AppRouter
 *
 * @author carlos
 */
class AppRouter
{

    public function getApp()
    {
        $uri = $this->getUri();
        if ('/api' === $uri || '/api/' === substr($uri, 0, 5)) {
            return new AppAPI($uri);
        }

        if ('/cron' === $uri) {
            return new AppCron($uri);
        }

        return new AppController($uri);
    }

    private function getUri()
    {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        return substr($uri, strlen(FS_ROUTE));
    }
}
