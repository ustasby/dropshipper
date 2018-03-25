<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module;

/**
* Класс - информация об одном модуле. + действия с модулем
*/
class Item
{
    protected static
        $mod_css = '/modules/%MODULE%/view/css/',
        $mod_js = '/modules/%MODULE%/view/js/',
        $mod_img = '/modules/%MODULE%/view/img/',
        $mod_tpl = '/modules/%MODULE%/view/';
    
    protected
        $mod_name,
        $config,
        $changelog_filename = 'changelog',
        $module_folder = MODULE_FOLDER, //Берется из \Setup
        $config_folder = CONFIG_FOLDER,
        $config_class = CONFIG_CLASS,
        $handlers_class = HANDLERS_CLASS,
        $my_handlers_class = MY_HANDLERS_CLASS,
        
        $install_class = 'install',
        $uninstall_class = 'uninstall',
        
        $config_prefix;
        
    function __construct($mod_name)
    {
        if (is_object($mod_name)) {
            $mod_name = self::nameByObject($mod_name);
        }
        $this->mod_name = $mod_name;
        $this->config_prefix = str_replace('/','\\',trim($this->config_folder,'/'));
    }
    
    /**
    * Возвращает объект - конфигурационный файл модуля
    * 
    * @param integer $site_id - ID сайта. Если null, то конфиг загружается для текущего сайта
    * @return \RS\Orm\ConfigObject
    */
    function getConfig($site_id = null)
    {
        return \RS\Config\Loader::byModule($this->mod_name, $site_id);
    }
    
    /**
    * Помечает модуль как установленный
    * 
    * @return Item
    */
    function markAsInstalled()
    {
        $config = $this->getConfig();
        
        if ($config) {
            $config['installed'] = true;
            $config->update();
        }
        return $this;
    }
    
    /**
    * Устанавливает время последнего обновения модуля
    * 
    * @return Item
    */
    function setUpdateTime($time = null)
    {
        if (\Setup::$INSTALLED) {
            if (!$time) $time = time();
            $config = $this->getConfig();
            if ($config) {
                $config['lastupdate'] = $time;
                $config->update();
            }
        }
        return $this;
    }
    
    /**
    * Возврщает массив со списком клиентских контроллеров блоков
    */
    function getBlockControllers()
    {
        $ret = array();
        return $this->findBlockControllers(\Setup::$PATH.$this->module_folder.'/', $this->mod_name.'/controller/');
    }
    
    /**
    * Возвращает true, если модуль существует, иначе - false
    * 
    * @return bool
    */
    function exists()
    {
        $config_prefix = str_replace('/','\\',trim($this->config_folder,'/'));
        $class_name = $this->mod_name.'\\'.$config_prefix.'\\'.$this->config_class;
        return class_exists( $class_name );
    }
    
    /**
    * Рекурсивно проходит по директориям в посках блочных контроллеров
    * 
    * @param string $dir
    * @return array
    */
    protected function findBlockControllers($base, $path)
    {
        $ret = array();
        $dir = $base.$path;
        if (file_exists($dir)) {
            $dh = opendir( $dir );
            while (($file = readdir($dh)) !== false) {
                $file = strtolower($file);
                if ($file == '.' || $file == '..' || $file == '.svn') continue;
                if ($path == $this->mod_name.'/controller/' && $file == 'admin') continue; //Исключаем контроллеры админки
                $type = filetype($dir . $file);
                if ($type == 'dir') {
                    $ret = array_merge($ret, $this->findBlockControllers($base, $path.$file.'/'));
                }
                if ($type == 'file') {
                    if (preg_match('/(.*?)\.(.*)/', $file, $matches)) {
                        if ($matches[2] == \Setup::$CLASS_EXT) {
                            $class_name = str_replace('/', '\\', $path).$matches[1];
                            if (class_exists($class_name) && is_subclass_of($class_name, '\RS\Controller\Block')) {
                                $ret[] = array(
                                    'class' => $class_name,
                                    'short_class' => $matches[1],
                                    'info' => $class_name::getInfo()
                                );
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }
        return $ret;
    }
    
    
    /**
    *  Возвращает у модуля объект класса uninstall
    * @return UninstallInterface
    */
    function getUninstallInstance()
    {
        $class_name = '\\'.$this->mod_name.'\\'.$this->config_prefix.'\\'.$this->uninstall_class;
        $exists = class_exists($class_name);
        return $exists ? new $class_name() : new AbstractUninstall($this->mod_name);
    }
    
    /**
    * Возвращает у модуля объект класса install
    * 
    * @return InstallInterface
    */
    function getInstallInstance()
    {
        $class_name = '\\'.$this->mod_name.'\\'.$this->config_prefix.'\\'.$this->install_class;
        $exists = class_exists($class_name);
        return $exists ? new $class_name() : new AbstractInstall($this->mod_name);
    }
    
    /**
    * Возвращает у модуля объект класса handlers
    * 
    * @return \RS\Event\HandlerAbstract
    */
    function getHandlersInstance()
    {
        $class_name = '\\'.$this->mod_name.'\\'.$this->config_prefix.'\\'.$this->handlers_class;
        $exists = class_exists($class_name);
        if ($exists) return new $class_name(); else return false;
    }
    
    /**
    * Устанавливает или обновляет модуль
    * 
    * @param array $options - опции для установки. Массив с названиями методов класса Install модуля
    * @return bool(true) | array - Возвращает true в случае успеха, иначе массив с ошибками
    */
    function install($options = array())
    {
        $installer = $this->getInstallInstance();
        $errors = array();
        if ($installer) {
            $config = $this->getConfig();
            $method = ($config['installed']) ? 'update' : 'install';

            //Проверка зависимостей перед первой установкой
            if ($method == 'install' && ($dependency_check_result = $this->checkDependency()) !== true) {
                //Ошибка проверки зависимостей
                return $dependency_check_result;
            }

            if ($installer->$method()) {
                \RS\Cache\Cleaner::obj()->clean(\RS\Cache\Cleaner::CACHE_TYPE_FULL);

                $this->markAsInstalled()->setUpdateTime();
                foreach($options as $option => $value) { //Модуль установлен, запускаем опции
                    if ($value) {
                        if (is_callable(array($installer, $option)) && !$installer->$option()) {
                            $errors[] = t('Во время установки опции "%0" произошла ошибка', array($option));  //Если опция не установлена
                        }
                    }
                }

                /**
                * Event: module.install.ИМЯ_МОДУЛЯ
                * paramtype \RS\Module\Item
                */
                $event_result = \RS\Event\Manager::fire('module.install.'.$this->mod_name, $this);
                $errors = array_merge($errors, $event_result->getEvent()->getErrors());
            } else {
                return (array)$installer->getErrors();
            }
        } else {
            $this->markAsInstalled()->setUpdateTime();
        }
        
        
        return count($errors) ? $errors : true;
    }


    /**
     * Проверяет зависимости модуля от ядра (core_version) и зависимость от других модулей
     *
     * @return bool(true) | array Возвращает true, в случае успеха, иначе массив с ошибками
     */
    function checkDependency()
    {
        $config = $this->getConfig();
        $errors = array();

        if ($config->core_version && !\RS\Helper\Tools::checkVersionRange(\Setup::$VERSION, $config->core_version)) {
            $errors[] = t('Версия ядра не соответствует требуемой для модуля: %0', $config->core_version);
        }

        if ($config->dependfrom) {
            $modules = explode(',', $config->dependfrom);
            $non_exists_modules = array();
            foreach($modules as $module) {
                if (!\RS\Module\Manager::staticModuleExists($module)) {
                    $non_exists_modules[] = $module;
                }
            }

            if ($non_exists_modules) {
                $errors[] = t('Модуль зависит от следующих модулей: %0', array(implode(', ', $non_exists_modules)));
            }
        }

        return $errors ? $errors : true;
    }
    
    /**
    * Удаляет модуль
    * @return bool(true) | array - Возвращает true, в случае успеха, иначе возвращает false
    */
    function uninstall()
    {
        $config = $this->getConfig();
        
        if ($config['is_system']) {
            return array(t('Невозможно удалить системный модуль'));
        }
        $uninstall = $this->getUninstallInstance();
        if ($uninstall === false) {
            return array(t('Не найден класс деинсталяции'));
        }
        
        /**
        * Event: module.beforeUninstall.ИМЯ_МОДУЛЯ
        * paramtype \RS\Module\Item
        */
        $event_result = \RS\Event\Manager::fire('module.beforeuninstall.'.$this->mod_name, $this);
        if ($event_result->getEvent()->isStopped()) {
            return $event_result->getEvent()->getErrors();
        }
        
        if (!$uninstall->uninstall()) {
            return (array)$uninstall->getErrors();
        } else {
            if (($err = $this->removeModuleFromDisk()) !== true) {
                return (array)t("Не удалось удалить модуль с диска");
            }
            //Удаляем конфиг модуля из базы
            \RS\Orm\Request::make()->delete()->from(new ModuleConfig())->where(array(
                'module' => $this->mod_name
            ))->exec();
            
            \RS\Cache\Cleaner::obj()->clean(\RS\Cache\Cleaner::CACHE_TYPE_FULL);
        }

        /**
        * Event: module.afterUninstall.ИМЯ_МОДУЛЯ
        * paramtype \RS\Module\Item
        */
        \RS\Event\Manager::fire('module.afteruninstall.'.$this->mod_name, $this);
        if ($event_result->getEvent()->isStopped()) {
            return $event_result->getEvent()->getErrors();
        }        
        
        return true;
    }
    
    
    /**
    * Удаляет модуль с диска
    * 
    * @param string $module
    * @return bool
    */
    protected function removeModuleFromDisk()
    {
        $module_folder = \Setup::$PATH.$this->module_folder.'/'.$this->mod_name;
        return \RS\File\Tools::deleteFolder($module_folder, true);
    }    
    
    /**
    * Возвращает сокращенной имя модуля
    * 
    * @param object | string $object - экземпляр класса модуля или имя класса модуля
    * @param mixed $default - значение, в случае, если модуль не будет распознан.
    */
    public static function nameByObject($object, $default = 'main')
    {
        if (is_object($object)) {
            $object = get_class($object);
        }
        if (preg_match('/^(.*?)\\\/i', $object, $match) && strtolower($match[1]) != 'rs') {
            $mod_name = strtolower($match[1]);
        } else {
            $mod_name = $default;
        }
        return $mod_name;
    }
    
    /**
    * Возвращает массив относительных путей к css, js, img, tpl
    * 
    * @param object $object объект любого модуля
    */
    public static function getResourceFolders($module_name)
    {
        if (is_object($module_name)) {
            $module_name = self::nameByObject($module_name);
        }
        $folders = array(
            'mod_css' => strtolower(str_replace('%MODULE%', $module_name, \Setup::$FOLDER.self::$mod_css)),
            'mod_js' => strtolower(str_replace('%MODULE%', $module_name, \Setup::$FOLDER.self::$mod_js)),
            'mod_img' => strtolower(str_replace('%MODULE%', $module_name, \Setup::$FOLDER.self::$mod_img)),
            'mod_tpl' => strtolower(str_replace('%MODULE%', $module_name, \Setup::$FOLDER.self::$mod_tpl)),
        );
        return $folders;
    }
    
    /**
    * Возвращает объект класса $class_name, если класс соответствует всем требованиям блочных контроллеров
    * иначе false
    * 
    * @param string $class_name
    */
    public static function getBlockControllerInstance($class_name)
    {
        if (class_exists($class_name) && is_subclass_of($class_name, '\RS\Controller\Block')) {
            return new $class_name;
        }
        return false;
    }
    
    /**
    * Возвращает папку модуля (он же краткий символьный идентификатор)
    * @return string
    */
    public function getName()
    {
        return $this->mod_name;
    }
    
    /**
    * Возвращает корневую папку модуля
    * @return string
    */
    public function getFolder()
    {
        return \Setup::$PATH.$this->module_folder.'/'.$this->getName();
    }
    
    /**
    * Инициализирует обработчики событий модуля
    */
    public function initHandlers()
    {
        $handlers_classname = $this->mod_name.'\\'.$this->config_prefix.'\\'.$this->handlers_class;
        $my_handlers_classname = $this->mod_name.'\\'.$this->config_prefix.'\\'.$this->my_handlers_class;
        
        if (class_exists($my_handlers_classname)) {
            $handlers = new $my_handlers_classname();
        } 
        elseif (class_exists($handlers_classname)) {
            $handlers = new $handlers_classname();
        }
        
        if (isset($handlers)) {
            if ($handlers instanceof \RS\Event\HandlerAbstract) {
                $handlers->init();
            } else {
                throw new Exception(t("Класс %0 должен быть наследником RS\\Event\\HandlerAbstract", array($handlers_classname)));
            }
            return true;
        }
        return false;
    }
    
    /**
    * Возвращает список действий, которые можно произвести с модулем
    * 
    * @return array of array
    * 
    * Пример стуктуры результирующего массива:
    * array(
    *   array(
    *       'url' => 'http://....',
    *       'title' => 'Название'
    *       'confirm' => 'Текст'
    *   )
    * )
    */
    public function getTools()
    {
        $router = \RS\Router\Manager::obj();
        $tools = array(
            array(
                'url' => $router->getAdminUrl('AjaxReInstall', array('module' => $this->mod_name), 'modcontrol-control'),
                'title' => t('Переустановить модуль'),
                'description' => t('Обновляет структуру базы данных, заново создает пункты меню, устанавливает модуль'),
                'confirm' => t('Вы действительно хотите переустановить модуль?')
            )
        );
        
        if (($install = $this->getInstallInstance()) !== false) {
            if ($install->canInsertDemoData()) {
                $tools[] = array(
                    'url' => $router->getAdminUrl('AjaxInstallDemoData', array('module' => $this->mod_name), 'modcontrol-control'),
                    'title' => t('Установить демо-данные'),
                    'description' => t('Добавляет демонстационные данные к данным, которые сейчас присуствуют на сайте'),
                    'confirm' => t('Вы действительно хотите добавить демонстрационные данные для данного модуля?')
                );
            }
        }
        
        if ($this->issetChangelog()) {
            $tools[] = array(
                    'url' => $router->getAdminUrl('AjaxShowChangelog', array('module' => $this->mod_name), 'modcontrol-control'),
                    'title' => t('История изменений'),
                    'description' => t('Показывает список изменений, произошедших в каждой версии модуля'),
                    'class' => 'crud-add'
                );
        }
        
        $extra_tools = $this->getConfig()->tools;
        if (!empty($extra_tools)) {
            $tools = array_merge($tools, $extra_tools);
        }
        
        return $tools;
    }
    
    /**
    * Возвращает true, если у модуля есть changelog файл
    * 
    * @return bool
    */
    function issetChangelog()
    {
        $default_changelog_filename = \Setup::$PATH.$this->module_folder.'/'.$this->mod_name.'/'.$this->config_folder.'/'.$this->changelog_filename.'.txt';
        return file_exists($default_changelog_filename);
    }
    
    /**
    * Возвращает содержимое файла changelog на языке $lang или на текущем языке
    * 
    * @param string $lang - двухсимвольный идентификатор языка
    * @return string
    */
    function getChangelog($lang = null)
    {
        if ($lang === null) {
            $lang = \RS\Language\Core::getCurrentLang();
        }
        
        $base_name = \Setup::$PATH.$this->module_folder.'/'.$this->mod_name.'/'.$this->config_folder.'/'.$this->changelog_filename;
        $default_changelog = $base_name.'.txt';
        $lang_changelog = $base_name.'_'.$lang.'.txt';
        
        if (file_exists($lang_changelog)) {
            $changelog = $lang_changelog;
        } elseif (file_exists($default_changelog)) {
            $changelog = $default_changelog;
        } else {
            return false;
        }
        
        return file_get_contents($changelog);
    }

    /**
    * Возвращает список ORM объектов, принадлежащих данному модулю
    * 
    * @return array of \RS\Orm\AbstractObject
    */
    function getOrmObjects()
    {
        return $this->findOrmObjects();
    }
    
    /**
    * Возвращает список ORM объектов, находящихся в указанной папке
    * 
    * @param mixed $base - путь к корневой папке orm объектов
    * @param mixed $subfolder - путь к объектам, отностельно корневой папки
    * @param mixed $prefix - текст, приписываемый вначале к имени класса
    */
    protected function findOrmObjects($base = null, $subfolder = '', $prefix = null)
    {
        if ($base === null) {
            $base = $this->getFolder().'/model/orm/';
            $prefix = '\\'.$this->mod_name.'\model\orm\\';
        }
        
        $result = array();
        $dir = $base.$subfolder;
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    if (is_dir($dir.$file)) {
                        $result = array_merge($result, $this->findOrmObjects($base, $subfolder.$file.'/', $prefix));
                    } else {
                        if (strpos($file, '.'.\Setup::$CLASS_EXT) !== false && strpos($file, '.'.\Setup::$CUSTOM_CLASS_EXT) === false) {
                            $classname =  $prefix. str_replace('/', '\\', $subfolder.str_replace('.'.\Setup::$CLASS_EXT, '', $file));
                            if (is_subclass_of($classname, '\RS\Orm\AbstractObject')) {
                                $result[] = new $classname();
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
        return $result;
    }    
    
    /**
    * Возвращает параметры модуля по-умолчанию из XML-файла модуля.
    * В случае ошибки, возвращает пустой массив
    * 
    * @param string $file - путь к файлу module.xml
    * @return array
    */
    public static function parseModuleXml($file)
    {
        $result = array();        
        try {
            $config = @new \SimpleXMLElement($file, null, true);
            foreach($config->defaultValues[0] as $key => $value) {
                $result_value = (string)$value;
                $is_multilanguage = $value['multilanguage'];
                
                if ($value['type'] == 'array') {
                    $result_value = array();
                    $i = 0;
                    foreach($value->value as $array_value) {
                        $array_key = $array_value['key'] ? (string)$array_value['key'] : $i++;
                        $array_value = $is_multilanguage ? (string)$array_value : t( (string)$array_value );
                        $result_value[$array_key] = $array_value;
                    }
                } elseif ($is_multilanguage) {
                    $result_value = t($result_value);
                }
                $result[$key] = $result_value;
            }
        } catch (\Exception $e) {}
        
        return $result;
    }
}

