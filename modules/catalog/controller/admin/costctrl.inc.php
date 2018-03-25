<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table;

/**
* Контроллер. тип цен
* @ingroup Catalog
*/
class CostCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(\Catalog\Model\Costapi::getInstance());
    }
    
    function helperIndex()
    {        
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Справочник типов цен'));
        $helper->setTopHelp($this->view->fetch('help/costctrl_index.tpl'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить цену'))));
        $helper->setBottomToolbar($this->buttons(array('delete')));
        $helper->addCsvButton('catalog-typecost');
        $helper->setListFunction('getTableList');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),            
                new TableType\Text('title', t('Название'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('_type_text', t('Тип')),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'TdAttr' => array('class' => 'cell-sgray'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)),                
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('клонировать тип цены'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('установить по умолчанию'),
                            'attr' => array(
                                '@data-url' => $this->router->getAdminPattern('setDefaultCost', array(':id' => '@id')),
                                'class' => 'crud-get'
                            )
                        ),
                    )),
                    
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));
        return $helper;
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать цену {title}') : t('Добавить цену'));
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    /**
    * AJAX
    */
    function actionSetDefaultCost()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }        
        $id = $this->url->request('id', TYPE_INTEGER);
        $this->api->setDefaultCost($id);
        return $this->result->setSuccess(true);
    }
    
}


