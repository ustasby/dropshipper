<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Controller\Front;
use \ExternalApi\Model;

/**
* Контроллер, встречающий запросы к методам API и отображающий справку по методам API
*/
class ApiGate extends \RS\Controller\Front
{                
    public
        $method,
        $lang,
        $default_version,
        $version,
        $api_router;
        
    function init()
    {
        $this->wrapOutput(false);
        $config = \RS\Config\Loader::byModule($this);
        $this->default_version = !empty($config['default_api_version']) ? $config['default_api_version']: 1;
        $this->method          = $this->url->request('method', TYPE_STRING);
        $this->all_languages   = \ExternalApi\Model\ApiRouter::getMethodsLanguages();
        $this->lang            = $this->url->convert( $this->url->request('lang', TYPE_STRING), $this->all_languages);
        
        $this->view->assign(array(
            'lang' => $this->lang
        ));
        
        //Устанавливаем язык
        if (\RS\Language\Core::getCurrentLang() != $this->lang) {
            \RS\Language\Core::setSystemLang($this->lang);
            \RS\Language\Core::init();
        }
        
        $this->version = $this->request('v', TYPE_STRING, $this->default_version);
        $this->api_router = new \ExternalApi\Model\ApiRouter($this->version, $this->lang);        
        
        if (!$this->getModuleConfig()->enabled) {
            $this->e404(); //Запрещаем обращение к API, если модуль выключен
        }
        
        //Проверяем допустимый домен        
        $allow_domain = $this->getModuleConfig()->allow_domain;
        if ($allow_domain && $allow_domain != $this->url->server('HTTP_HOST')) {
            $this->e404(t('Неверный домен для обращения к API'));
        }
    }
        
    /**
    * Выполняет метод API
    */
    function actionIndex()
    {        
        $format = $this->url->convert( $this->url->request('format', TYPE_STRING), array(
            'json'
        ));        
        
        try {
            
            $params = $this->api_router->makeParams($this->method, $this->url);            
            $result = $this->api_router->runMethod($this->method, $params);
                        
        } catch(\ExternalApi\Model\AbstractException $e) {
            
            $result = $e->getApiError();
        }

        //Пишем запрос в лог
        \ExternalApi\Model\LogApi::writeToLog($this->url, $this->method, $params, $result);          
        \ExternalApi\Model\Orm\Log::removeOldItems();
        $this->app->headers
                        ->addHeader('Access-Control-Allow-Origin', '*')
                        ->addHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
                        ->addHeader('Access-Control-Allow-Headers', '*, x-client-name, x-client-version')
                        ->addHeader('Content-type', 'application/json; charset=utf-8');
        
        return Model\ResultFormatter::format($result, $format);
    }
    
    /**
    * Показывает справку по методу или всем методам API
    */
    function actionHelp()
    {
        if (!$this->getModuleConfig()->enable_api_help) {
            $this->e404(t('Раздел документации отключен'));
        }
        
        $method = $this->url->request('method', TYPE_STRING);
        
        if ($method == 'errors') {
            $this->view->assign(array(
                'exceptions' => \ExternalApi\Model\ErrorManager::getExceptionClasses()
            ));
            
            $template = 'help_errors.tpl';
        } elseif ($method) {
            if ($module_instance = \ExternalApi\Model\ApiRouter::getMethodInstance($method)) {
                //Просмотр одного метода
                $this->view->assign(array(
                    'method' => $method,
                    'method_info' => $module_instance->getInfo($this->lang)
                ));
                $template = 'help_method.tpl';
            } else {
                $this->e404(t('Метод не найден'));
            }
        } else {
            //Оглавление методов
            $this->view->assign(array(
                'grouped_methods' => \ExternalApi\Model\ApiRouter::getGroupedMethodsInfo($this->lang),
                'current_version' => $this->default_version,
                'languages'       => $this->all_languages
            ));
            $template = 'help_method_list.tpl';
        }
        return $this->view->fetch($template);
    }
}