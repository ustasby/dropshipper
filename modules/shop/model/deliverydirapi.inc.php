<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class DeliveryDirApi extends \RS\Module\AbstractModel\EntityList
{
    public 
        $uniq;
        
    function __construct() 
    {
        parent::__construct(new \Shop\Model\Orm\DeliveryDir(),
        array(
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'nameField' => 'title',
            'sortField' => 'sortn'
        ));
    }

    /**
     * Возвращает плоский список категорий
     *
     * @return array
     */
    public static function selectList()
    {
        $_this = new self();
        return array(0 => t('Без группы'))+ $_this -> getSelectList(0);
    }

    /**
     *
     * Возвращает древовидный список категорий
     *
     * @param bool $show_no_group - Показывать категорию "Без группы"
     * @return array
     */
    public function selectTreeList($show_no_group = true)
    {
        $list = array();
        $res = $this->getListAsResource();
        if ($show_no_group){ //Если нужно показывать категорию "Без группы"
            $list[] = array('fields' => array(
                'id' => 0,
                'title' => t('Без группы'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ), 'child' => array());
        }

        while($row = $res->fetchRow()) {
            $list[] = array('fields' => $row, 'child' => array());
        }
        return $list;
    }

}

