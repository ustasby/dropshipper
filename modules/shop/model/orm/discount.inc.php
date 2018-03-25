<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

/**
* Скидочный купон
*/
class Discount extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_discount';
    
    protected
        $serialized_products_field = 'sproducts',
        $products_field = 'products';
        
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'products' => new Type\ArrayList(array(
                'description' => t('Продукты'),
                'template' => '%shop%/form/discount/products.tpl',
            )),            
            'code' => new Type\Varchar(array(
                'maxLength' => '50',
                'description' => t('Код'),
                'hint' => t('Данный код можно будет ввести в корзине и получить заданную скидку. Оставьте поле пустым, чтобы код был сгенерирован автоматически'),
                'Attr' => array(array('size' => '30')),
                'meVisible' => false,
            )),
            'descr' => new Type\Varchar(array(
                'maxLength' => '2000',
                'description' => t('Описание скидки'),
            )),            
            'active' => new Type\Integer(array(
                'maxLength' => '1',
                'description' => t('Включен'),
                'CheckboxView' => array('1', '0'),
            )),
            'sproducts' => new Type\Text(array(
                'description' => t('Список товаров, на которые распространяется скидка'),
                'visible' => false,
            )),
            'period' => new Type\Enum(array('timelimit', 'forever'), array(
                'template' => '%shop%/form/discount/period.tpl',
                'description' => t('Срок действия'),
                'listFromArray' => array(array(
                    'timelimit' => t('Ограничен по времени'),
                    'forever' => t('Вечный')
                ))
            )),
            'endtime' => new Type\Datetime(array(
                'description' => t('Время окончания действия скидки'),
                'visible' => false,
                'allowempty' => true,
            )),
            'min_order_price' => new Type\Decimal(array(
                'maxLength' => 20,
                'decimal' => 2,
                'description' => t('Минимальная сумма заказа')
            )),
            'discount' => new Type\Decimal(array(
                'template' => '%shop%/form/discount/discount.tpl',
                'maxLength' => 20,
                'decimal' => 2,
                'description' => t('Скидка'),
                'Attr' => array(array('size' => '8')),
                'checker' => array('chkEmpty', t('Укажите скидку'))
            )),
            'discount_type' => new Type\Enum(array('', '%'), array(
                'description' => t('Скидка указана в процентах или в базовой валюте?'),
                'listFromArray' => array(array(
                    '%' => '%'
                )),
                'visible' => false
            )),
            'round' => new Type\Integer(array(
                'description' => t('Округлять скидку до целых чисел?'),
                'maxLength' => 1,
                'checkboxView' => array(1,0)
            )),
            'uselimit' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Лимит использования, раз'),
                'hint' => t('Количество раз, которое можно использовать купон, 0 - неограниченно'),
                'Attr' => array(array('size' => '5')),
            )),
            'oneuserlimit' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Лимит использования одним пользователем, раз'),
                'hint' => t('Количество раз, которое можно использовать купон, 0 - неограниченно<br/>
                           Действует только для зарегистрированых пользователей.<br/>
                           Если пользователь не зарегистрирован, то будет выдано <br/>
                           сообщение о авторизации'),
                'Attr' => array(array('size' => '5')),
            )),
            'wasused' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Была использована, раз'),
                'Attr' => array(array('size' => '5')),
                'default' => 0,
                'allowempty' => false
            )),
            'makecount' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Сгенерировать купонов'),
                'runtime' => true,
                'visible' => false,
                'hint' => t('Сгенерировать указанное число купонов с теми же параметрами, но разными кодами'),
                'Attr' => array(array('size' => '4')),
            )),
        ))->addMultieditKey(array('products', 'endtime'));
        
        $this->addIndex(array('site_id', 'code'), self::INDEX_UNIQUE);
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    * @return boolean
    */
    function beforeWrite($flag)
    {
        if(empty($this['code'])) {
            $this['code'] = $this->generateCode();
        }
        $this[$this->serialized_products_field] = serialize($this[$this->products_field]);
        return true;
    }
    
    /**
    * Генерирует код купона
    * 
    * @return string
    */
    function generateCode()
    {
        $config = \RS\Config\Loader::byModule($this);
        return \RS\Helper\Tools::generatePassword($config['discount_code_len'], 'abcdefghkmnpqrstuvwxyz123456789');
    }    
    
    function afterObjectLoad()
    {        
        if (!empty($this[$this->serialized_products_field]) && $unserialize = unserialize($this[$this->serialized_products_field])) {
            $this[$this->products_field] = $unserialize;
        }
    }
    
    /**
    * Возвращает объект, с помошью которого можно визуализировать выбор товаров
    * 
    * @return Catalog\Model\ProductDialog
    */
    function getProductDialog()
    {
        return new \Catalog\Model\ProductDialog('products', false, $this['products']);
    }
    
    /**
    * Возвращает true, если активен, иначе - текст ошибки
    */
    function isActive()
    {
        //Скидка считается активной, если:
        //Она включена, срок действия еще не истек, количество использвания - не истекло.
        if ($this['active'] == 0) return t('Скидка не активна');
        if ($this['period'] == 'timelimit' && $this['endtime'] < date('Y-m-d H:i:s')) return t('Срок действия скидки истек');
        if ($this['uselimit'] && ($this['wasused'] >= $this['uselimit'])) return t('Достигнут лимит использования скидки');
        return true;
    }
    
    /**
    * Возвращает true, если купон распространяется на все товары
    */
    function isForAll()
    {
        return empty($this['products']) || @in_array('0', (array)$this['products']['group']);
    }
    
    /**
    * Возвращает сумму минимального заказа, к которому может быть прикрепленн купон
    * 
    * @return float
    */
    function getMinOrderPrice()
    {
        return $this['min_order_price'];
    }
    
    /**
    * Увеличивает в базе счетчик использования на 1
    */
    function incrementUse()
    {
        $q = \RS\Orm\Request::make()
            ->update($this)
            ->set('wasused = wasused + 1')
            ->where("id = '#id'", array('id' => $this['id']))
            ->exec();
    }
    
    
    /**
    * Возвращает сумму скидки на цену $price
    *
    * @param float $price - сумма
    * @param bool $price_in_base_currency - если true, значит price передана в базовой валюте
    */
    function getDiscountValue($price, $use_currency)
    {
        //Определяем сколько вычитать.
        if ($this['discount_type'] == '%') {
            $delta = ($price * $this['discount']/100);
        } else {
            $delta = $this['discount'];
            if ($use_currency) {
                $delta = \Catalog\Model\CurrencyApi::applyCurrency($delta);
            }
        }
        if ($this['round']) {
            $delta = round($delta);
        }
        
        return $delta;
    }
    
    /**
    * Возвращает величину скидки, отформатированную для отображения (всегда с учетом текущей валюты)
    * 
    * @return string
    */
    function getDiscountTextValue()
    {
        if ($this['discount_type'] == '%') {
            $discount = (float)$this['discount']."%";
        } else {
            $discount =  \Catalog\Model\CurrencyApi::applyCurrency( $this['discount'] );
            $discount =  \RS\Helper\CustomView::cost($discount).' '.\Catalog\Model\CurrencyApi::getCurrentCurrency()->stitle;
        }
        return $discount;
    }
    
    /**
    * Возвращает клонированный объект купона
    * @return Discount
    */
    function cloneSelf()
    {
        $clone = parent::cloneSelf();
        unset($clone['wasused']);
        return $clone;
    }    

}