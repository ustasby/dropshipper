<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type,
    \Shop\Model\Orm\UserStatus,
    \Shop\Model\UserStatusApi;


/**
* Способ оплаты текущего сайта, присутствующий в списке выбора при оформлении заказа. 
* Содержит связь с модулем процессинга.
*/
class Payment extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_payment';
    
    protected
        $cache_payment,
        $order,
        $transaction;
    
    function __construct($id = null, $cache = true, Order $order = null, Transaction $transaction = null)
    {
        parent::__construct($id, $cache);
        $this->order        = $order;
        $this->transaction  = $transaction;
    }
        
    function _init()
    {
        parent::_init()->append(array(
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Название'),
                )),
                'admin_suffix' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Пояснение'),
                    'hint' => t('Отображается только в диалогах административной части<br>
                                    используйте если у вас есть доставки с одинаковым названем')
                )),
                'description' => new Type\Text(array(
                    'description' => t('Описание'),
                )),
                'picture' => new Type\Image(array(
                    'max_file_size' => 10000000,
                    'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
                    'description' => t('Логотип'),
                )),                
                'first_status' => new Type\Integer(array(
                    'description' => t('Стартовый статус заказа'),
                    'list' => array(array(__CLASS__, 'getFirstStatusList'))
                )),
                'success_status' => new Type\Integer(array(
                    'description' => t('Статус заказа в случае успешной оплаты'),
                    'list' => array(array(__CLASS__, 'getSuccessStatusList'))
                )),                
                'user_type' => new Type\Enum(array('all', 'user', 'company'), array(
                    'allowEmpty' => false,
                    'description' => t('Категория пользователей для данного типа оплаты'),
                    'listFromArray' => array(array(
                        'all' => t('Все'),
                        'user' => t('Физические лица'),
                        'company' => t('Юридические лица')
                    ))
                )),               
                'target' => new Type\Enum(array('all', 'orders', 'refill'), array(
                    'allowEmpty' => false,
                    'description' => t('Область применения'),
                    'listFromArray' => array(array(
                        'all' => t('Везде'),
                        'orders' => t('Оплата заказов'),
                        'refill' => t('Пополнение баланса')
                    ))
                )),     
                'delivery' => new Type\ArrayList(array(
                    'description' => t('Связь с доставками'),
                    'hint' => t('Если не указно, то способ оплаты будет отображен при выборе любого способа доставки'),
                    'list' => array(array('\Shop\Model\DeliveryApi', 'getListForPayment')),
                    'attr' => array(array(
                        'size' => 10,
                        'multiple' => true,
                    )),
                )),  
                '_delivery' => new Type\Varchar(array(
                    'maxLength' => '1500',
                    'description' => t('Связь с доставками'),
                    'visible' => false
                )),          
                'public' => new Type\Integer(array(
                    'description' => t('Публичный'),
                    'maxLength' => 1,
                    'default' => 1,
                    'checkboxView' => array(1,0)
                )),     
                'default_payment' => new Type\Integer(array(
                    'description' => t('Оплата по-умолчанию?'),
                    'hint' => t('Если включено, то будет выбрано при оформлении заказа'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxView' => array(1,0)
                )),
                'commission' => new Type\Real(array(
                    'description' => t('Комиссия за оплату в %'),
                    'hint' => t('Если 0 или ничего, то учитываться не будет'),
                    'maxLength' => 11,
                    'decimal' => 4,
                    'default' => 0,
                )),
                'commission_include_delivery' => new Type\Integer(array(
                    'description' => t('Включать стоимость доставки в комиссию'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxView' => array(1,0)
                )),
                'commission_as_product_discount' => new Type\Integer(array(
                    'description' => t('Присваивать комиссию в качестве скидки к товарам'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxView' => array(1,0),
                    'hitn' => t('При включении опция "Включать стоимость доставки в комиссию" становится недействительной')
                )),
                'create_cash_receipt' => new Type\Integer(array(
                    'description' => t('Выбить чек после оплаты?'),
                    'hint' => t('Если флаг установлен, то при записи заказа после его оплаты или возрата будет отправлен запрос к Вашей подключённой кассе'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxView' => array(1,0)
                )),
                'class' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Расчетный класс (тип оплаты)'),
                    'template' => '%shop%/form/payment/other.tpl',
                    'list' => array(array('\Shop\Model\PaymentApi', 'getTypesAssoc'))
                )),
                '_serialized' => new Type\Text(array(
                    'description' => t('Параметры рассчетного класса'),
                    'visible' => false,
                )),
                'data' => new Type\ArrayList(array(
                    'visible' => false
                )),
                'sortn' => new Type\Integer(array(
                    'maxLength' => '11',
                    'allowEmpty' => true,
                    'description' => t('Сорт. индекс'),
                    'visible' => false,
                )),
            ));
    }
    
    
    public static function getFirstStatusList()
    {
        $userstatus_api = new \Shop\Model\UserStatusApi();
        $list = $userstatus_api->getSelectList();
        return array(0 => t('По умолчанию (как в настройках модуля Магазин)')) + $list;
    }
    
    public static function getSuccessStatusList()
    {
        $userstatus_api = new \Shop\Model\UserStatusApi();
        $list = $userstatus_api->getSelectList();
        return array(0 => t('Не изменять')) + $list;        
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as max')
                ->from($this)
                ->where(array(
                    'site_id' => $this->__site_id->get()
                ))
                ->exec()->getOneField('max', 0) + 1;
        }
        $this['_serialized'] = serialize($this['data']);
        
        if (!empty($this['delivery'])){
            if (in_array(0,$this['delivery'])){
               $this['delivery'] = array(0); 
            }
        }
        $this['_delivery']   = serialize($this['delivery']);
    }
    
    function afterObjectLoad()
    {
        $this['data']     = @unserialize($this['_serialized']);
        $this['delivery'] = @unserialize($this['_delivery']);
    }
    
    /**
    * Возвращает объект расчетного класса (типа оплаты)
    * 
    * @return \Shop\Model\PaymentType\AbstractType | false
    */
    function getTypeObject()
    {
        if ($this->cache_payment === null) 
        {
            $this->cache_payment = clone \Shop\Model\PaymentApi::getTypeByShortName($this['class']);
            $this->cache_payment->loadOptions((array)$this['data'], $this->order, $this->transaction);
        }
        
        return $this->cache_payment;
    }
    
    
    /**
    * Возвращает true, если тип оплаты готов отобразить документы на оплату
    * 
    * @return bool
    */
    final function hasDocs()
    {
        return in_array($this->order['status'], UserStatusApi::getStatusesIdByType(UserStatus::STATUS_WAITFORPAY)) && $this->getTypeObject()->getDocsName();
    }
    
    /**
    * Возвращает объект компании(с реквизитами), которая поставляет услуги для данного типа оплаты
    * 
    * @return \Shop\Model
    */
    function getShopCompany()
    {
        if (($company = $this->getTypeObject()->getCompany()) == false) {
            $company = new Company();
            $company->getFromArray( \RS\Config\Loader::getSiteConfig($this['site_id'])->getValues() );            
        }
        return $company;
    }    
    
    /**
    * Возвращает клонированный объект оплаты
    * @return Payment
    */
    function cloneSelf()
    {
        /**
        * @var \Shop\Model\Orm\Payment
        */
        $clone = parent::cloneSelf();

        //Клонируем фото, если нужно
        if ($clone['picture']){
           /**
           * @var \RS\Orm\Type\Image
           */
           $clone['picture'] = $clone->__picture->addFromUrl($clone->__picture->getFullPath());
        }
        return $clone;
    }

    /**
     * Возвращает true, если в системе включено пробитие чеков и текущий способ оплаты это поддерживает
     */
    function isReceiptEnabled()
    {
        $config = \RS\Config\Loader::byModule($this);
        return $this->create_cash_receipt && $config->cashregister_class;
    }

}

