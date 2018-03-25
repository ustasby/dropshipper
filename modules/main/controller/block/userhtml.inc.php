<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Block;
use \RS\Orm\Type;

/**
* Блок - Произвольное содержимое
*/
class UserHtml extends \RS\Controller\Block
{
    protected static
        $controller_title = 'Произвольный HTML',
        $controller_description = 'Отображает заданное пользователем содержимое';
    
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return new \RS\Orm\ControllerParamObject(
            new \RS\Orm\PropertyIterator(array(
                'html' => new Type\Richtext(array(
                    'description' => t('Произвольное содержимое')
                ))
            ))
        );
    }
    

    function actionIndex()
    {
        return $this->result->setHtml($this->getParam('html'));
    }
}
?>
