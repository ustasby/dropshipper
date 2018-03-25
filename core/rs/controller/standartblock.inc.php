<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Controller;
use \RS\Orm\Type;

/**
* Класс описывает стандартные блоки-контроллеры, имеющие только одно действие и шаблон.
*/
class StandartBlock extends Block
{
    protected
        $default_params = array(
            'indexTemplate' => '', //Должен быть задан у наследника
        );        
        
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return new \RS\Orm\ControllerParamObject(
            new \RS\Orm\PropertyIterator(array(
                'indexTemplate' => new Type\Template(array(
                    'description' => t('Шаблон'),
                    'attr' => array(array(
                        'placeholder' => $this->default_params['indexTemplate']
                    ))
                ))
            ))
        );
    }
}