<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

use Main\Model\NoticeSystem\MeterApi;
use Main\Model\NoticeSystem\ReadedItemApi;

class ReservationApi extends \RS\Module\AbstractModel\EntityList
                        implements \Main\Model\NoticeSystem\HasMeterInterface
{
    const
        /**
         * Идентификатор счетчика предварительных заказов
         */
        METER_RESERVATION = 'rs-admin-menu-advorders';

    function __construct()
    {
        parent::__construct(new Orm\Reservation);
    }

    /**
     * Возвращает API по работе со счетчиками
     *
     * @param integer|null user_id ID пользователя. Если пользователь не задан, то используется текущий пользователь
     * @return \Main\Model\NoticeSystem\MeterApi
     */
    function getMeterApi($user_id = null)
    {
        return new MeterApi($this->obj_instance,
                            self::METER_RESERVATION,
                            $this->getSiteContext(),
                            $user_id);
    }

    /**
     * Возвращает количество непрочитанных предварительных заказов
     *
     * @param integer|null $user_id ID пользователя. Если null, то будет использован текущий пользователь
     * @return integer
     */
    function getNewCounter($user_id = null)
    {
        $readed_items_api = new ReadedItemApi($this->getSiteContext(), $user_id);
        return $readed_items_api->getUnreadCount($this->obj_instance, self::METER_RESERVATION);
    }
    
    /**
    * Отправляет уведомления подписавшимся клиентам о поступлении товар
    * 
    * @return integer возвращает количество отправленных уведомлений
    */
    public static function SendNoticeReceipt($site_id)
    {
        // Выбрали предварительные заказы, по которым нужно отправить уведомление
        $reservations = \RS\Orm\Request::make()
            ->select('R.*')
            ->from(new Orm\Reservation, 'R')
            ->join(new \Catalog\Model\Orm\Offer, 'R.offer_id = O.id', 'O')
            ->where("R.is_notify = '1' AND R.status = '#status' AND O.num >= R.amount AND R.site_id = '#site_id'", array(
                'status' => Orm\Reservation::STATUS_OPEN,
                'site_id' => $site_id
            ))
            ->objects();
            
            foreach($reservations as $reservation) {
                //Отправляем уведомление 
                $notice = new Notice\SupplyToUser();
                $notice->init($reservation);
                \Alerts\Model\Manager::send($notice);
                
                //Закрываем предварительный заказ
                $reservation['status'] = Orm\Reservation::STATUS_CLOSE;
                $reservation->update();
            }
        return count($reservations);
    }
    
    /**
    * Создает заказ из предварительного
    * 
    * @param Orm\Reservation $reservation - Предварительный заказ
    * @return Orm\Order возвращает объект созданного заказа
    */
    function createOrderFromReservation(Orm\Reservation $reservation)
    {
        //Создадим заказ
        $order = new \Shop\Model\Orm\Order();
        //Данные пользователя
        if ($reservation['user_id'] > 0) {
            $order['user_id'] = $reservation['user_id'];
        } else {
            $order['user_email'] = $reservation['email'];
            $order['user_phone'] = $reservation['phone'];
        }
        
        //Данные валюты
        $order['currency'] = $reservation['currency'];
         
        $currency_api = new \Catalog\Model\CurrencyApi();
        $currency     = $currency_api->setFilter('title', $order['currency'])->getFirst();
        if ($currency){
            $order['currency_ratio']  = $currency['ratio'];
            $order['currency_stitle'] = $currency['stitle'];
        }
        //Отключение уведомлений
        $order['disable_checkout_notice'] = 1; 

        $order->insert();  
        
        //Создаём корзину
        $cart = $order->getCart();
        
        $products_arr = array();       
        
        //Получаем offer( sortn ) 
        $offer = new \Catalog\Model\Orm\Offer($reservation['offer_id']);                
        
        //Попробуем загрузить сам товар
        $product     = new \Catalog\Model\Orm\Product($reservation['product_id']);
        $symb = array_merge(range('a', 'z'), range('0', '9')); //Символя для генерации уникального индекса                
        //Генерируем запист товара
        $uniq = \RS\Helper\Tools::generatePassword(10, $symb); //Уникальный индекс товара
        $products_arr[$uniq] = array(
              'uniq'          => $uniq,
              'type'          => \Shop\Model\Cart::TYPE_PRODUCT,
              'entity_id'     => $reservation['product_id'],  
              'title'         => $reservation['product_title'],  
              'barcode'       => $product->getBarCode($offer['sortn']),
              'single_weight' => $product->getWeight($offer['sortn']),
              'amount'        => $reservation['amount'],  
              'offer'         => $offer['sortn'],
              'single_cost'   => $product->getCost(null, $offer['sortn'], false),
        );     
        $reservation['status'] = 'close'; 
        $reservation->update();//изменяем статес предварительного заказа
        
        $cart->updateOrderItems($products_arr); //Обновляем товары в корзине
        $cart->saveOrderData(); //Сохраняем данные товаров в БД
        
        //Отправляем уведомление покупателю
        $notice = new \Shop\Model\Notice\CheckoutUser();
        $notice->init($order);
        \Alerts\Model\Manager::send($notice);  
        
        
        //Отключим уведомления
        $order['notify_user'] = false;
        $order->update(); //Обновляем заказ

        return $order;
    }   
    
}
?>
