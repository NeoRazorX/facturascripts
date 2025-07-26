<?php declare(strict_types=1);

namespace FacturaScripts\Core\Model;

class SettingsModel extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $classnamemodel;

    /** @var string */
    public $idmodel;

    /** @var string */
    public $idempresa;

    /** @var array */
    public $settings;

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'settings_model';
    }

    public function loadFromData(array $data = [], array $exclude = []): void
    {
        parent::loadFromData($data, ['settings', 'action']);
        $this->settings = isset($data['settings']) ? json_decode($data['settings'], true) : [];
    }

    protected function saveUpdate(array $values = []): bool
    {
        // agregamos a la propiedad settings las propiedades que hay en el modelo
        // y que nos vienen de la request/inputs
        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = empty($this->{$key}) ? null : $this->{$key};
        }

        if (is_array($this->settings)) {
            $this->settings = json_encode($this->settings);
        }

        return parent::saveUpdate($values);
    }
}
