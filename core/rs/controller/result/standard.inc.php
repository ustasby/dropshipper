<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Controller\Result;

/**
* Помогает формировать вывод контроллера в едином формате.
* Если это ajax запрос, то формирует json на выходе, в противном случае возвращает html
*/
class Standard implements ITemplateResult
{
    private $template;
    private $checkAjaxOutput = true;
    private $noAjaxRedirect;
    private $controller;
    private $result = array(
            'html' => null
        );
    
    function __construct(\RS\Controller\AbstractController $controller)
    {
        $this->controller = $controller;
    }
    
    function getController()
    {
        return $this->controller;
    }
    
    /**
    * Устанавливает шаблон, который будет использован для рендеринга
    * 
    * @param mixed $template
    * @return Standard
    */
    function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }
    
    /**
    * Возвращает шаблон, который используется для рендеринга
    * @return string
    */
    function getTemplate()
    {
        return $this->template;
    }
    
    /**
    * Возвращает переменные, переданные в шаблон
    * @return array
    */
    function getTemplateVars()
    {
        return $this->controller->view->getTemplateVars();
    }
    
    /**
    * Нужен редирект на авторизацию
    * 
    * @param boolean $bool - если true, то произойдет редирект на страницу авторизации
    * @return Standard
    */
    function setNeedAuthorize($bool)
    {
        $this->result['needAuthorization'] = $bool;
        return $this;
    }
    
    /**
    * Устанавливает успешно ли выполнено действие контроллера
    * 
    * @param boolean $success
    * @return Standard
    */
    function setSuccess($success)
    {
        $this->result['success'] = (bool)$success;
        return $this;
    }
    
    /**
    * Возвращает true, если раннее был вызван метод setSuccess(true)
    */
    function isSuccess()
    {
        return !empty($this->result['success']);
    }
    
    /**
    * Добавляет сообщение, которое будет отображено пользователю
    * 
    * @param string $text - текст сообщений
    * @param array | null $options - параметры, которые будут переданы в виде объекта JS плагину
    * @return Standard
    */
    function addMessage($text, array $options = null)
    {
        if (!isset($this->result['messages'])) {
            $this->result['messages'] = array();
        }
        $this->result['messages'][] = array(
            'text' => $text,
            'options' => $options
        );
        return $this;
    }
    
    /**
    * Добавляет сообщение об ошибке, которое будет отображено пользователю
    * 
    * @param string $text - текст сообщений
    * @param array | null $options - параметры, которые будут переданы в виде объекта JS плагину
    * @return Standard
    */    
    function addEMessage($text, array $options = null)
    {
        if (!$options) {
            $options = array();
        }
        return $this->addMessage($text, array_merge(array('theme' => 'error'), $options));
    }
    
    /**
    * Устанавливает html, который будет возвращен.
    * В случае, если это ajax запрос, то возвращается в формате json в секции html с остальными данными, 
    * в противном случае возвращается только $html
    * 
    * @param mixed $html
    * @return Standard
    */
    function setHtml($html)
    {
        $this->result['html'] = $html;
        return $this;
    }
    
    /**
    * Устанавливает ошибки, которые произошли в форме
    * 
    * @param array $errors
    * @return Standard
    */
    function setErrors(array $errors)
    {
        $this->result['formdata']['errors'] = $errors;
        return $this;
    }
    
    /**
    * Устанавливает текст успешного действия контроллера, например текст успешного сохранения.
    * 
    * @param string $text
    * @return Standard
    */
    function setSuccessText($text)
    {
        $this->result['formdata']['success_text'] = $text;
        return $this;
    }
    
    /**
    * Устанавливает редирект, который произойдет и если это ajax запрос и если это обычный запро
    * 
    * @param string $url
    * @return Standard
    */
    function setRedirect($url)
    {
        $this->setNoAjaxRedirect($url);
        $this->setAjaxRedirect($url);
        return $this;
    }
    
    /**
    * Устанавливает редирект, который произойдет в контексте updatable, если это ajax запрос
    * 
    * @param string $url
    * @return Standard
    */
    function setAjaxRedirect($url)
    {
        $this->result['redirect'] = $url;
        return $this;
    }    
    
    /**
    * Устанавливает редирект, который произойдет в окне браузера
    * 
    * @param string $url
    * @return Standard
    */
    function setAjaxWindowRedirect($url)
    {
        $this->result['windowRedirect'] = $url;
        return $this;
    }
    
    /**
    * Устанавливает редирект, который произойдет, если это не ajax запрос
    * 
    * @param string $url
    * @return Standard
    */
    function setNoAjaxRedirect($url)
    {
        $this->noAjaxRedirect = $url;
        return $this;
    }
    
    /**
    * Дабваляет произвольную секцию в JSON ответ, который будет возвращен в случае ajax запроса
    * 
    * @param mixed $key
    * @param mixed $value
    * @return Standard
    */
    function addSection($key, $value = null)
    {
        if (is_array($key)) {
            $this->result = array_merge($this->result, $key);
        } else {
            $this->result[$key] = $value;
        }
        return $this;
    }
    
    /**
    * Возвращает HTML, который задан через setHtml или формирует его с помощью заданного шаблона
    * @return string
    */
    public function getHtml()
    {
        if ($this->result['html'] === null && $this->template !== null) {
            return $this->controller->view->fetch($this->getTemplate());
        }
        return $this->result['html'];
    }
    
    /**
    * Включает/Отключает возможность вернуть результат в json в случае ajax запроса
    * 
    * @param bool $bool - Если true, то в случае ajax запроса getOutput вернет json, иначе HTML
    * @return Standard
    */
    public function checkAjaxOutput($bool = true)
    {
        $this->checkAjaxOutput = $bool;
        return $this;
    }
    
    /**
    * Возвращает установленный html или выполнит заданный редирект, если это не ajax запрос.
    * В случае ajax запроса возвращает в json формате установленные секции
    * @return mixed
    */
    function getOutput()
    {
        $this->result['html'] = $this->getHtml();
        if ($this->controller->url->isAjax() && $this->checkAjaxOutput) {
            if ($this->controller instanceof \RS\Controller\Front || $this->controller instanceof \RS\Controller\Admin\Front) {
                $this->controller->wrapOutput(false);
            }
            return json_encode($this->result);
        } else {            
            if ($this->noAjaxRedirect !== null) {
                $this->controller->redirect($this->noAjaxRedirect);
            }
            return isset($this->result['html']) ? $this->result['html'] : '';
        }
    }
}


