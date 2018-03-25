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
* Orm объект - ставка налога
*/
class TaxRate extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'order_tax_rate';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'tax_id' => new Type\Integer(array(
                'description' => t('Название')
            )),
            'region_id' => new Type\Integer(array(
                'description' => t('ID региона')
            )),
            'rate' => new Type\Decimal(array(
                'description' => t('Ставка налога'),
                'maxLength' => 12,
                'decimal' => 4
            ))
        ));
        $this->addIndex(array('tax_id', 'region_id'), self::INDEX_UNIQUE);
    }
}
?>
