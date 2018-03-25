<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Table;

/**
* Класс контроллера складов админки    
*/
class WareHouseCtrl extends \RS\Controller\Admin\Crud
{
    protected 
        /**
        * @var \Catalog\Model\WareHouseApi
        */
        $api;

    function __construct($param = array())
    {
        parent::__construct(new \Catalog\Model\WareHouseApi());
    }
    
    function helperIndex()
    {  
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('На этой вкладке вы можете задать список складов, а также настроить их географическое положение, время работы и другие параметры. В некоторых случаях, складами могут выступать ваши магазины(торговые точки). Создав в данном разделе склады, вы сможе указывать остатки каждого товара (во вкладке Комплектации) на любом из ваших складов. Остатки на складах могут отображаться всем пользователям в карточке товара в виде условных рисок.'));
        $helper->setTopTitle(t('Склады'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить'))));
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->addCsvButton('catalog-warehouse');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('id'),
                    new TableType\Sort('sortn', t('Порядок'),array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC,'ThAttr' => array('width' => '20'))),                    
                    new TableType\Text('title', t('Полное название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH, 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Text('alias', t('URL имя склада'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\StrYesno('default_house', t('Склад по умолчанию?')),
                    new TableType\StrYesno('public', t('Публичный')),
                    new TableType\StrYesno('checkout_public', t('Пункт самовывоза')),
                    new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                    new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                        new TableType\Action\DropDown(array(
                            array(
                                    'title' => t('клонировать склад'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                            ),  
                            array(
                                'title' => t('установить по умолчанию'),
                                'attr' => array(
                                    '@data-url' => $this->router->getAdminPattern('setDefaultWareHouse', array(':id' => '@id')),
                                    'class' => 'crud-get'
                                )
                            ),
                        )),
                        
                        ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ),
        ),
        'TableAttr' => array(
            'data-sort-request' => $this->router->getAdminUrl('move')
        ))));
        
        
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('title',t('Полное наименование'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('alias',t('URL имя склада'), array('SearchType' => '%like%')),
                                                        )
                                    ))
                                ),
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        return $helper;
    }    
    
    function actionMove()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $flag = $this->url->request('flag', TYPE_STRING); //Указывает выше или ниже элемента to находится элемент from
        
        if ($this->api->moveElement($from, $to, $flag)) {
            $this->result->setSuccess(true);
        } else {
            $this->result->setSuccess(true)->setErrors($this->api->getErrorsStr());
        }
        
        return $this->result->getOutput();
    }
    
    /**
    * AJAX
    */
    function actionSetDefaultWareHouse()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        $this->api->setDefaultWareHouse($id);
        return $this->result->setSuccess(true)->getOutput();
    }
    
    /**
    * Метод для клонирования
    * 
    */ 
    function actionClone()
    {
        $this->setHelper( $this->helperAdd() );
        $id = $this->url->get('id', TYPE_INTEGER);
        
        $elem = $this->api->getElement();
        
        if ($elem->load($id)) {
            $clone_id = null;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = $clone['id']; 
            }
            unset($elem['id']);
            unset($elem['xml_id']);
            $elem['default_house'] = 0;
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
    
}


