<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Router;

/**
* Менеджер маршрутов. Встречает запрос и передает управление подходящему маршруту модуля 
* или контроллеру.
*/
class Manager
{
    const
        ADMIN_ROUTE = 'main.admin';
        
    private
        $host,
        $uri,
        $admin_route,
        $is_admin_zone,
        $default_route,
        $enable_gzip = true;

    /**
     * @var Manager $instance
     */
    private static $instance;
    private static $routes = array();
        
    protected 
        $http_request;    
    /**
    * @var \RS\Router\RouteAbstract $currentRoute
    */
    private static $currentRoute;


    /**
     * Manager constructor.
     */
    protected function __construct() 
    {
        $this->http_request = \RS\Http\Request::commonInstance();
        $this->host = $this->http_request->server('HTTP_HOST');

        $this->default_route = new Route('default', array(
            '/{controller}/{Act}/', 
            '/{controller}/'
        ), null, t('По умолчанию'));

        //Создаем маршут административной панели
        $admin = \Setup::$ADMIN_SECTION;
        $this->admin_route = new Route(self::ADMIN_ROUTE, 
            array(
                "/{$admin}/{mod_controller}/",
                "/{$admin}/",
                "/{$admin}",
            ), array(
                'controller' => 'main-admin-index',
                'mod_controller' => 'main-widgets'
            ),
            t('Панель Администратора'),
            true
        );
        
        $this->admin_route->isAdmin(true);
        self::addRoute($this->admin_route, $this->admin_route->getId());
    }
    
    /**
    * Получает и формирует маршруты системы
    * 
    */
    public function initRoutes()
    {
        /**
        * Event: getRoute
        * Запрашивает все маршруты
        * Parameter: array of RS_Router_RouteAbstract
        */
        if (\Setup::$INSTALLED) {
            
            $event_result = \RS\Event\Manager::fire('getroute', array());
            $for_delete = array();
            foreach($event_result->getResult() as $route) {
                if ($route instanceof RouteAbstract) {
                    $this->addRoute($route);
                }
                if ($route instanceof DeleteRoute) {
                    $for_delete[] = $route->getRouteId();
                }
            }
            if (\Setup::$DEFAULT_ROUTE_ENABLE) {
                $this->addRoute($this->default_route); //В самый конец добавляем маршрут по-умолчанию
            }

            foreach($for_delete as $route_id) {
                $this->removeRoute($route_id);
            }
            
        } else {
            self::$routes = array(
                'install' => new \RS\Router\Route('install', '/install/', array(
                                'controller' => 'install-wizard'
                            ), t('Мастер установки'))
            );
        }
    }
    
    
    /**
    * Возвращает объект текущего класса
    * @return \RS\Router\Manager
    */
    public static function obj()
    {
        if ( !isset(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
    * Включает/выключает сжатие данных Gzip
    * Если не передан аргумент, то возвращает текущее значение
    * @param boolean | null $bool
    * @return boolean
    */
    public function gzipEnabled($bool = null)
    {
        if ($bool !== null) {
            $this->enable_gzip = $bool;
        }
        return $this->enable_gzip;
    }
    
    /**
    * Добавляет маршрут в общий список
    * 
    * @param \RS\Router\RouteAbstract $route маршрут
    * @param string $id внутреннее имя маршрута
    */
    public function addRoute(\RS\Router\RouteAbstract $route)
    {
        $id = $route->getId();
        if (isset(self::$routes[$id])) {
            unset(self::$routes[$id]);
        }
        self::$routes[$id] = $route;
    }
    
    /**
    * Удаляет route из списка по id
    * 
    * @param string $id - ID маршрута
    * @return void
    */
    public function removeRoute($id)
    {
        unset(self::$routes[$id]);
    }
    
    /**
    * Возвращает список маршрутов
    * 
    * @return array
    */
    public static function getRoutes()
    {
        return self::$routes;
    }
    
    /**
    * Возвращает маршрут по ID, вслучае, если маршрут не найден возврщается заглушка RouteUnknown
    * 
    * @param mixed $id
    * @return RouteAbstract
    */
    public static function getRoute($id)
    {
        return isset(self::$routes[$id]) ? self::$routes[$id] : new RouteUnknown($id);
    }
    
    /**
    * Возвращает построенный маршрутом URL с учетом параметров
    * В случае если маршрут не найден возвращает пустую строку. Генерирует warning
    * 
    * @param string | null $route_id Идентификатор маршрута. Если передан null, то используется текущий маршрут 
    * @param array $params - дополнительные параметры
    * @param bool $absolute - если true, то будет возвращен абсолютный путь
    * @param mixed $mask_key - ключ маски, которая должна быть использована для построения маршрута
    * 
    * @return string
    */
    public function getUrl($route_id, $params = array(), $absolute = false, $mask_key = null)
    {
        if ($route_id === null) {
            $route_id = $this->getCurrentRoute()->getId();
        }
        $route = $this->getRoute($route_id);
        if ($route instanceof RouteUnknown && !isset($params[RouteAbstract::CONTROLLER_PARAM])) {
            $route = $this->getRoute('default');
            $params[RouteAbstract::CONTROLLER_PARAM] = $route_id;
        }
        if ($route) {
            return $route->buildUrl($params, $absolute, $mask_key);
        }
        trigger_error("Unknown route $route_id"); //Вызываем notice, если маршрут не найден
        return '';
    }
    
    /**
    * Возвращает URL корня текущего сайта
    * 
    * @return string
    */
    public function getRootUrl($absolute = false)
    {
        return \RS\Site\Manager::getSite()->getRootUrl($absolute);
    }
    
    /**
    * Аналог getUrl за исключением того, что может оставлять незакодированными для URL значения ключей
    * Используется для генерации маски Url, в которую можно очень быстро подставлять значения
    * 
    * @param string|null $route_id - идентификатор текущего маршрута
    * @param array|null $params - дополнительные параметры. используйте запись ':(двоеточие)ИМЯ КЛЮЧА' => 'ЗНАЧЕНИЕ', чтобы указать, что данный ключ не следует кодировать
    * @param boolean $absolute - строить абсолютный адрес?
    *
    * @return string
    */
    public function getUrlPattern($route_id = null, $params = null, $absolute = true)
    {
        $search = array();
        $replace = array();
        foreach($params as $key => $value) {
            if ($key{0} == ':') {
                $search[] = urlencode($value);
                $replace[] = $value;
                unset($params[$key]);
                $params[urlencode(substr($key, 1))] = $value;
            }
        }        
        
        $url = $this->getUrl($route_id, $params, $absolute);
        return str_replace($search, $replace, $url);
    }    
    
    /**
    * Упрощенная функция для построения URL в административной части.
    * Если какой-то из параметров не задан, то используется текущее значение, кроме $params
    * 
    * @param string $mod_action - action вызываемого контроллера, если не указан берется из текущего url
    * @param array|null $params - дополнительные параметры
    * @param string $mod_controller - фронт контроллер, который будет вызван (обратный слеш заменяется на -(минус), можно вырезать часть "-controller-admin"), если не указан берется из текущего URL
    * @param bool $absolute - Если true, то путь будет абсолютным
    *
    * @return string
    */
    public function getAdminUrl($mod_action = null, $params = null, $mod_controller = null, $absolute = false)
    {
        $params = (array)$params;
        if ($mod_action === null) {
            $mod_action = $this->http_request->get('do', TYPE_STRING);
        }
        
        if ($mod_controller === null) {
            $mod_controller = $this->http_request->get('mod_controller', TYPE_STRING);
        }
        
        if ($mod_controller !== false) {
            $params['mod_controller'] = $mod_controller;
        }
        
        if (!empty($mod_action) && !isset($params['do'])) {
            $params['do'] = $mod_action;
        }
        
        if (isset($params['do']) && $params['do'] === false) unset($params['do']);
        return $this->getUrl('main.admin', $params, $absolute);
    }
    
    /**
    * Аналог getAdminUrl за исключением того, что может оставлять незакодированными для URL значения ключей
    * Используется для генерации маски Url, в которую можно очень быстро подставлять значения
    * 
    * @param mixed $mod_action - action вызываемого контроллера, если не указан берется из текущего url
    * @param mixed $params - дополнительные параметры. используйте запись ':(двоеточие)ИМЯ КЛЮЧА' => 'ЗНАЧЕНИЕ', чтобы указать, что данный ключ не следует кодировать
    * @param mixed $mod_controller - фронт контроллер, который будет вызван (обратный слеш заменяется на -(минус), можно вырезать часть "-controller-admin"), если не указан берется из текущего URL
    *
    * @return string
    */
    public function getAdminPattern($mod_action = null, $params = array(), $mod_controller = null)
    {
        $url = $this->getAdminUrl($mod_action, $params, $mod_controller);
        foreach($params as $key => $value) {
            if ($key{0} == ':') {
                $search = urlencode($key).'='.urlencode($value);
                $replace = urlencode(substr($key, 1)).'='.$value;
                $url = str_replace($search, $replace, $url);
            }
        }
        return $url;
    }
    
    /**
    * Возвращает текущий маршрут
    * 
    * @return \RS\Router\RouteAbstract
    */
    public static function getCurrentRoute()
    {
        return self::$currentRoute;
    }
    
    /**
    * Устанавливает текущий маршрут
    *
    * @param RouteAbstract $route маршрут
    */
    public static function setCurrentRoute(RouteAbstract $route)
    {
        self::$currentRoute = $route;
    }
    
    /**
    * Возвращает true, если текущая страница является страницей административной панели
    * @return boolean
    */
    public function isAdminZone()
    {
        if (!isset($this->is_admin_zone)) {
            $uri = strtok($this->http_request->server('REQUEST_URI'), '?');
            $this->is_admin_zone = \Setup::$INSTALLED && $this->admin_route->match($this->host, $uri);
        }
        return $this->is_admin_zone;
    }
    
    /**
    * Применяет маршрут, передает управление контроллеру.
    * 
    * @param \RS\Router\RouteAbstract $route
    * @throws \RS\Controller\ExceptionPageNotFound
    * @return boolean
    */
    private function applyRoute(\RS\Router\RouteAbstract $route)
    {
        $controller_class = $route->getController();
        if ($controller_class) {
            if (class_exists($controller_class)) {
                $controller = new $controller_class();
                if ($controller instanceof \RS\Controller\IController) {
                    $this->setCurrentRoute($route);

                    \RS\Event\Manager::fire('applyroute', $route);
                    
                    //Передаем управление Front контроллеру
                    if ($this->gzipEnabled()) ob_start("ob_gzhandler");
                    echo $controller->exec(); //Отправляем данные в браузер
                    if ($this->gzipEnabled()) @ob_end_flush();
                    
                    return true;
                }
            } else {
                throw new \RS\Controller\ExceptionPageNotFound(t('Не найден класс контроллера %0', $controller_class));
            }
        }
    }
    
    /**
    * Выполняет поиск необходимого маршрута и передает ему управление
    */
    public function dispatch()
    {
        $this->initRoutes();
        $uri = strtok($this->http_request->server('REQUEST_URI'), '?');
        foreach(self::$routes as $route) {
            //Определяем текущий маршрут
            if ($route->match($this->host, $uri)) {
                $this->applyRoute($route);
                break;
            }
        }
        
        if ($this->getCurrentRoute() === null) {
            if (\Setup::$INSTALLED) {
                throw new \RS\Controller\ExceptionPageNotFound();
            } else {
                header('location: '.$this->getUrl('install'));
            }
        }
    }
    
}
