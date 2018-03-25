<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model;


class Api extends \RS\Module\AbstractModel\EntityList
{
    static 
        $inst = null,
        $types;
    
    private 
        $basedir;       // Корневая папка модуля

    static public function getInstance()
    {
        if(self::$inst == null){
            self::$inst = new self();
        }
        return self::$inst;
    }

    function __construct()
    {
        parent::__construct(new \Export\Model\Orm\ExportProfile,
        array(
            'multisite' => true,
            'alias_field' => 'alias',
        ));
        
        $this->basedir = \Setup::$ROOT.\Setup::$STORAGE_DIR.DS.'export';
        if(!is_dir($this->basedir)){ 
            mkdir($this->basedir, \Setup::$CREATE_DIR_RIGHTS, true);
        }
    }
    
    
    /**
    * Получить экспортированные данные для данного профиля
    * Кеширует результат в файле
    * 
    * @param Orm\ExportProfile $profile
    * @return void
    */
    function printExportedData(Orm\ExportProfile $profile)
    {
        $cache_file = $profile->getCacheFilePath();
        // Если установлено "время жизни"
        if($profile->life_time > 0){
            $life_time_in_sec = $profile->life_time * 24 * 60 * 60;
            // Если время жизни еще не истекло
            if(file_exists($cache_file) && (time() < filemtime($cache_file) + $life_time_in_sec))
            {
                readfile($cache_file);
                return;
            } 
        }
        
        // Экспортируем данные в файл
        $profile->export();
        
        // Отправляем содержимое файла на вывод
        \RS\Application\Application::getInstance()->headers->cleanCookies();
        readfile($cache_file);
    }
    
    /**
    * Возвращает полный путь к файлу, содержащему экспортированные данные
    * 
    * @param Orm\ExportProfile $profile
    * @return string
    */
    function getCacheFilePath(Orm\ExportProfile $profile)
    {
        return $this->basedir.DS.'site'.$profile->site_id.'_'.$profile->class.'_'.$profile->id.'.cache';
    }
    
    /**
    * Возвращает объекты типов экспорта
    * 
    * @return array
    */
    function getTypes()
    {
        if (self::$types === null) {
            $event_result = \RS\Event\Manager::fire('export.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach($list as $type_object) {
                if (!($type_object instanceof ExportType\AbstractType)) {
                    throw new \Exception(t('Тип экспорта должен реализовать интерфейс \Export\Model\ExportType\AbstractType'));
                }
                self::$types[$type_object->getShortName()] = $type_object;
            }
        }
        
        return self::$types;
    }
    
    /**
    * Возвращает массив ключ => название типа 
    * 
    * @return array
    */
    static public function getTypesAssoc()
    {
        $_this = new self();
        $result = array();
        foreach($_this->getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }
    
    /**
    * Возвращает объект экспорта доставки по идентификатору
    * 
    * @param string $name
    */
    static public function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : null;
    }
}
