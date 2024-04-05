<?php


namespace FacturaScripts\Core\Base\Contract;


use FacturaScripts\Core\Model\Base\SalesDocument;

interface SalesModalHTMLModInterface
{
    public function apply(SalesDocument &$model, array $formData);

    public function addProductColumnsTable();

}