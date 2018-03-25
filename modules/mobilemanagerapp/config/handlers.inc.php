<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace MobileManagerApp\Config;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getapps')
            ->bind('order.change')
            ->bind('orm.afterwrite.shop-order');
    }
    
    public static function getApps($app_types)
    {
        $app_types[] = new \MobileManagerApp\Model\AppTypes\StoreManagement();
        
        return $app_types;
    }
    
    /**
    * Отправляем Push уведомление о назначении заказа курьеру
    * 
    * @param array $params
    */
    public static function orderChange($param)
    {
        if (\RS\Config\Loader::byModule(__CLASS__)->push_enable) {            
            if ($param['order']['courier_id'] && ($param['order']['courier_id'] != $param['order_before']['courier_id'])) {
                //Назначение курьера
                $push = new \MobileManagerApp\Model\Push\NewOrderToCourier();
                $push->init($param['order']);
                $push->send();
            }
        }
    }
    
    /**
    * Отправляем Push администратору при создании заказа
    * 
    * @param mixed $param
    */
    public static function ormAfterwriteShopOrder($param)
    {
        if (\RS\Config\Loader::byModule(__CLASS__)->push_enable 
            && $param['flag'] == \RS\Orm\AbstractObject::INSERT_FLAG) 
        {
            $push = new \MobileManagerApp\Model\Push\NewOrderToAdmin();
            $push->init($param['orm']);
            $push->send();            
        }
    }
    
}
