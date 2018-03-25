<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model\Orm;
use \RS\Orm\Type;

class Xzone extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'banner_x_zone';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'zone_id' => new Type\Integer(array(
                'description' => t('ID зоны')
            )),
            'banner_id' => new Type\Integer(array(
                'description' => t('ID баннера')
            ))
        ));
        $this->addIndex(array('zone_id', 'banner_id'), self::INDEX_UNIQUE);
    }
}
?>
