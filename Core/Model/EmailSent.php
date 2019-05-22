<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * Model EmailSent
 *
 * @author Raul Jimenez <raljopa@gmail.com>
 */
class EmailSent extends Base\ModelClass {

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $id;

    /**
     * date of send
     * @var date
     */
    public $date;

    /**
     * User than sent email
     * @var string
     */
    public $user;

    /**
     * Subject of email
     * @var string
     */
    public $subject;

    /**
     * Text of email
     * @var text
     */
    public $text;

    /**
     * Electronic address of addressee
     * @var string
     */
    public $addressee;

    /**
     * Reset the values of all model properties.
     */
    public function clear() {
        parent::clear();
        $this->date = date('d-m-Y');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn() {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName() {
        return 'emails_sent';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List') {
        return parent::url($type, $list);
    }

}
