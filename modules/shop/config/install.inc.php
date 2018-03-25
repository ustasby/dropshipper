<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Config;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends \RS\Module\AbstractInstall
{
    function install()
    {
        $result = parent::install();
        if ($result) {
            //Вставляем в таблицы данные по-умолчанию, в рамках нового сайта, вызывая принудительно обработчик события
            \Shop\Config\Handlers::onSiteCreate(array(
                'orm' => \RS\Site\Manager::getSite(),
                'flag' => \RS\Orm\AbstractObject::INSERT_FLAG
            ));
            
            //Обновляем структуру базы данных для объекта Товар
            $product = new \Catalog\Model\Orm\Product();
            $product->dbUpdate();
            
            //Обновляем структуру базы данных для объекта Категория Товара
            $dir = new \Catalog\Model\Orm\Dir();
            $dir->dbUpdate();
            
            //Добавляем виджеты на рабочий стол
            $widget_api = new \Main\Model\Widgets();
            $widget_api->setUserId(1);
            $widget_api->insertWidget('shop-widget-sellchart', 2);
            $widget_api->insertWidget('shop-widget-lastorders', 1);
            $widget_api->insertWidget('shop-widget-orderstatuses', 1);
            $widget_api->insertWidget('shop-widget-reservation', 3);
        }
        return $result;
    }  
    
    
    /**
    * Добавляет демонстрационные данные
    * 
    * @param array $params - произвольные параметры. 
    * @return boolean|array
    */
    function insertDemoData($params = array())
    {
        return $this->importCsvFiles(array(
            array('\Shop\Model\CsvSchema\Delivery', 'delivery'),
            array('\Shop\Model\CsvSchema\Payment', 'payments'),
         ), 'utf-8', $params);
    }
    
    /**
    * Возвращает true, если модуль может вставить демонстрационные данные
    * 
    * @return bool
    */
    function canInsertDemoData()
    {
        return true;
    }

    /**
     * Устанавливает демонстрационные заказы, если была выбрана опция установки демо-данных.
     * Выполняется только при первичной установке платформы
     *
     * @param array $options
     * @return bool
     */
    function deferredAfterInstall($options)
    {
        if (!empty($options['set_demo_data'])) {

            //Устанавливаем демо заказы
            $this->checkoutOrder(array(
                '509-080-204-030' => '',
                '10-106-070-204-090' => '',
                '786-774-032-691' => '',
                '10-309-080-105-050' => ''
            ), t('Самовывоз'), t('Безналичный расчет'), 'waitforpay');

            $this->checkoutOrder(array(
                'LO790EWBCK91' => 'XS',
                'SA004EWANW76' => '42',
                'SI954EWBDA89' => '40',
                'CA011EWALS32' => '42'
            ), t('Самовывоз'), t('Квитанция банка'), 'inprogress');

            $this->checkoutOrder(array(
                '64155-5RXQOAO' => '',
                '11852-72CSZ2O' => '',
                '27776-PD6GX4J' => ''
            ), t('Самовывоз'), t('Квитанция банка'), 'success');

            $this->checkoutOrder(array(
                'DIAMOND140-0' => '',
                'ALI-W-36-1' => '',
                '81006041' => '',
                '10531012' => '',
                '400742BL40-3' => ''
            ), t('Самовывоз'), t('Безналичный расчет'), 'cancelled');
        }

        return true;
    }

    /**
     * Оформляет заказ
     */
    private function checkoutOrder($products, $delivery, $payment, $status)
    {
        $user_id = 1;
        $cart = \Shop\Model\Cart::currentCart();
        $cart->clean();

        foreach($products as $code => $offer_title) {
            $product = \Catalog\Model\Orm\Product::loadByWhere(array(
                'barcode' => $code
            ));

            if ($product['id']) {
                $offer = 0;
                if ($offer_title != '') {
                    $offer = \RS\Orm\Request::make()
                        ->select('sortn')
                        ->from(new \Catalog\Model\Orm\Offer)
                        ->where(array(
                            'product_id' => $product['id'],
                            'title' => $offer_title
                        ))->exec()->getOneField('sortn', 0);
                }
                $cart->addProduct($product['id'], 1, $offer);
            }
        }

        //Создаем адрес
        $address = \Shop\Model\Orm\Address::loadByWhere(array(
            'user_id' => $user_id
        ));

        if (!$address['id']) {
            $address['user_id'] = $user_id;
            $address['zipcode'] = t('350000');
            $address['country'] = t('Россия');
            $address['region'] = t('Краснодарский край');
            $address['city'] = t('Краснодар');
            $address['address'] = t('ул. Красная, 100, кв. 35');
            $address['region_id'] = 13;
            $address['country_id'] = 1;
            $address->insert();
        }

        $delivery_obj = \Shop\Model\Orm\Delivery::loadByWhere(array(
            'title' => $delivery
        ));

        $payment_obj = \Shop\Model\Orm\Delivery::loadByWhere(array(
            'title' => $payment
        ));

        $order = new \Shop\Model\Orm\Order();
        $order->linkSessionCart($cart);
        $order->setCurrency( \Catalog\Model\CurrencyApi::getCurrentCurrency() );
        $order['user_id'] = $user_id;
        $order['warehouse'] = 1;
        $order['ip'] = '';
        $order['contact_person'] = t('Федор Васильевич Иванов');
        $order['use_addr'] = $address['id'];
        $order['delivery'] = $delivery_obj['id'];
        $order['payment'] = $payment_obj['id'];
        $order['disable_checkout_notice'] = true;

        if ($order->insert()) {
            $order['notify_user'] = false;
            $order['status'] = \Shop\Model\UserStatusApi::getStatusIdByType($status);
            $order->update();
        }
    }
}
