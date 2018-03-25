<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;

/**
* Абстрактный класс контроллера. 
*/
class AbstractController implements IController
{
    const
        DEFAULT_ERROR_PAGE_TPL = '%THEME%/exception.tpl'; //Путь к шаблону страницы ошибок

    /**
    * Smarty Шаблонизатор
    * @var \RS\View\Engine $view
    */
    public $view;
    /**
    * @var \RS\Http\Request $url
    */
    public $url;
    /**
    * @var \RS\Application\Application $app
    */
    public $app;
    /**
    * @var \RS\Router\Manager $router
    */
    public $router;
        
    protected
        $act = null,
        $action_var = 'Act',
        /**
        * @var Result\Standard
        */
        $result;
    
    private
        $preset_act = null;
    
    /**
    * Базовый конструктор контроллера. желательно вызывать в конструкторах порожденных классов.
    */
    function __construct()
    {
        $this->view   = new \RS\View\Engine();
        $this->app    = \RS\Application\Application::getInstance(); //Получили экземпляр класса страница
        $this->url    = \RS\Http\Request::commonInstance();
        $this->router = \RS\Router\Manager::obj();
        $this->result = new \RS\Controller\Result\Standard($this);
    }
    
    /**
    * Оборачивает HTML секциями body, html добавляет секцию head с мета тегами, заголовком
    * 
    * @param string $body - HTML, внутри тега body, который нужно обернуть
    * @param string $html_template - имя оборачивающего шаблона
    * @return string
    */
    function wrapHtml($body, $html_template = null)
    {
        if (!$html_template) {
            $html_template = '%system%/html.tpl';
        }

        $result = \RS\Event\Manager::fire('controller.beforewrap', array(
            'controller' => $this,
            'body' => $body,
        ));

        $result_array = $result->getResult();
        $body = $result_array['body'];
        
        $html = new \RS\View\Engine();
        $html->assign('app', $this->app);
        $html->assign('body', $body);
        return $html->fetch($html_template);
    }
    
    /**
    * Взвращает Action который будет вызван
    * 
    * @return string
    */
    function getAction()
    {
        if ($this->preset_act !== null) {
            $act = $this->preset_act;
        } else {
            $act = $this->action_var ? $this->url->request($this->action_var, TYPE_STRING, "index") : "index";
        }
        
        return $act;
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
        $act = $this->getAction();
        if (is_callable( array($this, 'action'.$act) )) {

            $this->act = $act;
            
            $event_result = \RS\Event\Manager::fire('controller.beforeexec.'.$this->getUrlName(), array(
                'controller' => $this,
                'action' => $act
            ))->getResult();
            
            if (isset($event_result['output'])) {
                $result = $event_result['output'];
            } else {
                $result = $this->{'action'.$act}(); 
            }
            
            $result = \RS\Event\Manager::fire('controller.afterexec.'.$this->getUrlName(), $result)->getResult();
            
            if ($returnAsIs) return $result;
            return $this->processResult($result);
        }
        else {
            $this->e404(t('Указанного действия не существует'));
        }
    }
    
    /**
    * Обрабатывает результат выполнения действия, возвращает HTML
    * Отправляет подготовленные заголовки в браузер
    * 
    * @param Result\IResult $result
    * @return string;
    */
    function processResult($result)
    {
        if (is_object($result)) {
            if ($result instanceof Result\IResult) {
                $result_html = $result->getOutput();
            } else {
                throw new Exception(t('Контроллер должен возвращать объект, имплементирующий интерфейс \RS\Controller\Result\IResult'));
            }
        } else {
            $result_html = $result;
        }
        $this->app->headers->sendHeaders();
        return $result_html;
    }

    
    /**
    * Позволяет переопределить вызываемый метод в контроллере
    * @param string $act - метод контроллера
    * @return void
    */
    function presetAct($act)
    {
        $this->preset_act = $act;
    }
    
    /**
    * Отдает в output страницу с ошибкой 404. Прекращает выполнение скрипта
    * 
    * @param mixed $reason
    * @throws ExceptionPageNotFound
    * @return void
    */
    function e404($reason = null)
    {
        if ($reason == null) {
            $reason = t('Проверьте правильность набранного адреса');
        }
        throw new ExceptionPageNotFound($reason, get_class($this));
    }

    /**
     * Перенаправляет браузер пользователя на другую страницу. Прекращает выполнение скрипта
     *
     * @param string $url Если url не задан, то перенаправляет на корневую страницу сайта
     * @param int $status - номер статуса редиректа с которым выполнять редирект
     * @return void
     */
    function redirect($url = null, $status = 302)
    {
        if (!$url) {
            if ($site = \RS\Site\Manager::getSite()) {
                $url = $site->getRootUrl();
            } else {
                $url = '/';
            }
        }
        $this->app->headers
            ->addHeader('location', $url)
            ->setStatusCode($status)
            ->sendHeaders();
        exit;
    }
    
    /**
    * Перезагружает у пользователя текущую страницу
    * 
    * @return void
    */
    function refreshPage()
    {
        $url = $this->url->server('REQUEST_URI', TYPE_STRING);
        return $this->redirect($url);
    }
    
    /**
    * Возврщает имя текущего контроллера для использования в URL
    * 
    * @return string
    */
    function getUrlName()
    {
        $class = get_class($this);
        $class = strtolower(trim(str_replace('\\', '-', $class),'-'));
        return str_replace('-controller', '', $class);
    }

}

