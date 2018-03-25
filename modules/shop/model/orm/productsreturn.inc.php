<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use RS\Helper\Pdf\PDFGenerator;
use \RS\Orm\Type;
use Shop\Model\ProductsReturnApi;

/**
 * ORM объект документа на возврат на заказа
 * @package Shop\Model\Orm
 */
class ProductsReturn extends \RS\Orm\OrmObject
{
    protected static $table = 'order_products_return';

    const
        STATUS_NEW         = 'new',
        STATUS_IN_PROGRESS = 'in_progress',
        STATUS_COMPLETE    = 'complete',
        STATUS_REFUSE      = 'refused';

    function _init() //инициализация полей класса. конструктор метаданных
    {
        return parent::_init()
            ->append(array(
                t('Основные данные'),
                    'site_id' => new Type\CurrentSite(),
                    'return_num' => new Type\Varchar(array(
                        'maxLength' => '20',
                        'description' => t('Номер возврата'),
                        'visible' => false,
                        'unique' => true
                    )),
                    'order_id' => new Type\Integer(array(
                        'maxLength' => '20',
                        'description' => t('Id заказа'),
                        'index' => true,
                        'hidden' => true,
                    )),
                    'user_id' => new Type\User(array(
                        'allowEmpty' => false,
                        'maxLength' => '11',
                        'attr' => array(array(
                            'data-autocomplete-body' => '1'
                        )),
                        'description' => t('ID пользователя'),
                        'hidden' => true,
                    )),
                    'status' => new Type\Enum(
                        array(
                            self::STATUS_NEW,
                            self::STATUS_IN_PROGRESS,
                            self::STATUS_COMPLETE,
                            self::STATUS_REFUSE,
                        ),
                        array(
                            'allowEmpty'    => false,
                            'description'   => t('Статус возврата'),
                            'listFromArray' => array(array(
                                self::STATUS_NEW         => t('Новый'),
                                self::STATUS_IN_PROGRESS => t('В процессе'),
                                self::STATUS_COMPLETE    => t('Завершено'),
                                self::STATUS_REFUSE      => t('Отклонено')
                            ))
                        )),
                    'name' => new Type\Varchar(array(
                        'description' => t('Имя пользователя'),
                        'checker' => array ('chkEmpty', 'Имя - обязательное поле')
                        )),
                    'surname' => new Type\Varchar(array(
                        'description' => t('Фамилия пользователя'),
                        'checker' => array ('chkEmpty', 'фамилия - обязательное поле')
                    )),
                    'midname' => new Type\Varchar(array(
                        'description' => t('Отчество пользователя'),
                        'checker' => array ('chkEmpty', 'Отчество - обязательное поле')
                    )),
                    'passport_series' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'description' => t('Серия паспорта'),
                        'checker' => array ('chkEmpty', 'Серия паспорта - обязательное поле')
                    )),
                    'passport_number' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'description' => t('Номер паспорта'),
                        'checker' => array ('chkEmpty', 'Номер паспорта - обязательное поле')
                    )),
                    'passport_issued_by' => new Type\Varchar(array(
                        'maxLength' => '100',
                        'description' => t('Кем выдан паспорт'),
                        'checker' => array ('chkEmpty', 'Кем выдан паспорт - обязательное поле')
                    )),
                    'phone' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'description' => t('Номер телефона'),
                        'checker' => array ('chkEmpty', 'Номер телефона - обязательное поле')
                    )),
                    'dateof' => new Type\Datetime(array(
                        'description' => t('Дата оформления возврата'),
                        'index' => true,
                        'checker' => array ('chkEmpty', 'Дата оформления возврата - обязательное поле')
                    )),
                    'date_exec' => new Type\Datetime(array(
                        'description' => t('Дата выполнения возврата'),
                    )),
                    'return_items' => new Type\ArrayList(array(
                        'maxLength' => '200',
                        'description' => t('Список товаров на возврат'),
                        'visible' => false,
                    )),
                    'return_reason' => new Type\Varchar(array(
                        'maxLength' => '200',
                        'description' => t('Причина возврата'),
                        'checker' => array ('chkEmpty', 'Причина возврата - обязательное поле')
                    )),
                    'bank_name' => new Type\Varchar(array(
                        'description' => t('Название банка'),
                        'maxLength' => '100',
                    )),
                    'bik' => new Type\Varchar(array(
                        'description' => t('БИК'),
                        'maxLength' => '50',
                    )),
                    'bank_account' => new Type\Varchar(array(
                        'description' => t('Рассчетный счет'),
                        'maxLength' => '100',
                    )),
                    'correspondent_account' => new Type\Varchar(array(
                        'description' => t('Корреспондентский счет'),
                        'maxLength' => '100',
                    )),
                    'cost_total' => new Type\Decimal(array(
                        'description' => t('Сумма возврата'),
                        'visible' => false,
                    )),
                    'currency' => new Type\Varchar(array(
                        'description' => t('Id валюты'),
                        'maxLength' => '20',
                        'hidden' => true,
                    )),
                    'currency_ratio' => new Type\Decimal(array(
                        'description' => t('Курс на момент оформления заказа'),
                        'maxLength' => '20',
                        'hidden' => true,
                    )),
                    'currency_stitle' => new Type\Varchar(array(
                        'description' => t('Символ курса валюты'),
                        'maxLength' => '20',
                        'hidden' => true,
                    )),
                t('Товары на возврат'),
                    'chooseproducts' => new Type\UserTemplate('%shop%/form/productsreturn/returnproductselect.tpl', array(
                        'return_api' => new ProductsReturnApi()
                    )),
            ));

    }

    /**
     * Действия перед записью объекта
     *
     * @param string $flag - insert или update
     * @return bool|null
     * @throws \RS\Orm\Exception
     */
    function beforeWrite($flag)
    {
        $before_orm = new self($this['id']); //Предыдущая версия ORM
        $order_items = $this->getOrderData(false); //Товары заказа
        $items       = $this['return_items']; //Товары на возврат

        if (empty($items)){
            $this->addError(t('Укажите товары для возврата'));
            return false;
        }

        //если есть ошибки, не даем записать возврат
        if(!$this->checkItemsInOrder($order_items, $items)){
            $this->addError(t('Количество товаров для возврата превышает разрешенное, возможно Вы уже вернули часть заказа.'));
            return false;
        }

        //Посчитаем цену возврата
        $total_cost_return = 0;
        foreach ($items as $uniq=>$item){
            $total_cost_return += $order_items['items'][$uniq]['single_cost_with_discount'] * $item['amount'];
        }

        $this['cost_total'] = $total_cost_return;

        if (empty($this['return_num'])){
            $this['return_num'] = \RS\Helper\Tools::generatePassword(6, '0123456789');
        }

        if ($this['status'] == self::STATUS_COMPLETE && ($before_orm['status'] != self::STATUS_COMPLETE)){
            $this['date_exec'] = date('Y-m-d H:i:s');
        }
    }

    /**
     * Действия после записи объекта
     *
     * @param string $flag - insert или update
     * @throws \RS\Db\Exception
     */
    function afterWrite($flag)
    {
        //зазписываем return_item'ы в бд если возврат сформирован без ошибок
        $this->deleteReturnItems(); //Преварительно удалим, если такие товары были
        $items       = $this['return_items'];
        $order       = $this->getOrder();
        $order_items = $this->getOrderData();
        foreach ($items as $uniq => $item) {
            $product_return = new \Shop\Model\Orm\ProductsReturnOrderItem();
            $product_return->getFromArray($item);
            $product_return['return_id'] = $this['id'];
            $product_return['site_id']   = $order['site_id'];
            $product_return['uniq']      = $uniq;
            $product_return['title']     = $order_items['items'][$uniq]['cartitem']['title'];
            $product_return['model']     = $order_items['items'][$uniq]['cartitem']['model'];
            $product_return['amount']    = $item['amount'];
            $product_return['barcode']   = $order_items['items'][$uniq]['cartitem']['barcode'];
            $product_return['cost']      = $order_items['items'][$uniq]['single_cost_with_discount'];
            $product_return->save();
        }
    }

    /**
     * Заполняет поле return_items, исходя из отмеченных ранее товаров
     */
    function fillReturnItems()
    {
        if ($this['id']) {
            $data = $this->getReturnItems();
            $return_items = array();
            foreach($data as $uniq => $item) {
                $return_items[$uniq]['uniq'] = $uniq;
                $return_items[$uniq]['amount'] = $item['amount'];
            }

            $this['return_items'] = $return_items;
        }
    }

    /**
     * Удаление возврата товара
     *
     * @return bool
     * @throws \RS\Db\Exception
     */
    function delete()
    {
        $this->deleteReturnItems(); //удаляем return_item'ы при удалении возврата
        return parent::delete();
    }


    /**
     * Возвращает ФИО того кто хочет вернуть товары
     *
     * @return string
     */
    function getFio(){
        return $this['surname']." ".$this['name']." ".$this['midname'];
    }


    /**
     * Заполняет поля для создания возврата
     *
     */
    function preFillFields()
    {
        $order = $this->getOrder();
        $user  = $order->getUser();
        $this['user_id'] = $user['id'];
        $this['name']    = $user['name'];
        $this['surname'] = $user['surname'];
        $this['midname'] = $user['midname'];
        $this['phone']   = $user['phone'] ? $user['phone'] : $order['user_phone'];
        $this['dateof']  = date("Y-m-d H:i:s");

        $this['currency']        = $order['currency'];
        $this['currency_ratio']  = $order['currency_ratio'];
        $this['currency_stitle'] = $order['currency_stitle'];

    }

    /**
     * Проверяет количество товаров в заказе, чтобы не превышало допустимое количество
     *
     * @param array $order_items - массив товаров в конкретном заказе
     * @param array $return_items - массив товаров, которые нужно вернуть для данного возврата
     * @return bool
     * @throws \RS\Orm\Exception
     */
    function checkItemsInOrder($order_items, $return_items)
    {
        $result = true;
        //получаем все возвращенные товары заказа
        $api = new \Shop\Model\ProductsReturnApi();
        $all_returned_items_of_order = $api->getReturnItemsByOrder($this['order_id']);
        foreach ($return_items as $uniq => $return_item) {
            //количество одинаковых товаров
            $current_item_returned_amount = 0;
            foreach ($all_returned_items_of_order as $ret_uniq => $returned_item){
                // не учитываем товаров редактируемого возврата
                if (($uniq == $ret_uniq) && ($returned_item['return_id'] != $this['id'])){
                    $current_item_returned_amount += $returned_item['amount'];
                }
            }
            //если количество одинаковых товаров превышает оставшееся количество в заказе.

            if($return_item['amount'] > $order_items['items'][$uniq]['cartitem']['amount'] - $current_item_returned_amount){
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Удаляет товары которые предназначены на возврат
     *
     * @param null|integer $return_id
     * @throws \RS\Db\Exception
     */
    function deleteReturnItems($return_id = null)
    {
        if(!isset($return_id)){
            $return_id = $this['id'];
        }
        \RS\Orm\Request::make()
            ->delete()
            ->from(new \Shop\Model\Orm\ProductsReturnOrderItem())
            ->where(array(
                'return_id' => $return_id
            ))->exec();
    }

    /**
     * Возвращает массив товаров для возврата в рамках для данного возврата
     *
     * @return array
     * @throws \RS\Orm\Exception
     */
    function getReturnItems()
    {
        return \RS\Orm\Request::make()
                    ->from(new \Shop\Model\Orm\ProductsReturnOrderItem())
                    ->where(array(
                        'return_id' => $this['id']
                    ))
                    ->objects(null , 'uniq');
    }

    /**
     * Возвращает элементы из заказа
     *
     * @return array
     */
    function getOrderData($format = true){
        $order = new \Shop\Model\Orm\Order($this['order_id']);
        return $order->getCart()->getOrderData($format);
    }

    /**
     * Возвращает заказ которому принадлежит возврат
     *
     * @return \Shop\Model\Orm\Order
     */
    function getOrder(){
        return new \Shop\Model\Orm\Order($this['order_id']);
    }

    /**
     * Возвращает шаблон заявления на возврат товара в формате PDF
     *
     * @return string
     */
    function getPdfForm()
    {
        $template = \RS\Config\Loader::byModule($this)->return_print_form_tpl;
        $pdf_generator = new PDFGenerator();
        return $pdf_generator->renderTemplate($template, array(
            'return' => $this,
            'return_text_totalcost' => \RS\Helper\Tools::priceToString($this['cost_total']),
            'site_config' => \RS\Config\Loader::getSiteConfig()
        ));
    }
}