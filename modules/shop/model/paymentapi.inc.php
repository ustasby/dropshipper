<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;
use Shop\Model\Orm\Order;

/**
* API функции для работы со способами доставки для текущего сайта
*/
class PaymentApi extends \RS\Module\AbstractModel\EntityList
{
    protected static
        $types;

    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Payment,
        array(
            'nameField' => 'title',
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'sortField' => 'sortn'
        ));
    }
  
    /**
    * Возвращает Имеющиеся в системе обработчики типов доставок.
    * 
    * @return array
    */
    function getTypes()
    {
        if (self::$types === null) {
            $event_result = \RS\Event\Manager::fire('payment.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach($list as $payment_type_object) {
                if (!($payment_type_object instanceof PaymentType\AbstractType)) {
                    throw new PaymentType\Exception(t('Тип оплаты должен быть наследником \Shop\Model\PaymentType\AbstractType'));
                }
                self::$types[$payment_type_object->getShortName()] = $payment_type_object;
            }
        }
        
        return self::$types;
    }
    
    /**
    * Возвращает массив ключ => название типа доставки
    * 
    * @return array
    */
    public static function getTypesAssoc()
    {
        $_this = new self();
        $result = array();
        foreach($_this->getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }
    
    /**
    * Возвращает объект типа доставки по идентификатору
    * 
    * @param string $name
    */
    public static function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : new PaymentType\Stub($name);
    }



    /**
     * Возвращает список оплат
     *
     * @deprecated Метод будет удален. Используйте вместо него getCheckoutPaymentList
     *
     * @param \Shop\Model\Orm\Order $order_obj - объект заказа
     * @param integer $page - номер страницы
     * @param integer $pageSize - количество элементов на страницу
     * @param string $order - условие сортировки
     * @return array of objects
     */
    function getPaymentsList($order_obj, $page = null, $page_size = null, $order = null)
    {
        $this->setPage($page, $page_size);
        $this->setOrder($order);
        $pay_list = (array)$this->q->objects($this->obj);

        $delivery_id = $order_obj['delivery'];

        foreach ($pay_list as $k=>$pay_item) {  //Перевод оплат
            if (is_array($pay_item['delivery']) && !empty($pay_item['delivery']) && !in_array(0,$pay_item['delivery'])) { //Если есть прявязанные доставки
                if (!in_array($delivery_id, $pay_item['delivery'])) {
                    unset($pay_list[$k]);
                }
            }
        }

        return $pay_list;
    }


    /**
     * Возвращает оплаты, которые необходимо отобразить на этапе
     * оформления заказа
     *
     * @param \Users\Model\Orm\User
     * @param \Shop\Model\Orm\Order $order
     * @return \Shop\Model\Orm\Payment[]
     */
    public function getCheckoutPaymentList($user, $order)
    {
        $my_type = $user['is_company'] ? 'company' : 'user';

        $this->setFilter('public', 1);
        $this->setFilter('user_type', array('all', $my_type), 'in');
        $this->setFilter('target', array('all', 'orders'), 'in');
        $this->setGroup('id');

        $delivery_id = $order['delivery'];

        if ($order['user_type'] == Order::USER_TYPE_NOREGISTER) {
            $this->setFilter('class', 'personalaccount', '!=');
        }

        $payment_list = $this->getList();

        foreach ($payment_list as $k => $pay_item) {  //Перевод оплат
            if (is_array($pay_item['delivery']) && !empty($pay_item['delivery']) && !in_array(0, $pay_item['delivery'])) { //Если есть прявязанные доставки
                if (!in_array($delivery_id, $pay_item['delivery'])) {
                    unset($payment_list[$k]);
                }
            }
        }

        // Событие для модификации списка оплат
        $result = \RS\Event\Manager::fire('checkout.payment.list', array(
            'list' => $payment_list,
            'order' => $order,
            'user' => $user
        ));
        list($payment_list) = $result->extract();

        return $payment_list;
    }

    /**
     * Возвращает ассоциативный массив с ID и названиями оплат
     *
     * @param array $root - произвольный набор элементов, который будет помещен вначало
     * @return array
     */
    public static function staticSelectList($root = array())
    {
        $list = parent::staticSelectList();
        return $root + $list;
    }

}