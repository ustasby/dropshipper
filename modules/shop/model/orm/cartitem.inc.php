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
class CartItem extends \RS\Orm\AbstractObject
{
    const
        //Тип
        TYPE_PRODUCT = 'product',
        TYPE_SERVICE = 'service',
        TYPE_COUPON  = 'coupon';

    protected static
        $table = 'cart';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'session_id' => new Type\Varchar(array(
                'description' => t('ID сессии'),
                'maxLength' => 32
            )),
            'uniq' => new Type\Varchar(array(
                'maxLength' => 10,
                'description' => t('ID в рамках одной корзины')
            )), 
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата добавления')
            )),
            'user_id' => new Type\Bigint(array(
                'description' => t('Пользователь')
            )),            
            'type' => new Type\Enum(array(
                self::TYPE_PRODUCT,
                self::TYPE_SERVICE,
                self::TYPE_COUPON
            ),
                array(
                'description' => t('Тип записи товар, услуга, скидочный купон')
            )),
            'entity_id' => new Type\Varchar(array(
                'description' => t('ID объекта type'),
                'maxLength' => 50
            )),
            'offer' => new Type\Integer(array(
                'description' => t('Комплектация')
            )),
            'multioffers' => new Type\Text(array(
                'description' => t('Многомерные комплектации')
            )),
            'amount' => new Type\Decimal(array(
                'description' => t('Количество'),
                'maxLength' => 11,
                'decimal' => 3,
                'default' => 1
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Название')
            )),
            'extra' => new Type\Text(array(
                'description' => t('Дополнительные сведения')
            ))
        ));
        
        $this->addIndex(array('site_id', 'session_id', 'uniq'), self::INDEX_PRIMARY);   
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
     * Возвращает данные из дополнительного параметра
     *
     * @param string $key - ключ в массиве
     * @param mixed $default - значение по умолчанию
     * @return mixed
     */
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
    * Вызывается после загрузки объекта
    * @return void
    */
    function afterObjectLoad()
    {
        // Приведение типов
        $this['amount'] = (float)$this['amount'];
    }
}

