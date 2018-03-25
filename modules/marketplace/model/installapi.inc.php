<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Marketplace\Model;

use RS\File\Tools;
use RS\Module\AbstractModel\BaseModel;
use RS\Module\Item;
use RS\Router\Manager;

/**
 * Класс содержит методы, необходимые для установки дополнений
 */
class InstallApi extends BaseModel
{
    private $addonArchiveApi;


    public function __construct()
    {
        $this->addonArchiveApi = new AddonArchiveApi();
    }


    public function installAddon($archive_file_path)
    {
        $addon_type = $this->addonArchiveApi->getAddonType($archive_file_path);
        if(!$addon_type){
            foreach($this->addonArchiveApi->getErrors() as $error) $this->addError($error);
            return false;
        }

        if($addon_type === 'module')
            return $this->installModule($archive_file_path);
        else if($addon_type === 'template')
            return $this->installTemplate($archive_file_path);
        else if($addon_type === 'solution')
            return $this->installSolution($archive_file_path);

        return false;
    }

    public function installModule($archive_file_path)
    {
        $zip = new \ZipArchive();
        $zip->open($archive_file_path);
        $first_folder_stat = $zip->statIndex(0);
        $mod_name = basename($first_folder_stat['name']);

        $is_module_exists_before = \RS\Module\Manager::staticModuleExists($mod_name);

        $ok = $zip->extractTo(\Setup::$PATH.\Setup::$MODULE_FOLDER);
        if(!$ok){
            return $this->addError(t('Не удалось распаковать архив в папку модулей'));
        }

        $mod_item = new Item($mod_name);
        $ok_or_errors = $mod_item->install();
        if($ok_or_errors !== true){
            foreach($ok_or_errors as $error) $this->addError($error);

            if ($mod_name && !$is_module_exists_before) {
                //Если модуля до этого не было в системе, то удаляем его,
                //так как он не смог корректно установиться
                \RS\File\Tools::deleteFolder(\Setup::$PATH.\Setup::$MODULE_FOLDER.'/'.$mod_name);
            }

            return false;
        }

        // Проверяем на на отсутсвие фатальных ошибок после установки модуля
        if(!$this->checkSiteForFatalErrors()){
            // Отключаем модуль
            $config = \RS\Config\Loader::byModule($mod_name);
            $config['enabled'] = 0;
            $config->update();

            return $this->addError(t('После установки модуля на сайте возникла фатальная ошибка. Модуль отключен'));
        }

        return true;
    }


    public function installTemplate($archive_file_path)
    {
        // Получение иформации о теме оформления
        $info = $this->addonArchiveApi->fetchTemplateInfo($archive_file_path);
        if($this->addonArchiveApi->hasError())
        {
            $errors = $this->addonArchiveApi->getErrors();
            foreach($errors as $error) $this->addError($error);
            return false;
        }

        // Распаковка
        $zip = new \ZipArchive();
        $zip->open($archive_file_path);
        $ok = $zip->extractTo(\Setup::$SM_TEMPLATE_PATH);
        if(!$ok){
            return $this->addError(t('Не удалось распаковать архив в папку тем оформления'));
        }

        //Устанавливаем первую цветовую вариацию, если она есть
        $shade = $info['shades'] ? "({$info['shades'][0]['id']})" : '';
        $theme_name = $info['name'].$shade;

        $item = new \RS\Theme\Item($theme_name);
        $ok_or_error = $item->setThisTheme();
        if($ok_or_error !== true){
            return $this->addError($ok_or_error);
        }
        return true;
    }


    public function installSolution($archive_file_path)
    {
        // Получение данных о имени модуля и формирование имени темы оформления
        $info = $this->addonArchiveApi->fetchSolutionInfo($archive_file_path);


        // * Установка модуля (из архива решения) *

        $module_installed = $this->installModule($archive_file_path);
        if(!$module_installed){
            return false;
        }

        // Удаление папки solutiontemplate из папки модуля
        Tools::deleteFolder(\Setup::$PATH.\Setup::$MODULE_FOLDER.DS.strtolower($info['name']).DS.'solutiontemplate');

        // * Установка темы (из архива решения) *

        $theme_name = strtolower($info['name']).'_theme';
        $zip = new \ZipArchive();
        $zip->open($archive_file_path);

        // Распаковываем архив во временную папку
        $temp_dir = \Setup::$TMP_DIR.'/'.md5(microtime());
        $ok = $zip->extractTo($temp_dir);
        if(!$ok) return $this->addError(t('Не удалось распаковать тему из архива решения'));

        // Перемещение темы из временной папки в /templates/<$theme_name>
        $to = \Setup::$SM_TEMPLATE_PATH.DS.$theme_name;
        Tools::deleteFolder($to);
        rename($temp_dir.DS.strtolower($info['name']).'/solutiontemplate', $to);
        Tools::deleteFolder($temp_dir);

        // Устовка темы как текущей
        $item = new \RS\Theme\Item($theme_name);
        $ok_or_error = $item->setThisTheme();
        if($ok_or_error !== true){
            return $this->addError($ok_or_error);
        }
        return true;

    }

    /**
     * Проверка сайта на базовую работоспособность - на предмет отсуствия фатальных ошибок
     * Делает запрос на локальный URL, ожидая определенного ответа.
     * Если ответ не соотвествует ожиданиям, предполагается что произошла ошибка
     * @return bool
     */
    private function checkSiteForFatalErrors()
    {
        $host = \RS\Http\Request::commonInstance()->getSelfAbsoluteHost();
        $url = $host.Manager::obj()->getUrl('marketplace-front-checkforfatal', array());

        $context = array(
            'ssl' => array(
                'allow_self_signed'=>true,
                'verify_peer'=>false,
                'verify_peer_name' => false
            ),
        );        
        
        $json = file_get_contents($url, false, stream_context_create($context));
        $res = json_decode($json);
        return isset($res->success) && $res->success;
    }
}