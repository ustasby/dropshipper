<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace MobileManagerApp\Model\Push;

/**
* Push уведомление курьеру о новом заказе
*/
class NewOrderToAdmin extends \PushSender\Model\Firebase\Push\RsPushNotice
{
    public
        $order;
    
    public function init(\Shop\Model\Orm\Order $order)
    {
        $this->order = $order;
    }
    
    /*
    * Возвращает описание уведомления для внутренних нужд системы и 
    * отображения в списках админ. панели
    * 
    * @return string
    */
    public function getTitle()
    {
        return t('Новый заказ(администратору)');
    }
    
    /**
    * Возвращает для какого приложения (идентификатора приложения в ReadyScript) предназначается push
    * 
    * @return string
    */
    public function getAppId()
    {
        return 'store-management';
    }
        
    /**
    * Возвращает одного или нескольких получателей
    * 
    * @return array
    */
    public function getRecipientUserIds()
    {
        $admin_groups = (array)\RS\Config\Loader::byModule($this)->allow_user_groups;
        $couriers_group = (array)\RS\Config\Loader::byModule('shop')->courier_user_group;
        
        $real_admin_groups = array_diff($admin_groups, $couriers_group);
        if ($real_admin_groups) {
            $user_ids = \RS\Orm\Request::make()
                ->select('user')
                ->from(new \Users\Model\Orm\UserInGroup())
                ->whereIn('group', $real_admin_groups)
                ->exec()->fetchSelected(null, 'user');
            
            return $user_ids;
        }
        return array();
    }
    
    /**
    * Возвращает Заголовок для Push уведомления
    * 
    * @return string
    */
    public function getPushTitle()
    {
        return t('Новый заказ N%num на сумму %total', array(
            'num' => $this->order['order_num'],
            'total' => \RS\Helper\CustomView::cost($this->order['totalcost'], $this->order['currency_stitle'])
        ));
    }
    
    /**
    * Возвращает текст Push уведомления
    * 
    * @return string
    */
    public function getPushBody()
    {
        return t('Покупатель: %fio, Сайт: %site', array(
            'fio' => $this->order->getUser()->getFio(),
            'site' => \Setup::$DOMAIN
        ));
    }
    
    /**
    * Возвращает произвольные данные ключ => значение, которые должны быть переданы с уведомлением
    * 
    * @return array
    */
    public function getPushData()
    {
        $site = new \Site\Model\Orm\Site($this->order['__site_id']->get());
            
        return array(
            'order_id' => $this->order['id'],
            'site_uid' => $site->getSiteHash()
        );
    }
    
    /**
    * Возвращает click_action для данного уведомления
    * 
    * @return string
    */
    public function getPushClickAction()
    {
        return 'com.readyscript.dk.storemanagement.Order.Detail_TARGET_NOTIFICATION';
    }
}