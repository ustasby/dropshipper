<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Model\Orm;
use RS\Orm\Type;
use ExternalApi\Model\Exception as ApiException;

/**
* Таблица содержит авторизационные token'ы
*/
class AuthorizationToken extends \RS\Orm\OrmObject
{
    protected static 
        $table = 'external_api_token';
        
    protected 
        $app_cache;
        
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'token' => new Type\Varchar(array(
                'description' => t('Авторизационный токен')
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('ID Пользователя')
            )),
            'app_type' => new Type\Varchar(array(
                'description' => t('Класс приложения')
            )),
            'ip' => new Type\Varchar(array(
                'description' => t('IP-адрес')
            )),
            'dateofcreate' => new Type\Datetime(array(
                'description' => t('Дата создания')
            )),
            'expire' => new Type\Integer(array(
                'description' => t('Срок истечения авторизационного токена')
            )),
        ));
    }
    
    /**
    * Выполняет действие перед записью объекта
    * 
    * @param string $flag
    */
    public function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['token'] = sha1(uniqid(rand(), true));
        }
    }
    
    /**
    * Возвращает первичный ключ 
    * 
    * @return string
    */
    public function getPrimaryKeyProperty()
    {
        return 'token';
    }
    
    /**
    * Возвращает объект приложения, для которого выдан token
    * 
    * @return ExternalApi\Model\AbstractMethods\AbstractMethod
    */
    public function getApp()
    {
        if ($this->app_cache === null) {
            $this->app_cache = \RS\RemoteApp\Manager::getAppByType($this['app_type']);
            if (!$this->app_cache) {
                throw new ApiException(t('Приложение %0 не найдено', array($this['app_type'])), ApiException::ERROR_INSIDE);
            }
            
            if (!($this->app_cache instanceof \ExternalApi\Model\App\InterfaceHasApi)) {
                throw new ApiException(t('Приложение %0 не поддерживает работу с API', array($this['app_type'])), ApiException::ERROR_INSIDE);
            }
            
            $this->app_cache->setToken($this);
        }
        
        return $this->app_cache;
    }
    
    /**
    * Возвращает пользователя, для которого выдан token
    * 
    * @return \Users\Model\Orm\User
    */
    public function getUser()
    {
        return new \Users\Model\Orm\User($this['user_id']);
    }
}
