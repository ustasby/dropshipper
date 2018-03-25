<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm;
use \RS\Orm\Type;

/**
* Orm остатка на складах
*/
class Xstock extends \RS\Orm\AbstractObject
{
    protected static
        $table = "product_x_stock";
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'product_id' => new Type\Integer(array(
                'description' => t('ID товара')
            )),
            'offer_id' => new Type\Integer(array(
                'description' => t('ID комплектации'),
                'index' => true
            )),
            'warehouse_id' => new Type\Integer(array(
                'description' => t('ID склада'),
                'index' => true
            )),
            'stock' => new Type\Decimal(array(
                'description' => t('Количество товара'),
                'maxLength' => 11,
                'decimal' => 3,
                'default' => 0
            )),
        ));
        
        $this->addIndex(array('product_id', 'offer_id', 'warehouse_id'), self::INDEX_UNIQUE);
    }
    
    /**
    * Вызывается после загрузки объекта
    * @return void
    */
    function afterObjectLoad()
    {
        // Приведение типов
        $this['stock'] = (float)$this['stock'];
    }
}

