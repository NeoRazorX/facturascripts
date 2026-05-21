<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\Role;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;

/**
 * Endpoint REST para completar la instalación de FacturaScripts sin interfaz web.
 * Lógica independiente de Wizard, adaptada para recibir datos vía JSON.
 * El estado se persiste en MyFiles/wizard_progress.json.
 *
 * ── Requisito previo: config.php ──
 *   El Installer debe haberse ejecutado y config.php debe contener al menos:
 *
 *     define('FS_DB_TYPE',      'mysql');          // motor: mysql | postgresql
 *     define('FS_DB_HOST',      'localhost');       // host de la base de datos
 *     define('FS_DB_PORT',      3306);              // puerto de la base de datos
 *     define('FS_DB_NAME',      'facturascripts');  // nombre de la base de datos
 *     define('FS_DB_USER',      'root');            // usuario de la base de datos
 *     define('FS_DB_PASS',      '');                // contraseña de la base de datos
 *     define('FS_INITIAL_USER', 'admin');           // nick del administrador
 *     define('FS_INITIAL_PASS', 'admin');           // contraseña del administrador
 *     define('FS_API_KEY',      'clave-secreta');   // habilita la API y sirve como token
 *
 *   Sin FS_API_KEY la API está desactivada y este endpoint devuelve 503.
 *   FS_INITIAL_USER/FS_INITIAL_PASS determinan las credenciales del admin
 *   que el Installer inserta en la BD al crear la tabla fs_users.
 *
 * Autenticación: X-Auth-Token: <FS_API_KEY> en todas las peticiones.
 *
 * ── POST ──
 *   Envía todo el formulario de una vez. Configura empresa y parámetros fiscales
 *   (pasos 1 y 2). Devuelve {current_step: "step3"} cuando está listo para el GET.
 *
 *   Obligatorios:
 *     empresa       string    Nombre completo de la empresa
 *     email         string    Email de contacto (obligatorio si no hay uno guardado)
 *
 *   Opcionales:
 *     password      string    Nueva contraseña para el usuario administrador
 *     repassword    string    Confirmación de la nueva contraseña (si se omite, se usa password)
 *     codpais       string    Código ISO 3166-1 alpha-3 del país. Ej: "ESP", "MEX", "ARG"
 *     cifnif        string    CIF, NIF o RFC de la empresa
 *     tipoidfiscal  string    Tipo de identificador fiscal según el país
 *     personafisica bool      true si la empresa es una persona física
 *     direccion     string    Dirección fiscal
 *     codpostal     string    Código postal
 *     ciudad        string    Ciudad
 *     provincia     string    Provincia o estado
 *     apartado      string    Apartado de correos
 *     telefono1     string    Teléfono principal
 *     telefono2     string    Teléfono secundario
 *     regimeniva    string    Régimen de IVA. Ej: "General", "Exento", "Recargo"
 *     codimpuesto   string    Código del impuesto por defecto. Ej: "IVA21", "IVA10"
 *     codpago       string    Código de la forma de pago por defecto. Ej: "CONT", "TRANS"
 *     ventasinstock bool      Permitir ventas sin stock. Default: false
 *     defaultplan   bool      Importar el plan contable del país configurado. Default: false
 *     invoice_start_number int  Número de inicio para las facturas. Default: 1
 *     iban          string    IBAN de la cuenta bancaria principal
 *     bank_name     string    Nombre descriptivo del banco
 *
 * ── GET ──
 *   Devuelve el estado actual de la instalación.
 *   Si el POST ya completó los pasos 1 y 2 (current_step = "step3"), el GET
 *   ejecuta el despliegue final (modelos, plugins, rol por defecto) y devuelve
 *   {status: "installed"} cuando termina.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class ApiSetupWizard extends ApiController
{
    const NEW_DEFAULT_PAGE = 'Dashboard';

    /** @var Empresa */
    private $empresa;

    /** @var User|false */
    private $user = false;

    public function __construct(string $className, string $url = '')
    {
        // Dependencias de la ApiAccess (necesario para que este funcione)
        new AttachedFile();
        new Diario();
        new Page();
        new Empresa();
        new Serie();
        new User();
        new ApiKey();
        new ApiAccess();

        parent::__construct($className, $url);
    }

    protected function runResource(): void
    {
        $this->empresa = $this->loadDefaultEmpresa();

        if ($this->checkIsInstalled()) {
            $this->response
                ->setHttpCode(Response::HTTP_FORBIDDEN)
                ->json(['status' => 'installed', 'message' => Tools::trans('wizard-already-completed')]);
            return;
        }

        if (($this->readWizardProgress()['installAgent'] ?? '') === 'wizard') {
            $this->response
                ->setHttpCode(Response::HTTP_CONFLICT)
                ->json([
                    'status'     => 'error',
                    'message'    => 'installation-managed-by-wizard',
                    'wizard_url' => Tools::siteUrl() . '/Wizard',
                ]);
            return;
        }

        $method = $this->request->method();

        if ($method === 'GET') {
            $this->handleGet();
        } elseif ($method === 'POST') {
            $this->handlePost();
        } else {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json(['status' => 'error', 'message' => 'method-not-allowed']);
        }
    }

    /** Devuelve el estado actual. Si current_step === 'step3', ejecuta el despliegue final. */
    private function handleGet(): void
    {
        $progress = $this->readWizardProgress();
        $currentStep = $progress['current_step'] ?? '';

        if ($currentStep !== 'step3') {
            $this->response->json([
                'status'       => 'installing',
                'current_step' => empty($currentStep) ? 'step1' : $currentStep,
            ]);
            return;
        }

        $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
        if (empty($users)) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json(['status' => 'error', 'message' => 'admin-user-not-found']);
            return;
        }
        $this->user = $users[0];

        $this->saveStep3();

        if ($this->isStepCompleted('step3')) {
            $this->response->json(['status' => 'installed', 'message' => Tools::trans('record-updated-correctly')]);
        } else {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json(['status' => 'error', 'step' => 'step3', 'message' => Tools::trans('record-save-error')]);
        }
    }

    /** Recibe todo el formulario de golpe y ejecuta step1 + step2 secuencialmente. */
    private function handlePost(): void
    {
        $body = $this->request->json() ?? [];

        $progress = $this->readWizardProgress();
        if (empty($progress['installAgent'])) {
            $progress['installAgent'] = 'apiWizard';
            file_put_contents(self::wizardStateFile(), json_encode($progress, JSON_PRETTY_PRINT));
        }

        if (!$this->isStepCompleted('step1')) {
            $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
            if (empty($users)) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'message' => 'admin-user-not-found']);
                return;
            }
            $this->user = $users[0];

            $errors = $this->validateStep1($body);
            if (!empty($errors)) {
                $this->response
                    ->setHttpCode(Response::HTTP_BAD_REQUEST)
                    ->json(['status' => 'error', 'step' => 'step1', 'errors' => $errors]);
                return;
            }

            $this->saveStep1($body);

            if (!$this->isStepCompleted('step1')) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'step' => 'step1', 'message' => Tools::trans('record-save-error')]);
                return;
            }
        }

        if (!$this->isStepCompleted('step2')) {
            $this->saveStep2($body);

            if (!$this->isStepCompleted('step2')) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json(['status' => 'error', 'step' => 'step2', 'message' => Tools::trans('record-save-error')]);
                return;
            }
        }

        $this->response->json([
            'status'       => 'installing',
            'current_step' => 'step3',
            'message'      => Tools::trans('record-updated-correctly'),
        ]);
    }

    private function loadDefaultEmpresa(): Empresa
    {
        $empresa = new Empresa();

        $idempresa = Tools::settings('default', 'idempresa');
        if (!empty($idempresa) && $empresa->load($idempresa)) {
            return $empresa;
        }

        $list = $empresa->all([], [], 0, 1);
        return empty($list) ? new Empresa() : $list[0];
    }

    // =========================================================================
    // Lógica de instalación (inspirada en Wizard, independiente de él)
    // =========================================================================

    private function saveStep1(array $data): void
    {
        $codpais = $data['codpais'] ?? $this->empresa->codpais;
        $this->preSetAppSettings($codpais);

        $this->initModels(['AttachedFile', 'Diario', 'EstadoDocumento', 'FormaPago',
            'Impuesto', 'Retencion', 'Serie', 'Provincia']);
        $this->saveAddress($codpais, $data);

        $pass = $data['password'] ?? '';
        if ('' !== $pass && false === $this->saveNewPassword($pass, $data['repassword'] ?? $pass)) {
            return;
        }

        $email = $data['email'] ?? '';
        if ('' !== $email && false === $this->saveEmail($email)) {
            return;
        }

        $this->completeWizardStep('step1');
    }

    private function saveStep2(array $data): void
    {
        $this->empresa->regimeniva = $data['regimeniva'] ?? null;
        $this->empresa->save();

        $codimpuesto = $data['codimpuesto'] ?? null;
        Tools::settingsSet('default', 'codimpuesto', empty($codimpuesto) ? null : $codimpuesto);

        $codpago = $data['codpago'] ?? null;
        Tools::settingsSet('default', 'codpago', empty($codpago) ? null : $codpago);

        Tools::settingsSet('default', 'ventasinstock', (bool)($data['ventasinstock'] ?? '0'));
        Tools::settingsSet('default', 'site_url', Tools::siteUrl());
        Tools::settingsSave();

        if ($data['defaultplan'] ?? '0') {
            $this->loadDefaultAccountingPlan($this->empresa->codpais);
        }

        $this->saveInvoiceStartNumber((int)($data['invoice_start_number'] ?? '1'));
        $this->saveBankAccount($data['iban'] ?? '', $data['bank_name'] ?? '');

        $this->completeWizardStep('step2');
    }

    private function saveStep3(): void
    {
        $modelNames = [];
        $modelsFolder = Tools::folder('Dinamic', 'Model');
        foreach (Tools::folderScan($modelsFolder) as $fileName) {
            if ('.php' === substr($fileName, -4)) {
                $modelNames[] = substr($fileName, 0, -4);
            }
        }
        if (false === $this->db()->tableExists('fs_users')) {
            $this->initModels($modelNames);
        }

        Plugins::deploy(true, true);

        $role = new Role();
        if ($role->load('employee')) {
            Tools::settingsSet('default', 'codrole', $role->codrole);
            Tools::settingsSave();
        }

        $this->user->homepage = $this->db()->tableExists('fs_users') ? 'AdminPlugins' : self::NEW_DEFAULT_PAGE;
        $this->user->save();

        $this->completeWizardStep('step3');
    }

    private function preSetAppSettings(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/default.json';
        if (file_exists($filePath)) {
            $defaultValues = json_decode(file_get_contents($filePath), true) ?? [];
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

    private function initModels(array $names): void
    {
        foreach ($names as $name) {
            $className = '\\FacturaScripts\\Dinamic\\Model\\' . $name;
            new $className();
        }
    }

    private function saveAddress(string $codpais, array $data): void
    {
        $this->empresa->apartado = $data['apartado'] ?? '';
        $this->empresa->cifnif = $data['cifnif'] ?? '';
        $this->empresa->ciudad = $data['ciudad'] ?? '';
        $this->empresa->codpais = $codpais;
        $this->empresa->codpostal = $data['codpostal'] ?? '';
        $this->empresa->direccion = $data['direccion'] ?? '';
        $this->empresa->nombre = $data['empresa'] ?? '';
        $this->empresa->nombrecorto = Tools::textBreak($this->empresa->nombre, 32);
        $this->empresa->personafisica = (bool)($data['personafisica'] ?? '0');
        $this->empresa->provincia = $data['provincia'] ?? '';
        $this->empresa->telefono1 = $data['telefono1'] ?? '';
        $this->empresa->telefono2 = $data['telefono2'] ?? '';
        $this->empresa->tipoidfiscal = $data['tipoidfiscal'] ?? '';
        if (empty($this->empresa->tipoidfiscal)) {
            $this->empresa->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        }
        $this->empresa->save();

        $where = [
            Where::eq('idempresa', $this->empresa->idempresa),
            Where::orIsNull('idempresa'),
        ];
        foreach (Almacen::all($where) as $almacen) {
            $this->setWarehouse($almacen, $codpais);
            return;
        }

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

    private function saveNewPassword(string $pass, string $repass): bool
    {
        $this->user->newPassword  = $pass;
        $this->user->newPassword2 = $repass;
        return $this->user->save();
    }

    private function loadDefaultAccountingPlan(string $codpais): void
    {
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (!file_exists($filePath)) {
            return;
        }

        if ($this->db()->tableExists('co_cuentas')) {
            return;
        }

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

    private function saveInvoiceStartNumber(int $startNumber): void
    {
        if ($startNumber < 2) {
            return;
        }

        $exerciseCode = $this->getCompanyExerciseCode();
        if (empty($exerciseCode)) {
            return;
        }

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
            $sec->inicio  = $startNumber;
            $sec->numero  = $startNumber;
            $sec->patron  = 'F{EJE}{SERIE}{NUM}';
            $sec->save();
        }
        if ($found) {
            return;
        }

        $secuencia->codejercicio = $exerciseCode;
        $secuencia->codserie     = 'A';
        $secuencia->idempresa    = $this->empresa->idempresa;
        $secuencia->inicio       = $startNumber;
        $secuencia->numero       = $startNumber;
        $secuencia->patron       = 'F{EJE}{SERIE}{NUM}';
        $secuencia->tipodoc      = 'FacturaCliente';
        $secuencia->usarhuecos   = true;
        $secuencia->save();
    }

    private function saveBankAccount(string $iban, string $bankName): void
    {
        if (empty($iban) && empty($bankName)) {
            return;
        }

        $paymentMethod = $this->getTransferPaymentMethod();
        if (!$paymentMethod->exists()) {
            return;
        }

        $account = new CuentaBanco();
        if (!empty($paymentMethod->codcuentabanco)) {
            $account->load($paymentMethod->codcuentabanco);
        }

        $account->descripcion = empty($bankName) ? $this->empresa->nombrecorto : $bankName;
        $account->iban        = $iban;
        $account->idempresa   = $this->empresa->idempresa;
        if (!$account->save()) {
            return;
        }

        $paymentMethod->codcuentabanco = $account->codcuenta;
        $paymentMethod->idempresa      = $this->empresa->idempresa;
        $paymentMethod->save();

        if (empty($account->codsubcuenta)) {
            $exerciseCode = $this->getCompanyExerciseCode();
            if (!empty($exerciseCode)) {
                $account->createSubcuenta($exerciseCode);
            }
        }
    }

    private function setWarehouse(Almacen $almacen, string $codpais): void
    {
        $almacen->ciudad    = $this->empresa->ciudad;
        $almacen->codpais   = $codpais;
        $almacen->codpostal = $this->empresa->codpostal;
        $almacen->direccion = $this->empresa->direccion;
        $almacen->idempresa = $this->empresa->idempresa;
        $almacen->nombre    = $this->empresa->nombrecorto;
        $almacen->provincia = $this->empresa->provincia;
        $almacen->save();

        Tools::settingsSet('default', 'codalmacen', $almacen->codalmacen);
        Tools::settingsSet('default', 'idempresa', $this->empresa->idempresa);
        Tools::settingsSave();
    }

    private function getTransferPaymentMethod(): FormaPago
    {
        $paymentMethod = new FormaPago();
        if ($paymentMethod->load('TRANS')) {
            return $paymentMethod;
        }

        $paymentMethod->codpago          = 'TRANS';
        $paymentMethod->descripcion      = 'Transferencia bancaria';
        $paymentMethod->idempresa        = $this->empresa->idempresa;
        $paymentMethod->plazovencimiento = 1;
        $paymentMethod->tipovencimiento  = 'months';
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

    // =========================================================================
    // Progreso del wizard (estado en MyFiles/wizard_progress.json)
    // =========================================================================

    private static function wizardStateFile(): string
    {
        return Tools::folder('MyFiles') . DIRECTORY_SEPARATOR . 'wizard_progress.json';
    }

    private function readWizardProgress(): array
    {
        $file = self::wizardStateFile();
        return file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    }

    private function isStepCompleted(string $step): bool
    {
        return ($this->readWizardProgress()[$step]['completed'] ?? false) === true;
    }

    private function completeWizardStep(string $step): void
    {
        $next = ['step1' => 'step2', 'step2' => 'step3'];
        $data = $this->readWizardProgress();
        $data[$step] = [
            'completed'    => true,
            'completed_at' => date('c'),
            'version'      => \FacturaScripts\Core\Kernel::version(),
        ];
        $data['current_step'] = $next[$step] ?? null;
        file_put_contents(self::wizardStateFile(), json_encode($data, JSON_PRETTY_PRINT));
    }

    private function checkIsInstalled(): bool
    {
        if ($this->isStepCompleted('step3')) {
            return true;
        }
        $users = (new User())->all([Where::eq('admin', true)], [], 0, 1);
        return !empty($users) && $users[0]->homepage !== 'Wizard';
    }

    // =========================================================================
    // Validaciones
    // =========================================================================

    /** Valida los campos obligatorios del paso 1. Devuelve array de mensajes de error. */
    private function validateStep1(array $body): array
    {
        $errors = [];

        if (empty($body['empresa'])) {
            $errors[] = Tools::trans('field-can-not-be-null', ['%fieldName%' => 'empresa']);
        }

        if (empty($this->empresa->email) && empty($this->user->email) && empty($body['email'])) {
            $errors[] = Tools::trans('field-can-not-be-null', ['%fieldName%' => 'email']);
        }

        return $errors;
    }
}
