<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Model\AttachedFileRelation;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Description of DocFilesTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait DocFilesTrait
{
    /** @var array ids compartidos, cacheados por modelo y código */
    private $sharedFileIds = [];

    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fa-brands fa-html5');

    abstract protected function checkOwnerData($model): bool;

    private function addFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken() || false === $this->checkFileOwnerData()) {
            return true;
        }

        // el formulario permite subir archivos nuevos y vincular otros de la biblioteca a la vez
        $uploadFiles = array_filter($this->request->files->getArray('new-files'));
        $idFiles = $this->request->request->getArray('idfiles');
        if (empty($uploadFiles) && empty($idFiles)) {
            Tools::log()->warning('no-data');
            return true;
        }

        if (false === $this->uploadNewFiles($uploadFiles) || false === $this->linkLibraryFiles($idFiles)) {
            return true;
        }

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Indica si el usuario puede eliminar el archivo, no solo desvincularlo. Eliminar
     * lo saca de la biblioteca, que es un almacén compartido, así que exigimos permiso
     * de borrado sobre ella. Cada usuario sí puede eliminar lo que él mismo subió.
     * Es pública porque la vista decide con ella si muestra el botón.
     */
    public function canDeleteFiles(AttachedFileRelation $fileRelation): bool
    {
        return $this->user->can('ListAttachedFile', 'delete')
            || (false === empty($fileRelation->nick) && $fileRelation->nick === $this->user->nick);
    }

    /**
     * Indica si el usuario puede elegir archivos de la biblioteca. Vincular un archivo
     * existente da acceso a su contenido, así que exigimos el mismo permiso que para
     * consultar la biblioteca.
     */
    private function canUseFileLibrary(): bool
    {
        return $this->user->can('ListAttachedFile');
    }

    /**
     * Comprueba que el usuario puede modificar los adjuntos del registro actual
     * cuando solo tiene acceso a sus propios datos.
     */
    private function checkFileOwnerData(): bool
    {
        if (empty($this->permissions->onlyOwnerData)) {
            return true;
        }

        // en este punto el registro principal puede no estar cargado todavía
        $model = $this->getModel();
        $code = $this->request->query('code');
        if (empty($model->id()) && false === empty($code)) {
            $modelClass = get_class($model);
            $model = new $modelClass();
            $model->load($code);
        }

        if (false === $this->checkOwnerData($model)) {
            Tools::log()->warning('access-denied');
            return false;
        }

        return true;
    }

    /**
     * Comprueba que la relación pertenece al registro actual.
     */
    private function checkFileRelation(AttachedFileRelation $fileRelation): bool
    {
        $code = $this->request->query('code');
        if (empty($code) || $fileRelation->model !== $this->getModelClassName()) {
            return false;
        }

        $modelId = empty($fileRelation->modelcode) ? $fileRelation->modelid : $fileRelation->modelcode;
        return $modelId == $code;
    }

    protected function createViewDocFiles(string $viewName = 'docfiles', string $template = 'Tab/DocFiles'): void
    {
        $this->addHtmlView($viewName, $template, 'AttachedFileRelation', 'files', 'fa-solid fa-paperclip');
    }

    private function deleteFileAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return true;
        } elseif (false === $this->validateFileActionToken() || false === $this->checkFileOwnerData()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->input('id');
        if (false === $fileRelation->load($id)) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if (false === $this->checkFileRelation($fileRelation)) {
            Tools::log()->warning('not-allowed-delete');
            return true;
        }

        // eliminar saca el archivo de la biblioteca, no solo de este registro
        if (false === $this->canDeleteFiles($fileRelation)) {
            Tools::log()->warning('not-allowed-delete-library-files');
            return true;
        }

        // el archivo puede estar adjunto en más registros, incluso en registros que este
        // usuario no puede ver. En ese caso no se puede eliminar: hay que desvincularlo.
        $file = $fileRelation->getFile();
        $others = $file ? $file->countRelations() - 1 : 0;
        if ($others > 0) {
            Tools::log()->warning('file-in-use-cannot-delete', ['%count%' => $others]);
            return true;
        }

        $fileRelation->delete();
        if ($file) {
            $file->delete();
        }

        Tools::log()->notice('record-deleted-correctly');

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        return true;
    }

    private function editFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken() || false === $this->checkFileOwnerData()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->input('id');
        if (false === $fileRelation->load($id)) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if (false === $this->checkFileRelation($fileRelation)) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $fileRelation->observations = $this->request->input('observations');
        $this->pipeFalse('editFileAction', $fileRelation, $this->request);

        if (false === $fileRelation->save()) {
            Tools::log()->error('record-save-error');
            return true;
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Devuelve los ids de los archivos que ya están vinculados al registro actual.
     *
     * @param string|null $modelCode
     * @return array
     */
    private function getLinkedFileIds($modelCode): array
    {
        $where = [Where::eq('model', $this->getModelClassName())];
        $where[] = is_numeric($modelCode) ?
            Where::eq('modelid|modelcode', $modelCode) :
            Where::eq('modelcode', $modelCode);

        // solamente necesitamos la columna, no los modelos
        $ids = [];
        foreach (DbQuery::table(AttachedFileRelation::tableName())->where($where)->select('idfile')->get() as $row) {
            $ids[] = (int)$row['idfile'];
        }

        return $ids;
    }

    /**
     * Devuelve los ids de los archivos del registro actual que además están adjuntos
     * en otros registros. La vista lo necesita para todas las tarjetas, así que se
     * resuelve con una sola consulta.
     */
    public function getSharedFileIds(): array
    {
        // la caché va por modelo y código, no por controlador, para que un controlador
        // con más de una pestaña de archivos no reutilice la respuesta de otra
        $code = $this->request->query('code');
        $cacheKey = $this->getModelClassName() . '|' . $code;
        if (isset($this->sharedFileIds[$cacheKey])) {
            return $this->sharedFileIds[$cacheKey];
        }

        $this->sharedFileIds[$cacheKey] = [];

        $idFiles = $this->getLinkedFileIds($code);
        if (empty($idFiles)) {
            return $this->sharedFileIds[$cacheKey];
        }

        $counts = DbQuery::table(AttachedFileRelation::tableName())
            ->whereIn('idfile', $idFiles)
            ->countArray('idfile', 'idfile');
        foreach ($counts as $idFile => $count) {
            if ($count > 1) {
                $this->sharedFileIds[$cacheKey][] = (int)$idFile;
            }
        }

        return $this->sharedFileIds[$cacheKey];
    }

    /**
     * Vincula al registro actual archivos que ya están en la biblioteca.
     *
     * @param array $idFiles
     * @return bool false si hay que interrumpir la acción
     */
    private function linkLibraryFiles(array $idFiles): bool
    {
        if (empty($idFiles)) {
            return true;
        }

        if (false === $this->canUseFileLibrary()) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }

        // no recortamos la lista: si llegan de más, el usuario tiene que saber que no se ha guardado
        $max = $this->maxLibraryFilesPerRequest();
        if (count($idFiles) > $max) {
            Tools::log()->warning('max-files-per-request', ['%max%' => $max]);
            return false;
        }

        $linked = $this->getLinkedFileIds($this->request->query('code'));
        foreach ($idFiles as $idFile) {
            // los ids llegan del formulario, así que no nos fiamos del formato
            if (false === is_numeric($idFile)) {
                continue;
            }

            $file = new AttachedFile();
            if (false === $file->load($idFile)) {
                Tools::log()->warning('record-not-found');
                continue;
            }

            // si ya está vinculado a este registro, no lo duplicamos
            if (in_array($file->idfile, $linked)) {
                continue;
            }

            if (false === $this->saveFileRelation($file->idfile, 'linkFilesAction')) {
                return false;
            }

            $linked[] = $file->idfile;
        }

        return true;
    }

    /**
     * @param BaseView $view
     * @param string $model
     * @param string $modelid
     */
    private function loadDataDocFiles($view, $model, $modelid): void
    {
        $where = [Where::eq('model', $model)];
        $where[] = is_numeric($modelid) ?
            Where::eq('modelid|modelcode', $modelid) :
            Where::eq('modelcode', $modelid);
        $view->loadData('', $where, ['orden' => 'ASC', 'creationdate' => 'DESC']);
    }

    /**
     * Número de archivos de la biblioteca que devuelve cada página de la búsqueda.
     * Es pública porque la vista la necesita para pedir la página siguiente.
     */
    public function libraryPageSize(): int
    {
        return 8;
    }

    /**
     * Número máximo de archivos de la biblioteca que se pueden vincular en una sola
     * petición. Los plugins pueden sobrescribirlo si necesitan más.
     * Es pública porque la vista también muestra el límite al usuario.
     */
    public function maxLibraryFilesPerRequest(): int
    {
        return 20;
    }

    /**
     * Número máximo de archivos que PHP acepta en una sola petición. Si llegan más,
     * PHP descarta los que sobran sin avisar, así que el formulario no deja guardar
     * cuando se pasa de aquí.
     * Es pública porque la vista la necesita para avisar al usuario.
     */
    public function maxUploadFilesPerRequest(): int
    {
        $max = (int)ini_get('max_file_uploads');

        return $max > 0 ? $max : 20;
    }

    /**
     * Crea la relación entre un archivo y el registro actual.
     *
     * @param int $idFile
     * @param string $hook nombre de la extensión que reciben los plugins
     * @return bool
     */
    private function saveFileRelation(int $idFile, string $hook): bool
    {
        $fileRelation = new AttachedFileRelation();
        $fileRelation->idfile = $idFile;
        $fileRelation->model = $this->getModelClassName();
        $fileRelation->modelcode = $this->request->query('code');
        $fileRelation->modelid = (int)$fileRelation->modelcode;
        $fileRelation->nick = $this->user->nick;
        $fileRelation->observations = $this->request->input('observations');
        $this->pipeFalse($hook, $fileRelation, $this->request);

        if (false === $fileRelation->save()) {
            Tools::log()->error('fail-relation');
            return false;
        }

        return true;
    }

    /**
     * Devuelve, en formato json, los archivos de la biblioteca, marcando los que
     * ya están vinculados al registro actual.
     *
     * Es protegida porque la resuelve PanelController::execPreviousAction(), así los
     * controladores que usan este trait no tienen que enrutar la acción.
     */
    protected function searchLibraryAction(): bool
    {
        $this->setTemplate(false);

        if (false === $this->permissions->allowUpdate) {
            $this->response->json(['error' => Tools::trans('not-allowed-modify')]);
            return false;
        }

        // el token caduca a las pocas horas, así que la respuesta pide recargar la página
        if (false === $this->validateFileToken()) {
            $this->response->json(['error' => Tools::trans('session-expired-reload'), 'reload' => true]);
            return false;
        }

        if (false === $this->checkFileOwnerData()) {
            $this->response->json(['error' => Tools::trans('access-denied')]);
            return false;
        }

        if (false === $this->canUseFileLibrary()) {
            // sin permiso no devolvemos la lista, para no filtrar los nombres de los archivos
            $this->response->json([]);
            return false;
        }

        $where = [];
        $query = $this->request->input('query', '');
        if (false === empty($query)) {
            // xlike busca cada palabra por separado, así el orden de las palabras da igual
            $where[] = Where::xlike('filename', $query);
        }

        // el idfile desempata: sin él, dos archivos de la misma hora pueden
        // repetirse o perderse al pasar de página
        $orderBy = $this->request->input('sort') === 'date-asc' ?
            ['date' => 'ASC', 'hour' => 'ASC', 'idfile' => 'ASC'] :
            ['date' => 'DESC', 'hour' => 'DESC', 'idfile' => 'DESC'];

        $linked = $this->getLinkedFileIds($this->request->query('code'));

        // devolvemos solo la página pedida. pedimos un archivo de más para saber
        // si hay siguiente sin tener que contarlos todos.
        $limit = $this->libraryPageSize();
        $offset = max(0, (int)$this->request->input('offset', 0));

        $files = [];
        $more = false;
        foreach (AttachedFile::all($where, $orderBy, $offset, $limit + 1) as $file) {
            if (count($files) >= $limit) {
                $more = true;
                break;
            }

            $files[] = [
                'date' => $file->date . ' ' . $file->hour,
                'filename' => $file->filename,
                'idfile' => $file->idfile,
                'image' => $file->isImage() ? $file->url('download') : '',
                // los ya vinculados se muestran, pero no se pueden volver a elegir
                'linked' => in_array($file->idfile, $linked),
                'size' => Tools::bytes($file->size),
                'url' => $file->url(),
            ];
        }

        $this->response->json(['files' => $files, 'more' => $more, 'offset' => $offset]);
        return false;
    }

    private function sortFilesAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->checkFileOwnerData()) {
            return true;
        }

        $idsOrdenadas = $this->request->request->getArray('orden');
        if (false === empty($idsOrdenadas)) {
            $orden = 1;
            foreach ($idsOrdenadas as $id_archivo) {
                // solo reordenamos las relaciones del registro actual
                $archivo = new AttachedFileRelation();
                if (false === $archivo->load($id_archivo) || false === $this->checkFileRelation($archivo)) {
                    continue;
                }

                $archivo->orden = $orden;
                if ($archivo->save()) {
                    $orden++;
                }
            }
        }

        $this->setTemplate(false);

        $this->response->json(['status' => 'ok']);

        return false;
    }

    private function unlinkFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken() || false === $this->checkFileOwnerData()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->input('id');
        if (false === $fileRelation->load($id)) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if (false === $this->checkFileRelation($fileRelation)) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $fileRelation->delete();

        Tools::log()->notice('record-updated-correctly');

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        return true;
    }

    /**
     * Actualiza el número de adjuntos del documento.
     */
    protected function updateNumDocs(): void
    {
        $model = $this->getModel();
        $model->numdocs = AttachedFileRelation::count([
            Where::eq('model', $this->getModelClassName()),
            Where::eq('modelid', $this->request->queryOrInput('code'))
        ]);

        if (false === $model->save()) {
            Tools::log()->error('record-save-error');
        }
    }

    /**
     * Mueve a MyFiles los archivos subidos y los vincula al registro actual.
     *
     * @param array $uploadFiles
     * @return bool false si hay que interrumpir la acción
     */
    private function uploadNewFiles(array $uploadFiles): bool
    {
        foreach ($uploadFiles as $uploadFile) {
            if (false === $uploadFile->isValid()) {
                Tools::log()->error($uploadFile->getErrorMessage());
                continue;
            }

            // check if the file already exists
            $destiny = FS_FOLDER . '/MyFiles/';
            $destinyName = $uploadFile->getClientOriginalName();
            if (file_exists($destiny . $destinyName)) {
                $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
            }

            // move the file to the MyFiles folder
            if (false === $uploadFile->move($destiny, $destinyName)) {
                Tools::log()->error(Tools::trans('file-not-found'));
                continue;
            }

            $newFile = new AttachedFile();
            $newFile->path = $destinyName;
            if (false === $newFile->save()) {
                Tools::log()->error('fail');
                return false;
            }

            if (false === $this->saveFileRelation($newFile->idfile, 'addFileAction')) {
                return false;
            }
        }

        return true;
    }

    private function validateFileActionToken(): bool
    {
        // valid request?
        if (false === $this->validateFileToken()) {
            return false;
        }

        // duplicated request?
        $token = $this->request->input('multireqtoken', '');
        if ($this->multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }

    /**
     * Comprueba la firma del token, pero sin marcarlo como usado. La búsqueda en
     * la biblioteca se repite varias veces en la misma página, así que no puede
     * consumir el token del formulario.
     */
    private function validateFileToken(): bool
    {
        $token = $this->request->input('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-request');
            return false;
        }

        return true;
    }
}
