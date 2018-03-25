<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Application\Block;

/**
* С помощью данного класса - можно встраивать блоки( блок-контроллеры модулей ) в шаблоны. 
* Методы данного класса используются шаблонизатором в конструкциях moduleinsert, modulegetvars
*/
class Template
{
    /**
    * Возвращает вывод блочного контроллера.
    * 
    * @param string $controller_name - Имя класса блок-контроллера
    * @param array $param - Дополнительные параметры
    * @param array &$vars - Возвращает параметры, которые блок-контроллер передает в шаблон
    * @return mixed
    */
    public static function insert($controller_name, $param = array(), &$vars = array())
    {
        try 
        {
            if (!class_exists($controller_name)) {
                throw new Exception(t("Блочный контроллер '%0' не найден", array($controller_name)));
            }
            
            if (!is_subclass_of($controller_name, '\RS\Controller\Block') && !is_subclass_of($controller_name, '\RS\Controller\Admin\Block')) {
                throw new Exception(t("Блочный контроллер '%0' должен быть наследником \\RS\\Controller\\Block", array($controller_name)));
            }
            
            $com = new $controller_name($param + array('_rendering_mode' => true)); //Сообщаем блоку-контроллеру, что он вызван во время рендеринга страницы
            $result = $com->exec(true);
            
            if ($result instanceof \RS\Controller\Result\ITemplateResult) {
                //Получаем переменные, используемые в шаблоне
                $vars = $result->getTemplateVars();
            }
            
            $result_html = $com->processResult($result);
            
        } catch(\Exception $e) {
            //Если в контроллере произошло исключение, выводим информацию об этом
            $ex_tpl = new \RS\View\Engine();
            $ex_tpl->assign(array(
                'exception' => $e,
                'controllerName' => $controller_name,
                'type' => get_class($e),
                'uniq' => uniqid()
            ));
            $result_html = $ex_tpl->fetch('%SYSTEM%/comexception.tpl');
        }
        return $result_html;
    }
    
    /**
    * Возвращает массив переменнх, которые должны были пойти в шаблон(на вывод) блочного контроллера.
    * 
    * @param string $controller_name - Имя класса контроллера
    * @param array $param - Дополнительные параметры
    * @return mixed
    */
    public static function getVariable($controller_name, $param = array())
    {
        $param = array('return_variable' => true) + $param;
        $com = new $controller_name($param + array('_rendering_mode' => true));
        $result = $com->exec(true);

        if ($result instanceof \RS\Controller\Result\ITemplateResult) {
            return $result->getTemplateVars();
        }
        return null;
    }
    
    /**
    * Проверяет существование блочного контроллера
    * 
    * @param string $controller_name - Имя класса контроллера
    * @return boolean
    */
    public static function isControllerExists($controller_name)
    {
        return class_exists($controller_name);
    }    
    
}

