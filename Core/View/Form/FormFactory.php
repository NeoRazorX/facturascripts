<?php


namespace FacturaScripts\Core\View\Form;


class FormFactory
{

    public static function text($model, $fieldname)
    {
        $type = 'text';
        $children = [];

        $form = new Form($model, $fieldname, $type, $children);

        return $form->render();
    }
}
