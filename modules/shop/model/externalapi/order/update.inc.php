<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\ExternalApi\Order;
use \ExternalApi\Model\Exception as ApiException;
use \Shop\Model\Orm;
use \Shop\Model\DeliveryApi;

/**
* Сохраняет изменения в заказе
*/
class Update extends \ExternalApi\Model\AbstractMethods\AbstractUpdate
{
    const
        RIGHT_UPDATE = 1,
        RIGHT_UPDATE_COURIER = 2;
        
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_UPDATE => t('Изменение заказа'),
            self::RIGHT_UPDATE_COURIER => t('Изменение заказа курьером')
        );
    }
    
    /**
    * Возвращает список прав, требуемых для запуска метода API
    * По умолчанию для запуска метода нужны все права, что присутствуют в методе
    * 
    * @return [код1, код2, ...]
    */
    public function getRunRights()
    {
        return array(self::RIGHT_UPDATE);
    }    
    
    /**
    * Возвращает допустимую структуру значений в переменной data, в которой будут содержаться сведения для обновления
    * 
    * @return array
    */
    public function getUpdateDataScheme()
    {
        return array(
            'fields' => array(
                'status' => array(
                    '@title' => t('ID статуса'),
                    '@type' => 'integer',
                    '@validate_callback' => function($value) {
                        $status_api = new \Shop\Model\UserStatusApi();
                        return $status_api->getOneItem($value) !== false;
                    }
                ),
                'payment' => array(
                    '@title' => t('ID способа оплаты'),
                    '@type' => 'integer',
                    '@validate_callback' => function($value) {
                        $payment_api = new \Shop\Model\PaymentApi();
                        return $payment_api->getOneItem($value) !== false;
                    }
                ),
                'is_payed' => array(
                    '@title' => t('Флаг оплаты'),
                    '@type' => 'integer',
                    '@allowable_values' => array(1,0)
                ),
                'courier_id' => array(
                    '@title' => t('ID курьера'),
                    '@type' => 'integer',
                    '@validate_callback' => function($value) {
                        $couriers = DeliveryApi::getCourierList();
                        return $value == 0 || isset($couriers[$value]);
                    }
                ),
                'admin_comments' => array(
                    '@title' => t('Комментарий администратора'),
                    '@type' => 'string'
                )
            ),
            'remove_items' => array(
                '@title' => t('Уникальные коды удаляемых из заказа товаров'),
                '@type' => 'array',
                '@arrayitemtype' => 'string',
            )
        );
    }
    
    /**
    * Возвращает объект, который необходимо обновить
    * 
    * @return \Shop\Model\Orm\Order
    */
    public function getOrmObject()
    {
        return new Orm\Order();
    }
    
    /**
    * Обновляет данные
    * 
    * @param \Shop\Model\Orm\Order $orm_object
    * @param array $data
    */
    public function updateData($orm_object, $data)
    {
        if (!empty($data['fields']['is_payed'])) {
            $payment_class = $orm_object->getPayment()->getTypeObject()->getShortName();
            if ($payment_class == 'toucan') {
                $this->onToucanPayment($orm_object);
            }
            
            if ($success_status = $orm_object->getPayment()->success_status) {
                //Изменяем статус заказа согласно настройке способа оплаты при установке флага оплаты
                $orm_object['status'] = $success_status;
            }
        }
        
        if (isset($data['remove_items'])) {

            if ($this->checkAccessError(self::RIGHT_UPDATE_COURIER) === false){
              $config = \RS\Config\Loader::byModule('shop');
              if  ($config['ban_courier_del'] == 1){
                  goto end;   // Костыль пока не можем словить    throw new ApiException(t('Курьер не может корректировать товары в заказе'), ApiException::ERROR_METHOD_ACCESS_DENIED);
              }
            }

            //Удаляем элементы из заказа
            $cart = $orm_object->getCart();
            $items = $cart->getItems();
            foreach($items as $key => $item_data) {
                //Оставим только товары и купоны на скидку
                if ($item_data['type'] !== \Shop\Model\Cart::TYPE_PRODUCT 
                    && $item_data['type'] !== \Shop\Model\Cart::TYPE_COUPON) 
                {
                    unset($items[$key]);
                } else {
                    //Исключим сведения о многомерных комплектациях, чтобы не обновлять их
                    unset($items[$key]['multioffers']); 
                }
            }
            
            foreach($data['remove_items'] as $uniq) {
                
                if (!isset($items[$uniq]) || $items[$uniq]['type'] != \Shop\Model\Cart::TYPE_PRODUCT) {
                    throw new ApiException(t("Товара с таким уникальным ключем '%0' не существует", array($uniq)), ApiException::ERROR_WRONG_PARAM_VALUE);
                }
                
                unset($items[$uniq]); //Удалим указанные элементы
            }
            
            $cart->updateOrderItems($items);

        }
        end:
        parent::updateData($orm_object, $data);
        
        if (isset($cart)) {
            $cart->saveOrderData();
        }
    }
    
    /**
    * Загружает объект из БД по ID
    * 
    * @param \RS\Orm\AbstractObject $orm_object
    * @param integer $object_id
    * 
    * @throws \ExternalApi\Model\Exception
    * @return void
    */
    protected function loadObject($orm_object, $object_id)
    {
        //Загружаем объект
        if (!$orm_object->load($object_id)) {
            throw new ApiException(t('Объект с таким ID не найден'), ApiException::ERROR_WRONG_PARAM_VALUE);
        }
        
        //Если это курьер
        if ($this->checkAccessError(self::RIGHT_UPDATE_COURIER) === false) {
            if ($orm_object['courier_id'] != $this->token['user_id']) {
                throw new ApiException(t('Курьер не может корректировать данный заказ'), ApiException::ERROR_METHOD_ACCESS_DENIED);
            }
        }        
    }    
    
    /**
    * Вызывается в случае, если оплата происходит через сервис 2can.
    * Добавляет сведения о транзакции к заказу
    * 
    * @param \Shop\Model\Orm\Order $orm_object
    * @param mixed $data
    * @return void
    */
    protected function onToucanPayment($orm_object)
    {
        if (!isset($this->params['custom'])) {
            throw new ApiException(t('Не переданы подробности трнзакции в секции custom'), ApiException::ERROR_WRONG_PARAM_VALUE);
        }
        
        $custom_data = $this->params['custom'];
        
        try {
            $toucan_api = new \Shop\Model\ToucanApi();
            $toucan_api->onMobileAppPaymentRequest($orm_object, $custom_data);
        } catch (\RS\Exception $e) {
            throw new ApiException($e->getMessage(), ApiException::ERROR_WRONG_PARAM_VALUE);
        }
    }    
    
    /**
    * Обновляет заказ
    * 
    * @param string $token Авторизационный токен
    * @param integer $order_id ID заказа
    * @param array $data Данные для обновления заказа #data-info
    * 
    * @example POST /api/methods/order.update
    * token=b9574d1b793605e3ff86dcc6413efd4f330ee943&order_id=159&data[fields][status]=4&data[remove_items][]=40f3d3da3e&data[remove_items][]=70f2d3ga3e
    * 
    * Ответ:
    * <pre>
    * {
    *     "response": {
    *         "success": true
    *     }
    * }
    * </pre>
    * 
    * @return array Возвращает флаг успешного выполнения success = true
    */
    protected function process($token, $order_id, $data)
    {
        return parent::process($token, $order_id, $data);
    }
}
