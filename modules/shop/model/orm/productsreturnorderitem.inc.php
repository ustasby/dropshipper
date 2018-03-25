<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use RS\Orm\Type;

/**
 * ORM объект товара в возврате товаров
 */
class ProductsReturnOrderItem extends \RS\Orm\OrmObject
{
    protected static 
        $table = 'order_products_return_item';

    function _init() //инициализация полей класса. конструктор метаданных
    {
        return parent::_init()
            ->append(array(
                'site_id' => new Type\Integer(),
                'uniq' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'description' => t('Уникальный идентификатор'),
                )),
                'return_id' => new Type\Integer(array(
                    'maxLength' => '20',
                    'index' => true,
                    'description' => t('Id возврата'),
                )),
                'amount' => new Type\Integer(array(
                    'maxLength' => '20',
                    'description' => t('Количество товара'),
                )),
                'cost' => new Type\Decimal(array(
                    'maxLength' => '20',
                    'description' => t('Цена товара'),
                )),
                'barcode' => new Type\Varchar(array(
                    'description' => t('Артикул'),
                )),
                'model' => new Type\Varchar(array(
                    'description' => t('Модель'),
                )),
                'title' => new Type\Varchar(array(
                    'description' => t('Название'),
                )),
            ));
    }

    /**
     * Возвращает объект заявления на возврат
     *
     * @return ProductsReturn
     */
    function getReturn()
    {
        static $cache = array();

        $return_id = $this['return_id'];

        if (!isset($cache[$return_id])) {
            $cache[$return_id] = new ProductsReturn($return_id);
        }
        return $cache[$return_id];
    }
}
