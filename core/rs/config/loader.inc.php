<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Config;

/**
* Возвращает instance необходимого класса конфигурации. Создает его один раз
*/
class Loader
{
	public static 
        $instance_list = array();
	
    /**
    * Возвращает объект конфигурации модуля или false в случае отсутствия класса.
    * 
    * @param string $classname Имя класса конфигурации модуля
    * @param integer | null $site_id ID сайта
    * @return RS\Orm\ConfigObject | false;
    */
	public static function get($classname, $site_id = null)
	{	
        $site_id = $site_id ?: \RS\Site\Manager::getSiteId();
        $classname = strtolower($classname);
		if (!isset(self::$instance_list[$classname.$site_id])) {
            if (!class_exists($classname)) {
                return false;
            }
			$config = new $classname();
			$config->loadFromCache($site_id);
            self::$instance_list[$classname.$site_id] = $config;
		}
		return self::$instance_list[$classname.$site_id];
	}
    
    /**
    * Извлекает из названия класса название модуля и возвращает объект - конфигурационный файл этого модуля.
    * 
    * @param mixed $classname - экземпляр класса модуля, имя класса модуля (контроллер, модель, все что угодно) или имя папки модуля
    * @param integer | null $site_id ID сайта
    * @return \RS\Orm\ConfigObject
    */
    public static function byModule($classname, $site_id = null)
    {        
        if (($is_object = is_object($classname)) || strpos($classname, '\\') !== false) {
            if ($is_object) {
                $classname = get_class($classname);
            }
            if (!preg_match('/^(.*?)\\\/i', $classname, $match)) {
                throw new \RS\Exception(t('Неверный аргумент. Ожидался объект любого класса модуля.'));
            }
            $classname = $match[1];
        }
        
        $config_class_name = str_replace('/','\\', $classname.\Setup::$CONFIG_FOLDER.'\\'.\Setup::$CONFIG_CLASS);
        return self::get($config_class_name, $site_id);
    }
    
    /**
    * Возвращает системный конфигурационный файл
    * @return \RS\Config\Cms
    */
    public static function getSystemConfig()
    {
        return self::get('\RS\Config\Cms');
    }
    
    /**
    * Возвращает объект конфигурации текущего сайта
    * 
    * @param integer | null $site_id - ID сайта, если null, то текущий
    * @return \Site\Model\Orm\Config
    */
    public static function getSiteConfig($site_id = null)
    {
        if ($site_id) {
            $site_config = new \Site\Model\Orm\Config();
            $site_config->load($site_id);
            $site_config['site_id'] = $site_id;
            return $site_config;
        } else {
            return \Site\Model\Orm\Config::getActualInstance();
        }
    }
    
    /**
    * Сбрасывает внутреннее хранилище конфигурационных инстансов.
    * При следующем вызове инстанс будет создан заново
    * @return void
    */
    public static function resetInstances()
    {
        self::$instance_list = array();
    }

}

