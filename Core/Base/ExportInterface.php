<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\Base;

/**
 * Description of ExportInterface
 *
 * @author carlos
 */
interface ExportInterface
{
    public function newDoc($model);
    public function newListDoc($cursor);
}
