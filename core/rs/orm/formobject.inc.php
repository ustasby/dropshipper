<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Orm;

/**
* ORM объект с динамической структурой, используемый исключительно для отображения форм
*/
class FormObject extends AbstractObject
{
    /**
    * Конструктор объекта параметров контроллера
    *
    * @param PropertyIterator $properties - список свойств, свойство "sectionmodule" зарезервировано.
    */
    function __construct(PropertyIterator $properties)
    {
        parent::__construct();
        $this->setPropertyIterator($properties);
    }
    
    function _init()
    {}

    /**
    * Возвращает объект хранилища
    * @return \RS\Orm\Storage\AbstractStorage
    */
    function getStorageInstance()
    {
        return new \RS\Orm\Storage\Stub($this);
    }
}

