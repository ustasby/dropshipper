<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class ToucanApi
{
    const
        ENTITY_TOUCAN = '2can';
        
    /**
    * Возвращает поля, которые должны прийти от 2can, в случае если 
    * мобильное приложение store-management присылает флаг is_payed = 1 при 
    * вызове метода API order.update
    * 
    * @return array
    */
    public function getToucanCustomFields()
    {
        return array(
            'payment' => t('ID транзакции(2can)'), 
            'card_number' => t('Номер карты(2can)'), 
            'amount' => t('Сумма(2can)'), 
            'customer_address' => t('Адрес клиента(2can)'), 
            'description' => t('Назначение платежа(2can)'), 
            'imei' => t('IMEI телефона(2can)'),
            
            'rrn_code' => t('RRN код(2can)'),
            'slip' => t('Текст операции(2can)'),
            'status' => t('Статус(2can)'), 
            
            'date_time' => t('Дата и время(2can)'),
            'device_id' => t('ID устройства(2can)'),
            'fee' => t('Комиссия(2can)'),
            'merchant' => t('ID продавца(2can)'),
        );
    }
    
    /**
    * Выполняет действия, связанные с оплатой заказа через мобильное 
    * приложение и сервис 2can
    * 
    * @param \Shop\Model\Orm\Order $order - Заказ
    * @param array $custom_data - Данные от мобильного приложения
    * 
    * Пример:
    * "custom": {
        "payment": "100",
        "card_number": "554386** **** 4384",
        "amount": "960548",
        "customer_address": "",
        "description": "Оплата заказа №123",
        "imei": "969718021523581",
        "lat": "45.0393375",
        "lng": "38.9200281",
        "reason": "",
        "rrn_code": "633413178949",
        "slip": "Торговая точка: ReadyScript\nCумма: 1,00\nКомиссия: 0,00\nКарта: MasterCard **** **** **** 4384\nEMV App: MasterCard\nEMV AID: A0000000041010\n...",
        "status": "Проведен",
        "date_time": "Tue Nov 29 16:33:07 GMT+03:00 2016",
        "device_id": "null",
        "fee": "3",
        "merchant": "53758"
    }
    * 
    * @return void
    */
    public function onMobileAppPaymentRequest($order, $custom_data)
    {
        $toucan_custom_fields = $this->getToucanCustomFields();
        
        if ($not_exists_fields = array_diff_key($toucan_custom_fields, $custom_data)) {
            throw new \RS\Exception(t('Не переданы поля %0 в секции custom', array( implode(',', array_keys($not_exists_fields)) )));
        }
        
        $extra_data = array_intersect_key($custom_data, $toucan_custom_fields);
        unset($extra_data['slip']);
        
        $order->addExtraInfoLine(t('Информация о платеже 2can'), nl2br($custom_data['slip']), $extra_data, '2can_payment_info');
        
        $this->updateTransaction($order, $custom_data);
    }
    
    /**
    * Создаем транзакцию, связанную с заказом
    * 
    * @return void
    */
    protected function updateTransaction($order, $custom_data)
    {
        $transaction = self::getTransaction($custom_data['rrn_code']);
        
        $transaction['amount'] = $custom_data['amount']/100;
        $transaction['reason'] = $custom_data['description'];
        $transaction['payment'] = $order['payment'];
        if ($order['user_id']>0) {
            $transaction['user_id'] = $order['user_id'];
        }
        $transaction['extra_arr'] = array(
            'PaymentId' => $custom_data['payment'],
            'Card' => $custom_data['card_number']
        );
        $transaction['order_id'] = $order['id']; //Самое главное - привязываем транзакцию к заказу
            
        $transaction['sign'] = \Shop\Model\TransactionApi::getTransactionSign($transaction);
        $transaction->update();            
    }
    
    /**
    * Создает или загружает транзакцию по RRN
    * 
    * @param mixed $rrn
    */
    public static function getTransaction($rrn)
    {
        //Пытаемся сперва найти такую транзакцию.
        $transaction = \Shop\Model\Orm\Transaction::loadByWhere(array(
            'entity' => self::ENTITY_TOUCAN,
            'entity_id' => $rrn
        ));
        
        if (!$transaction['id']) {
            //Если нет, то создаем транзакцию
            $transaction['dateof'] = date('c');
            $transaction['personal_account'] = 0;
            $transaction['cost'] = 0;
            
            $transaction['entity'] = self::ENTITY_TOUCAN;
            $transaction['entity_id'] = $rrn;
            $transaction['status'] = \Shop\Model\Orm\Transaction::STATUS_NEW;

            $transaction->insert();
        }
        
        return $transaction;
    }
}
