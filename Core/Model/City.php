<?php

namespace FacturaScripts\Core\Model;

/**
 * City
 */
class City extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Id ciudad
     *
     * @var int
     */
    public $idciudad;

    /**
     * Id provincia
     *
     * @var int
     */
    public $idprovincia;

    /**
     * Ciudad
     *
     * @var string
     */
    public $ciudad;

    /**
     * Code id
     *
     * @var string
     */
    public $codeid;

    /**
     * Reset the values of all model properties.
     */
    public function clear() {
        parent::clear();

        $this->codeid = null;
    }

    /**
     * Primary column
     *
     * @return string
     */
    public static function primaryColumn() {
        return 'idciudad';
    }

    /**
     * Table name
     *
     * @return string
     */
    public static function tableName() {
        return 'ciudades';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List')
    {
        return parent::url($type, $list);
    }
}
