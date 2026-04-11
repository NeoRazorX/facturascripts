<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Role;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of Wizard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Wizard extends Controller
{
    const ITEM_SELECT_LIMIT = 500;
    const NEW_DEFAULT_PAGE = 'Dashboard';

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard';
        $data['icon'] = 'fa-solid fa-wand-magic-sparkles';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getRegimenIva(): array
    {
        $list = ['' => '------'];
        foreach (RegimenIVA::all() as $key => $value) {
            $list[$key] = Tools::trans($value);
        }
        return $list;
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     * @param bool $addEmpty
     *
     * @return array
     */
    public function getSelectValues(string $modelName, bool $addEmpty = false): array
    {
        $values = $addEmpty ? ['' => '------'] : [];
        $modelName = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $model = new $modelName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->inputOrQuery('action', '');
        switch ($action) {
            case 'step1':
                $this->saveStep1();
                break;

            case 'step2':
                $this->saveStep2();
                break;

            case 'step3':
                $this->saveStep3();
                break;

            default:
                if (empty($this->empresa->email) && $this->user->email) {
                    $this->empresa->email = $this->user->email;
                    $this->empresa->save();
                }
        }
    }

    protected function finalRedirect(): void
    {
        // redirect to the home page
        $this->redirect($this->user->homepage, 2);
    }

    /**
     * Initialize required models.
     *
     * @param array $names
     */
    private function initModels(array $names): void
    {
        foreach ($names as $name) {
            $className = '\\FacturaScripts\\Dinamic\\Model\\' . $name;
            new $className();
        }
    }

    /**
     * Loads the default accounting plan. If there is one.
     *
     * @param string $codpais
     *
     * @return void
     */
    private function loadDefaultAccountingPlan(string $codpais): void
    {
        // ¿Hay un plan contable para ese país?
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // ¿La base de datos es de 2017 o anterior?
        if ($this->dataBase->tableExists('co_cuentas')) {
            return;
        }

        // ¿Ya existe el plan contable?
        $cuenta = new Cuenta();
        if ($cuenta->count() > 0) {
            return;
        }

        foreach (Ejercicio::all() as $exercise) {
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
            return;
        }
    }

    /**
     * Set default AppSettings based on codpais
     *
     * @param string $codpais
     */
    private function preSetAppSettings(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            $defaultValues = json_decode($fileContent, true) ?? [];
            foreach ($defaultValues as $group => $values) {
                foreach ($values as $key => $value) {
                    Tools::settingsSet($group, $key, $value);
                }
            }
        }

        Tools::settingsSet('default', 'codpais', $codpais);
        Tools::settingsSet('default', 'homepage', 'Root');
        Tools::settingsSave();
    }

    /**
     * Save company default address.
     *
     * @param string $codpais
     */
    private function saveAddress(string $codpais): void
    {
        $this->empresa->apartado = $this->request->input('apartado', '');
        $this->empresa->cifnif = $this->request->input('cifnif', '');
        $this->empresa->ciudad = $this->request->input('ciudad', '');
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $this->request->input('codpostal', '');
        $this->empresa->direccion = $this->request->input('direccion', '');
        $this->empresa->nombre = $this->request->input('empresa', '');
        $this->empresa->nombrecorto = Tools::textBreak($this->empresa->nombre, 32);
        $this->empresa->personafisica = (bool)$this->request->input('personafisica', '0');
        $this->empresa->provincia = $this->request->input('provincia', '');
        $this->empresa->telefono1 = $this->request->input('telefono1', '');
        $this->empresa->telefono2 = $this->request->input('telefono2', '');
        $this->empresa->tipoidfiscal = $this->request->input('tipoidfiscal', '');
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        }
        $this->empresa->save();

        // assigns warehouse?
        $where = [
            Where::eq('idempresa', $this->empresa->idempresa),
            Where::orIsNull('idempresa')
        ];
        foreach (Almacen::all($where) as $almacen) {
            $this->setWarehouse($almacen, $codpais);
            return;
        }

        // no assigned warehouse? Create a new one
        $almacen = new Almacen();
        $this->setWarehouse($almacen, $codpais);
    }

    private function saveEmail(string $email): bool
    {
        if (empty($this->empresa->email)) {
            $this->empresa->email = $email;
        }

        if (empty($this->user->email)) {
            $this->user->email = $email;
        }

        return $this->empresa->save() && $this->user->save();
    }

    /**
     * Save the new password if data is admin admin
     *
     * @return bool Returns true if success, otherwise return false.
     */
    private function saveNewPassword(string $pass): bool
    {
        $this->user->newPassword = $pass;
        $this->user->newPassword2 = $this->request->input('repassword', '');
        return $this->user->save();
    }

    private function saveStep1(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $codpais = $this->request->input('codpais', $this->empresa->codpais);
        $this->preSetAppSettings($codpais);

        $this->initModels(['AttachedFile', 'Diario', 'EstadoDocumento', 'FormaPago',
            'Impuesto', 'Retencion', 'Serie', 'Provincia']);
        $this->saveAddress($codpais);

        if (false === $this->saveLogo()) {
            return;
        }

        // change password
        $pass = $this->request->input('password', '');
        if ('' !== $pass && false === $this->saveNewPassword($pass)) {
            return;
        }

        // change email
        $email = $this->request->input('email', '');
        if ('' !== $email && false === $this->saveEmail($email)) {
            return;
        }

        // change template
        $this->setTemplate('Wizard-2');
    }

    private function saveStep2(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $this->empresa->regimeniva = $this->request->input('regimeniva');
        $this->empresa->save();

        $codimpuesto = $this->request->input('codimpuesto');
        Tools::settingsSet('default', 'codimpuesto', empty($codimpuesto) ? null : $codimpuesto);

        $codpago = $this->request->input('codpago');
        Tools::settingsSet('default', 'codpago', empty($codpago) ? null : $codpago);

        Tools::settingsSet('default', 'ventasinstock', (bool)$this->request->input('ventasinstock', '0'));
        Tools::settingsSet('default', 'site_url', Tools::siteUrl());
        Tools::settingsSave();

        $this->saveInvoiceStartNumber();
        $this->saveBankAccount();

        if ($this->request->input('defaultplan', '0')) {
            $this->loadDefaultAccountingPlan($this->empresa->codpais);
        }

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->redirect($this->url() . '?action=step3', 2);
    }

    protected function saveStep3(): void
    {
        // load all models
        $modelNames = [];
        $modelsFolder = Tools::folder('Dinamic', 'Model');
        foreach (Tools::folderScan($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        if (false === $this->dataBase->tableExists('fs_users')) {
            // avoid this step in 2017 installations
            $this->initModels($modelNames);
        }

        // load controllers
        Plugins::deploy(true, true);

        // obtenemos el rol de empleados, y lo asignamos como rol predeterminado
        $role = new Role();
        if ($role->load('employee')) {
            Tools::settingsSet('default', 'codrole', $role->codrole);
            Tools::settingsSave();
        }

        // change user homepage
        $this->user->homepage = $this->dataBase->tableExists('fs_users') ? 'AdminPlugins' : static::NEW_DEFAULT_PAGE;
        $this->user->save();

        // change template and redirect
        $this->setTemplate('Wizard-3');
        $this->finalRedirect();
    }

    private function saveBankAccount(): void
    {
        $iban = $this->request->input('iban', '');
        $bankName = $this->request->input('bank_name', '');
        if (empty($iban) && empty($bankName)) {
            return;
        }

        $paymentMethod = $this->getTransferPaymentMethod();
        if (false === $paymentMethod->exists()) {
            return;
        }

        $account = new CuentaBanco();
        if (!empty($paymentMethod->codcuentabanco)) {
            $account->load($paymentMethod->codcuentabanco);
        }

        $account->descripcion = empty($bankName) ? $this->empresa->nombrecorto : $bankName;
        $account->iban = $iban;
        $account->idempresa = $this->empresa->idempresa;
        if (false === $account->save()) {
            return;
        }

        $paymentMethod->codcuentabanco = $account->codcuenta;
        $paymentMethod->idempresa = $this->empresa->idempresa;
        $paymentMethod->save();
    }

    private function saveLogo(): bool
    {
        $uploadFile = $this->request->file('logo');
        if (empty($uploadFile)) {
            return true;
        }

        if (false === $uploadFile->isValid()) {
            Tools::log()->error($uploadFile->getErrorMessage());
            return false;
        }

        if (false === in_array($uploadFile->getClientMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            Tools::log()->error('not-valid-image');
            return false;
        }

        $attachedFile = $this->uploadLogoFile($uploadFile);
        if (empty($attachedFile->idfile)) {
            Tools::log()->error('file-not-found', ['%fileName%' => $uploadFile->getClientOriginalName()]);
            return false;
        }

        $this->empresa->idlogo = $attachedFile->idfile;
        return $this->empresa->save();
    }

    private function saveInvoiceStartNumber(): void
    {
        $startNumber = (int)$this->request->input('invoice_start_number', '1');
        if ($startNumber < 2) {
            return;
        }

        $exerciseCode = $this->getCompanyExerciseCode();
        if (empty($exerciseCode)) {
            return;
        }

        // buscamos las secuencias de FacturaCliente para actualizar el número de inicio
        $secuencia = new SecuenciaDocumento();
        $where = [
            Where::eq('codejercicio', $exerciseCode),
            Where::eq('codserie', 'A'),
            Where::eq('tipodoc', 'FacturaCliente'),
            Where::eq('idempresa', $this->empresa->idempresa),
        ];
        $found = false;
        foreach ($secuencia->all($where) as $sec) {
            $found = true;
            $sec->inicio = $startNumber;
            $sec->numero = $startNumber;
            $sec->patron = 'F{EJE}{SERIE}{NUM}';
            $sec->save();
        }
        if ($found) {
            return;
        }

        // si no existe la secuencia, la creamos
        $secuencia->codejercicio = $exerciseCode;
        $secuencia->codserie = 'A';
        $secuencia->idempresa = $this->empresa->idempresa;
        $secuencia->inicio = $startNumber;
        $secuencia->numero = $startNumber;
        $secuencia->patron = 'F{EJE}{SERIE}{NUM}';
        $secuencia->tipodoc = 'FacturaCliente';
        $secuencia->usarhuecos = true;
        $secuencia->save();
    }

    private function setWarehouse(Almacen $almacen, string $codpais): void
    {
        $almacen->ciudad = $this->empresa->ciudad;
        $almacen->codpais = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
        Tools::settingsSave();
    }

    private function uploadLogoFile(UploadedFile $uploadFile): AttachedFile
    {
        // exclude php files
        if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
            return new AttachedFile();
        }

        $destiny = FS_FOLDER . '/MyFiles/';
        $destinyName = $uploadFile->getClientOriginalName();
        if (file_exists($destiny . $destinyName)) {
            $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
        }

        if (false === $uploadFile->move($destiny, $destinyName)) {
            return new AttachedFile();
        }

        $file = new AttachedFile();
        $file->path = $destinyName;
        return $file->save() ? $file : new AttachedFile();
    }

    private function getTransferPaymentMethod(): FormaPago
    {
        $paymentMethod = new FormaPago();
        if ($paymentMethod->load('TRANS')) {
            return $paymentMethod;
        }

        $paymentMethod->codpago = 'TRANS';
        $paymentMethod->descripcion = 'Transferencia bancaria';
        $paymentMethod->idempresa = $this->empresa->idempresa;
        $paymentMethod->plazovencimiento = 1;
        $paymentMethod->tipovencimiento = 'months';
        $paymentMethod->save();
        return $paymentMethod;
    }

    private function getCompanyExerciseCode(): string
    {
        foreach ($this->empresa->getExercises() as $exercise) {
            return (string)$exercise->codejercicio;
        }

        return '';
    }
}
