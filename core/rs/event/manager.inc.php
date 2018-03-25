<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Event;
use RS\Cache\Manager as CacheManager,
    RS\Module\Manager as ModuleManager,
    RS\Module\Item as ModuleItem;

/**
* Класс обработки событий. Во время инициализации системы событий (выполняется в \Setup::init),
* происходит попытка запуска метода \ИмяМодуля\Config\MyHandlers::init 
* или \ИмяМодуля\Config\Handlers::init у каждого модуля с целью собрать подписчиков на обработку событий. 
* Позже, при генерации события, подписчики в порядке очереди получают управление для обработки события
*/
class Manager 
{
    const
        SYSTEM_MODULE = 'main',
        USER_CALLBACK_CLASS = '\Config\MyHandlers',
        DEFAULT_CALLBACK_CLASS = '\Config\Handlers';
    
    protected static 
        $initialized = false,
        $closure = 0,
        $base = array();
    
    protected function __construct() {}
    
    /**
    * Инициализирует класс событий.
    */
    public static function init()
    {
        $is_admin_zone = \RS\Router\Manager::obj()->isAdminZone();
        if (\RS\Site\Manager::getSite() !== false) {
            self::$base = CacheManager::obj()
                            ->expire(0)
                            ->request(array(__CLASS__, 'loadBase'), $is_admin_zone, \RS\Site\Manager::getSiteId());
        
            //Сбрасываем описание класса пользователя
            \Users\Model\Orm\User::destroyClass();
        }
    }
    
    /**
    * Обходит модули и загружает базу слушателей событий.
    */
    public static function loadBase()
    {
        $module_manager = new ModuleManager();
        $active_modules = $module_manager->getActiveList();
        
        foreach($active_modules as $module) {
            $module->initHandlers();
        }
        
        self::sortByPriority();
        self::$initialized = true;
        return self::$base;
    }
    
    /**
    * Возвращает true, если все подписчики подписались на события
    * 
    * @return bool
    */
    public static function isInitialized()
    {
        return self::$initialized;
    }
    
    /**
    * Нормализует название события
    * 
    * @param string $event - Имя события
    * @return string
    */
    protected static function prepare($event)
    {   
        return strtolower($event);
    }
    
    /**
    * Устанавливает слушателя на событие
    * 
    * @param string | object $module Название модуля. Например: MSystem
    * @param string $event Событие
    * @param callback $callback - callback для вызова. 
    * Вместо имени метода можно указывать null, в таком случае оно будет сгенерировано из названия события
    * @return bool
    */
    public static function bind($event, $callback, $priority = 10)
    {
        $event = self::prepare($event);
        
        if ($callback instanceof Closure) {
            $callback_class = 'closure_'.self::$closure++;
            $callback_method = '';
        }
        if (is_array($callback)) {
            if (!isset($callback[1])) {
                //Если не передано имя метода, то генерируем его из названия события
                $callback[1] = str_replace(array('.','-'), '', $event);
            }
            
            if ($callback[0] instanceof HandlerAbstract) {
                $module = ModuleItem::nameByObject($callback[0]);
                $custom_handlers = array($module.self::USER_CALLBACK_CLASS, $callback[1]);
                if (is_callable($custom_handlers)) { //Попытаемся сперва вызвать обработчик MyHandlers
                    $callback = $custom_handlers;
                }
            }
            
            $callback_class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            $callback_method = $callback[1];
        }

        self::$base[$event][$callback_class.'.'.$callback_method] 
            = array('callback' => array($callback_class, $callback_method), 'priority' => $priority );
            
        return true;
    }
    
    /**
    * Сортирует обработчики событий по приоритетам
    * 
    * @return void
    */
    public static function sortByPriority()
    {
        foreach(self::$base as &$one) {
            uasort($one, array(__CLASS__, 'cmpItems'));
        }
    }
    
    /**
    * Сравнивает два приоритета и возвращает, который из них больше
    * 
    * @param array $a
    * @param array $b
    * @return integer
    */
    public static function cmpItems($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return 0;
        }
        return ($a['priority'] > $b['priority']) ? -1 : 1;
    }
    
    
    /**
    * Удаляет слушателя у события
    * 
    * @param string $event Событие
    * @param string $callback_class Имя класса обработчика события
    * @param string $callback_method Имя статического метода класса обработчика события
    */
    public static function unbind($event = null, $callback_class = null, $callback_method = null)
    {
        $event = self::prepare($event);
        
        if ($callback_method && $callback_class) {
            unset(self::$base[$event][$callback_class.'.'.$callback_method]);
        } elseif ($event) {
            unset(self::$base[$event]);
        }
    }
    
    /**
    * Возвращает список слушателей события
    * 
    * @param string $event Событие
    * @return array
    */
    public static function getListeners($event = null)
    {
        if ($event === null) {
            return self::$base;
        }
        return isset(self::$base[$event]) ? self::$base[$event] : array();
    }    
    
    /**
    * Вызывает событие. Сообщает об этом слушателям, передает каждому слушателю результат выполнения предыдущего в виде параметров
    * 
    * @param string $event Имя события
    * @param mixed $params Параметры, которые будут переданы слушателям события в качестве аргументов.
    * @return Result
    */
    public static function fire($event, $params = null)
    {
        $event = self::prepare($event);

        $original_params = $params;
        $this_event = new Event($event);
        
        if (isset(self::$base[$event])) {
            $params_type = gettype($params);            
            foreach(self::$base[$event] as $event_params) {
                $callback = $event_params['callback'];
                if (is_callable($callback)) {
                    $new_params = call_user_func($callback, $params, $this_event);
                    if ($new_params !== null) {
                        $params = $new_params;
                        if (gettype($params) != $params_type) {
                            throw new Exception(t("Обработчик %0::%1. события %2 должен вернуть значение того же типа, что и 'params' (%3) или NULL", array($callback[0], $callback[1], $event, $params_type)));
                        }
                    }
                    if ($this_event->isStopped()) break;
                } else {
                    throw new Exception(t("Не найден обработчик '%0' события '%1'", array(implode('::', $callback), $event)));
                }
            }
        }
        
        return new Result($original_params, $params, $this_event);
    }
    
    /**
    * Возвращает true, если имеются подписчики на событие $event
    * 
    * @param string $event
    * @return bool
    */
    public static function issetHandlers($event)
    {
        return isset(self::$base[$event]);
    }
}
