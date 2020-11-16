<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

/**
 * A fee for the products.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Tarifa extends Base\ModelClass
{

    use Base\ModelTrait;

    const APPLY_COST = 'coste';
    const APPLY_PRICE = 'pvp';

    /**
     * Formula to apply. Possible values (coste or pvp).
     *
     * @var string
     */
    public $aplicar;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Do not sell above retail price.
     *
     * @var bool
     */
    public $maxpvp;

    /**
     * Do not sell below cost.
     *
     * @var bool
     */
    public $mincoste;

    /**
     * Name of the rate.
     *
     * @var string
     */
    public $nombre;

    /**
     *
     * @var float
     */
    public $valorx;

    /**
     *
     * @var float
     */
    public $valory;

    /**
     * 
     * @param float $cost
     * @param float $price
     *
     * @return float
     */
    public function apply($cost, $price)
    {
        $finalPrice = 0.0;

        switch ($this->aplicar) {
            case self::APPLY_COST:
                $finalPrice += $cost + ($cost * $this->valorx / 100) + $this->valory;
                break;

            case self::APPLY_PRICE:
                $finalPrice += $price - ($price * $this->valorx / 100) - $this->valory;
                break;
        }

        if ($this->maxpvp && $finalPrice > $price) {
            return (float) $price;
        } elseif ($this->mincoste && $finalPrice < $cost) {
            return (float) $cost;
        }

        return $finalPrice > 0 ? $finalPrice : 0.0;
    }

    /**
     * 
     * @param Variante $variant
     * @param Producto $product
     *
     * @return float
     */
    public function applyTo($variant, $product)
    {
        return $this->apply($variant->coste, $variant->precio);
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->aplicar = self::APPLY_PRICE;
        $this->maxpvp = false;
        $this->mincoste = false;
        $this->valorx = 0.0;
        $this->valory = 0.0;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codtarifa';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'tarifas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codtarifa = \trim($this->codtarifa);
        if ($this->codtarifa && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codtarifa)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codtarifa, '%column%' => 'codtarifa', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codtarifa)) {
            $this->codtarifa = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
