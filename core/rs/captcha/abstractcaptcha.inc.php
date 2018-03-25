<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Captcha;

/**
* Абстрактный класс капчи. Любой класс капчи в системе должен быть потомком данного класса.
*/
abstract class AbstractCaptcha
{
    /**
    * Возвращает идентификатор класса капчи
    * 
    * @return string
    */
    function getShortName(){}
    
    /**
    * Возвращает название класса капчи
    * 
    * @return string
    */
    function getTitle(){}
    
    /**
    * Возвращает название поля для клиентских форм
    * 
    * @return string
    */
    function getFieldTitle(){}
    
    /**
    * Возвращает HTML капчи
    * 
    * @param string $name - атрибут name для шаблона отображения
    * @param string $context - контекст капчи
    * @param array $attributes - дополнительные атрибуты для Dom элемента капчи
    * @param array|null $view_options - параметры отображения формы. если null, то отображать все
    *     Возможные элементы массива:
    *         'form' - форма,
    *         'error' - блок с ошибками,
    *         'hint' - ярлык с подсказкой,
    * @param string $template - используемый шаблон
    * 
    * @return string
    */
    function getView($name, $context = null, $attributes = array(), $view_options = null, $template = null){}
    
    /**
    * Превращает массив атрибутов в строку
    * 
    * @param array $attributes
    * @return string
    */
    protected function getReadyAttributes($attributes = null)
    {
        $ready_attr = '';
        if (!empty($attributes)) {
            foreach ($attributes as $key=>$value) {
                $ready_attr[] .= " $key=\"$value\"";
            }
        }
        return $ready_attr;
    }
    
    /**
    * Проверяет правильность заполнения капчи
    * 
    * @param mixed $data - данные для проверки
    * @param string $context - контекст капчи
    * @return bool
    */
    function check($data, $context = null){}
    
    /**
    * Возвращает текст ошибки
    * 
    * @return string
    */
    function errorText(){}
    
    /**
    * Действие по умолчанию
    */
    function actionDefault(){}
    
    /**
    * Запускается при старте системы
    */
    function onStart(){}
}