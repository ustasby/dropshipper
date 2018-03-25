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
* Позиция в корзине
*/
class OrderItem extends \RS\Orm\AbstractObject
{
    const
        TYPE_PRODUCT        = 'product',
        TYPE_COUPON         = 'coupon',
        TYPE_COMMISSION     = 'commission',
        TYPE_TAX            = 'tax',
        TYPE_DELIVERY       = 'delivery',
        TYPE_ORDER_DISCOUNT = 'order_discount',
        TYPE_SUBTOTAL       = 'subtotal';
        
    protected static
        $table = 'order_items';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'order_id' => new Type\Integer(array(
                'description' => t('ID заказа'),
            )),
            'uniq' => new Type\Varchar(array(
                'maxLength' => 10,
                'description' => t('ID в рамках одной корзины')
            )), 
            'type' => new Type\Enum(array(
                self::TYPE_PRODUCT,
                self::TYPE_COUPON,
                self::TYPE_COMMISSION,
                self::TYPE_TAX,
                self::TYPE_DELIVERY,
                self::TYPE_ORDER_DISCOUNT,
                self::TYPE_SUBTOTAL), array(
                'description' => t('Тип записи товар, услуга, скидочный купон'),
                'index' => true
            )),
            'entity_id' => new Type\Varchar(array(
                'description' => t('ID объекта type'),
                'maxLength' => 50
            )),
            'multioffers' => new Type\Text(array(
                'description' => t('Многомерные комплектации')
            )),
            'offer' => new Type\Integer(array(
                'description' => t('Комплектация')
            )),
            'amount' => new Type\Decimal(array(
                'description' => t('Количество'),
                'maxLength' => 11,
                'decimal' => 3,
                'default' => 1
            )),
            'barcode' => new Type\Varchar(array(
                'description' => t('Артикул'),
                'maxLength' => 100,
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Название')
            )),
            'model' => new Type\Varchar(array(
                'description' => t('Модель')
            )),
            'single_weight' => new Type\Double(array(
                'description' => t('Вес')
            )),
            'single_cost' => new Type\Decimal(array(
                'description' => t('Цена за единицу продукции'),
                'maxlength' => 20,
                'decimal' => 2
            )),
            'price' => new Type\Decimal(array(
                'description' => t('Стоимость'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'profit' => new Type\Decimal(array(
                'description' => t('Доход'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'discount' => new Type\Decimal(array(
                'description' => t('Скидка'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок')
            )),
            'extra' => new Type\Text(array(
                'description' => t('Дополнительные сведения'),
                'appVisible' => false
            )),
            'data' => new Type\ArrayList()
        ));
        
        $this->addIndex(array('order_id', 'uniq'), self::INDEX_PRIMARY);   
        $this->addIndex(array('type', 'entity_id'), self::INDEX_KEY);
    }
    
    function getPrimaryKeyProperty()
    {
        return array('order_id', 'uniq');
    }
    
    function afterObjectLoad()
    {
        @$this['data'] = unserialize($this['extra']);
        
        // Приведение типов
        $this['amount'] = (float)$this['amount'];
    }
    
    function beforeWrite($flag)
    {
        if (!$this->isModified('profit')) {
            $this['profit'] = $this->getProfit();
        }
    }
    
    function getExtraParam($key, $default = null)
    {
        @$data = unserialize($this['extra']);
        return isset($data[$key]) ? $data[$key] : $default;
    }
    
    /**
    * Возвращает массив с названиями и выбранными значениями многомерных комплектаций
    * 
    * @return array
    */
    function getMultiOfferTitles()
    {
        $multioffers = @unserialize($this['multioffers']);
        return $multioffers ?: array(); 
    }

    /**
     * Возвращает шаг изменения количества товара. Если это не товар, то возвращает false
     *
     * @return float|false
     */
    function getProductAmountStep()
    {
        if ($this['type'] == self::TYPE_PRODUCT){
            $product = new \Catalog\Model\Orm\Product($this['entity_id']);
            return $product->getAmountStep();
        }
        return false;
    }

    /**
     * Возвращает доход, от продажи товара в базовой валюте
     * Возвращает null, в случае если невозможно рассчитать доход для записи
     *
     * @return double|null
     * @throws \RS\Exception
     */
    function getProfit()
    {
        $config = \RS\Config\Loader::byModule($this);
        if ($this['type'] == self::TYPE_PRODUCT && $config->source_cost) {
            $product = new \Catalog\Model\Orm\Product($this['entity_id']);
            
            if ($product['id']) {
                $sell_price = $this['price'] - $this['discount'];
                $source_cost = $product->getCost($config->source_cost, $this['offer'], false, true);
                return $sell_price - ($source_cost * $this['amount']);
            }
        }
        return null;
    }
}

