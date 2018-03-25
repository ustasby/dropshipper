<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Config;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getroute')
            ->bind('orm.afterwrite.users-user')
            ->bind('externalapi.getexceptions');
    }
    
    public static function getRoute($routes)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);
        $api_key = $config->api_key ? '-'.$config->api_key : '';
        
        $routes[] = new \RS\Router\Route('externalapi-front-apigate', array(
            "/api{$api_key}/methods/{method}",
        ), null, t('Шлюз обмена данными по API'), true);

        $routes[] = new \RS\Router\Route('externalapi-front-apigate-help', array(
            "/api{$api_key}/help/{method}",
            "/api{$api_key}/help"
        ), array(
            'controller' => 'externalapi-front-apigate',
            'Act' => 'help'
        ), t('Описание методов API'), true);        
        
        return $routes;
    }
    
    /**
    * Удаляет все token'ы, выданные пользователю, если тот сменил пароль
    * 
    * @param array $param
    */
    public static function ormAfterwriteUsersUser($param)
    {
        $user = $param['orm'];
        if ($user->isModified('pass')) {
            
            \RS\Orm\Request::make()
                ->delete()
                ->from(new \ExternalApi\Model\Orm\AuthorizationToken())
                ->where(array(
                    'user_id' => $user['id']
                ))
                ->exec();
        }
    }
    
    /**
    * Возвращаем классы исключений, которые используются в методах API
    * 
    * @param \ExternalApi\Model\AbstractException[] $list
    * @return \ExternalApi\Model\AbstractException[]
    */
    public static function externalApiGetExceptions($list)
    {
        $list[] = new \ExternalApi\Model\Exception();
        return $list;
    }
}
