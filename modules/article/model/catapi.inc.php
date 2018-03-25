<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Model;

class CatApi extends \RS\Module\AbstractModel\TreeCookieList
{
    protected
        static $instance;
        
    function __construct()
    {
        parent::__construct(new \Article\Model\Orm\Category, 
        array(
            'parentField' => 'parent',
            'multisite' => true,
            'idField' => 'id',
            'aliasField' => 'alias',
            'nameField' => 'title',
            'sortField' => 'sortn',
            'defaultOrder' => 'sortn'
        ));
    }
        
    static function getInstance()
    {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }            
    
    static function selectList($include_root = true)
    {
        $_this = self::getInstance();
        $list = $_this->getSelectList(0);
        return $include_root ? array('' => t('Верхний уровень')) + $list : $list;
    }

    
    /**
    * будет выдавать ошибку при попытке установить в качестве родителя категории саму себя
    */
    function multiedit_dir_check($element, $post, $ids) 
    {
        if (isset($post['parent'])) {
            foreach($ids as $id) {
                if (!strcmp($id, $post['parent'])) {
                    $element->addError(t('Неверно задана родительская категория для выбранных элементов.'));
                    return false;
                }
            }
        }
        return true;
    }    
        
}

