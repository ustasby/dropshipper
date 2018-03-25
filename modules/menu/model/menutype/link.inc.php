<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Model\MenuType;
use \RS\Orm\FormObject,
    \RS\Orm\Type,
    \RS\Orm\PropertyIterator;
    

class Link extends AbstractType
{
    /**
    * Возвращает уникальный идентификатор для данного типа
    * 
    * @return string
    */
    public function getId()
    {
        return 'link';
    }
    
    /**
    * Возвращает название данного типа
    * 
    * @return string
    */
    public function getTitle()
    {
        return t('Ссылка');
    }
    
    /**
    * Возвращает описание данного типа 
    * 
    * @return string
    */
    public function getDescription()
    {
        return t('Предназначена для переадресации на любую страницу при нажатии на пункт меню');
    }
    
    /**
    * Возвращает маршрут, если пункт меню должен добавлять его, 
    * в противном случае - false
    * 
    * @return \RS\Router\Route | false
    */
    public function getRoute()
    {
        return false;
    }
    
    /**
    * Возвращает поля, которые должны быть отображены при выборе данного типа
    * 
    * @return \RS\Orm\FormObject
    */
    public function getFormObject()
    {
        return new \RS\Orm\FormObject(new \RS\Orm\PropertyIterator(array(
            'link' => new Type\Varchar(array(
                        'maxLength' => '255',
                        'description' => t('Ссылка'),
                        'hint' => t('Относительная или абсолютная ссылка, например: /news/ или http://readyscript.ru'),
                    )),
            'target_blank' => new Type\Integer(array(
                        'maxLength' => 1,
                        'description' => t('Открывать ссылку в новом окне'),
                        'checkboxView' => array(1,0),
                        'default' => 0,
                    )),
        )));
    }
    
    /**
    * Возвращает ссылку, на которую должен вести данный пункт меню
    * 
    * @return string
    */
    public function getHref($absolute = false)
    {
        return $this->menu['link'];
    }
    
    /**
    * Возвращает true, если пункт меню активен в настоящее время
    * 
    * @return bool
    */
    public function isActive()
    {
        return \RS\Http\Request::commonInstance()->server('REQUEST_URI') === $this->menu['link'];
    }

}