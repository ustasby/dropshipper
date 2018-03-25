<?php
namespace StatusOrder\Controller\Block; //Задаем пространство имен, соответствующее пути к файлу, относительно папки /modules
use \RS\Orm\Type;
 
/**
* Класс блочного контроллера "Недавние комментарии". 
* Будем наследовать от абстрактного класса блочных контроллеров, в котором реализовано все необходимое.
*/
class StatusOrder extends \RS\Controller\Block
{
    protected static
        $controller_title = 'Проверка статуса заказа', //Краткое название контроллера
        $controller_description = 'Отображает статус заказ'; //Описание контроллера
 
    /**
    * Action контроллера
    * 
    * @return \RS\Controller\Result\Standart
    */
    function actionIndex()
    {
        $this->config = \RS\Config\Loader::byModule($this);
        $this->view->assign(array(
            "buttonText" => $this->config->buttonText,
            "authFrom" => $this->config->authFrom,
            ));
        return $this->result->setTemplate('blocks/orderstatus.tpl');
    }
}