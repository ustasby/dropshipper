<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Model\Orm;
use \RS\Orm\Type;
 
/**
* Класс ORM-объектов "E-mail для рассылки".
* Наследуется от объекта \RS\Orm\OrmObject, у которого объявлено свойство id
*/
class Email extends \RS\Orm\OrmObject
{
    protected static
        $table = 'subscribe_email'; //Имя таблицы в БД
         
    /**
    * Инициализирует свойства ORM объекта
    *
    * @return void
    */
    function _init()
    {     
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),  
            'email' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('E-mail'),
            )),  
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата подписки'),
            )),   
            'confirm' => new Type\Integer(array(
                'maxLength' => 1,
                'default' => 0,
                'description' => t('Подтверждён?'),
                'checkboxView' => array(1,0)
            )),
            'signature' => new Type\Varchar(array(
                'maxLength' => '250',
                'index'=> true,
                'description' => t('Подпись для E-mail'),
            )),  
        ));
        $this->addIndex(array('email', 'confirm'), self::INDEX_KEY);
    }
    
    
    /**
    * Действия перед записью объекта в БД
    * 
    * @param string $flag - insert или update
    * @return void
    */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
           //Получим подпись для E-mail 
           $this['signature'] = md5("signEmail".$this['dateof'].$this['email'].\RS\Site\Manager::getSiteId()); 
        }
    }
    
    /**
    * Получает url активации подписки
    * 
    */
    function getSubscribeActiveUrl()
    {
        return \RS\Router\Manager::obj()->getUrl('emailsubscribe-front-window',array(
            'Act' => 'activateEmail',
            'signature' => $this['signature'],
        ),true);
    }
    
    /**
    * Получает url деактивации подписки
    * 
    */
    function getSubscribeDeActivateUrl()
    {
        return \RS\Router\Manager::obj()->getUrl('emailsubscribe-front-window',array(
            'Act' => 'deActivateEmail',
            'email' => $this['email'],
        ),true);
    }
    
}