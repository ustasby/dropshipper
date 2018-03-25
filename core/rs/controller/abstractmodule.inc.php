<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;
use \RS\Application\Auth as AppAuth;

/**
* Базовый контроллер для модулей.
*/
abstract class AbstractModule extends AbstractController
{

    /**
    * Текущий пользователь
    *
    * @var \Users\Model\Orm\User $user
    */
    protected $user;
        
    /**
    * Минимальные права к модулю для запуска контроллера
    * @var integer
    */
    protected $access_right = 1;
        
    /**
    * Значение параметров по-умолчанию, которые возвращаются методом getParam
    *
    * @var array
    */
    protected $default_params = array();
    protected $param = array();
        
    protected $mod_name = '';
        //Путь к ресурсам модуля
    protected $controller_name = '';
    protected $mod_css = '/modules/%MODULE%/view/css/';
    protected $mod_js = '/modules/%MODULE%/view/js/';
    protected $mod_img = '/modules/%MODULE%/view/img/';
    protected $mod_tpl = '/modules/%MODULE%/view/';
        
    private
        $com_error; //результат выполнения функции инициализации _init(), если null, то все в порядке.
    
    function __construct($param = array())
    {
        
        parent::__construct();
        
        $this->param = $param + $this->param;
        
        if (!isset($this->param['name'])) {
            $this->param['name'] = str_replace('\\','-',get_class($this)).'-'.$this->getAction();
        };
        $this->setResource();
        $this->addResource();

        $theme = \RS\Theme\Manager::getCurrentTheme('theme');
        $common_path = \Setup::$SM_TEMPLATE_PATH.$theme.\Setup::$MODULE_WATCH_TPL.'/'.strtolower($this->mod_name).'/';
        if (is_dir($common_path)) {
            $dirs = $this->view->getTemplateDir();
            $dirs = array('theme_module' => $common_path) + $dirs;
            $this->view->setTemplateDir($dirs);
        }        
        
        $this->com_error = $this->init();
    }
    
    /**
    * Возвращает значение параметра по ключу, заданного в конструкторе или default - если такового не существует
    * Поиск значения по ключу также ведется в массиве $this->default_params, если не задан параметр $default
    * Если ключ не задан, возвращает все параметры
    * 
    * @param string $key - название параметра, если key == null, то вернётся весь массив параметров
    * @param mixed $default - значение параметра по-умолчанию
    * @param bool $checkempty - Если true, то наличие значеня для key будет проверяться функцией empty иначе isset
    * @return mixed
    */
    function getParam($key = null, $default = null, $checkempty = false)
    {
        if ($key === null) return ($this->param + $this->default_params);
        
        if ($default === null && isset($this->default_params[$key])) {
            $default = $this->default_params[$key];
        }
        $isset = $checkempty ? !empty($this->param[$key]) : (isset($this->param[$key]) && $this->param[$key] !== '');
        
        return $isset ? $this->param[$key] : $default;
    }
    
    /**
    * Устанавливает основные пути для компонента, исходя из его имени.
    * 
    * @return void
    */
    function setResource()
    {
        $this->controller_name = get_class($this);
        
        if (preg_match('/(.*?)\\\.*/', $this->controller_name, $match))
        {
            $this->mod_name = strtolower($match[1]);
            
            $url_controller_name =  str_replace('-controller-admin','', strtolower(str_replace('\\', '-', trim($this->controller_name,'\\'))));
            $this->mod_css = strtolower(str_replace('%MODULE%', $this->mod_name, \Setup::$FOLDER.$this->mod_css));
            $this->mod_js = strtolower(str_replace('%MODULE%', $this->mod_name, \Setup::$FOLDER.$this->mod_js));
            $this->mod_img = strtolower(str_replace('%MODULE%', $this->mod_name, \Setup::$FOLDER.$this->mod_img));
            $this->mod_relative_tpl = str_replace('%MODULE%', $this->mod_name, $this->mod_tpl); //Относительный путь к шаблону модуля
            $this->mod_tpl = \Setup::$PATH.str_replace('%MODULE%', $this->mod_name, $this->mod_tpl);
            
            $this->user = clone AppAuth::getCurrentUser();
            $this->view->template_dir = $this->mod_tpl;
            
            $this->view->assign('mod_css', $this->mod_css);
            $this->view->assign('mod_js', $this->mod_js);
            $this->view->assign('mod_img', $this->mod_img);
            $this->view->assign('mod_name', $this->mod_name);
            $this->view->assign('mod_tpl', $this->mod_tpl);
            
            $this->view->assign('action_var', $this->action_var);
            $this->view->assign('ctrl_name', $this->controller_name); 
            $this->view->assign('this_controller', $this); //Чтобы  шаблоне можно было использовать любую переменную
            $this->view->assign('param', $this->getParam());
            $this->view->assign('is_auth', AppAuth::isAuthorize());
            $this->view->assign('current_user', $this->user);
            $this->view->assign('current_site', \RS\Site\Manager::getSite());
        } else {
            throw new Exception(t('Неверно задано имя контроллера(Контроллер должен находиться в namespace ИМЯМОДУЛЯ/Controller) ').$this->controller_name, 1);
        }
    }
    
    function getControllerName()
    {
        return $this->controller_name;
    }
    
    /**
    * Здесь добавляем все css, js которые требует компонент
    */
    function addResource() {}
    
    /**
    * Функция, вызывающяся сразу после конструктора
    * в случае успешной инициализации ничего не должна возвращать (null),
    * в случае ошибки должна вернуть текст ошибки, который будет возвращен при вызове _exec();
    */
    function init() {}
    
    /**
    * готовим Output компонента
    */
    function fetch($tpl, $param_name = 'tpl')
    {        
        if (!empty($this->param['return_variable'])) return $this->view->getTemplateVars();
        
        //Если при вставке компонента указан другой шаблон, то используем его.
        if (isset($this->param[$param_name])) 
        {
            $tpl = $this->param[$param_name];
        }
        if (empty($tpl)) throw new CComException(t('Не задан шаблон у компонента ').get_class($this));
        return $this->view->fetch($tpl);
    }
    
    /**
    * Метод возвращает параметры из $_request с учетом установленных префиксов для компонента.
    * (префиксы можно устанавливать, если на одной странице имеется несколько одинаковых компонентов)
    */
    function request($key, $type, $default = null, $strip = '')
    {
        if (isset($this->param['prefix_get'])) $key = $this->param['prefix_get'].$key;
        return $this->url->request($key, $type, $default, $strip);
    }
    
    /**
    * Возвращает имя переменной с учетом префикса
    */
    function getKeyName($keyname)
    {
        return (isset($this->param['prefix_get'])) ? $this->param['prefix_get'].$keyname : $keyname;
    }
    
    /**
    * Возвращает ошибку $error_text, в удобном виде (обернутую в HTML)
    * 
    * @param string $error_text
    */
    function comError($error_text)
    {
        $this->view->assign(array(
            'com' => $this,
            'error_text' => $error_text
        ));
        $this->app->addCss('common/errors.css', null, BP_COMMON);
        return $this->view->fetch(\Setup::$SM_TEMPLATE_PATH.'system/comerror.tpl');
    }
    
    /**
    * Возвращает false, если нет ограничений по досуту к данному контроллеру, иначе возвращает текст ошибки
    * @return string | bool(false)
    */
    function checkAccessRight()
    {
        if (\RS\Application\Auth::getCurrentUser()->getRight($this->mod_name) < $this->access_right) {
            return t('Недостаточно прав к запрашиваемому модулю "%0"', array($this->mod_name));
        }
        return false;
    }     

    /**
    * Выполняет action(действие) текущего контроллера, возвращает результат действия
    * 
    * @param boolean $returnAsIs - возвращать как есть. Если true, то метод будет возвращать точно то, 
    * что вернет действие, иначе результат будет обработан методом processResult
    * 
    * @return mixed
    */
    function exec($returnAsIs = false)
    {
        //Проверка прав. Разрешаем выполнение контроллера только при наличии минимальных прав к текущему модулю        
        if ($message = $this->checkAccessRight()) {
            return $this->processResult($message);
        }
                
        if ($this->com_error !== null) {
            return $this->com_error; //Если во время инициализации были ошибки, то возвращаем их.
        }
        
        if (isset($this->param['__action'])) {
            $this->presetAct($this->param['__action']);
        }
        return parent::exec($returnAsIs);
    }
    
    /**
    * У контроллеров админки не может быть отладочной информации
    * @return \RS\Debug\Group | null
    */
    function getDebugGroup()
    {
        return null;
    }    
    
    /**
    * Возвращает путь к шаблонам модуля
    */
    function getModTplPath()
    {
        return \Setup::$PATH.\Setup::$MODULE_FOLDER.'/'.strtolower($this->mod_name).\Setup::$MODULE_TPL_FOLDER.'/';
    }    
    
    /**
    * Возвращает объект с конфигурацией модуля, к которому относится данный контроллер
    * 
    * @return RS\Orm\ConfigObject
    */
    function getModuleConfig()
    {
        return \RS\Config\Loader::byModule($this);
    }
  
}

