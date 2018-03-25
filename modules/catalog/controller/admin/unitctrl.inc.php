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

class UnitCtrl extends \RS\Controller\Admin\Crud
{
    protected 
        $api;

    function __construct($param = array())
    {
        parent::__construct(new \Catalog\Model\UnitApi());
    }
    
    function helperIndex()
    {  
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Создайте в данном разделе единицы измерения, которые будут использоваться в ваших товарах.'));
        $helper->setTopTitle(t('Единицы измерения'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить единицу измерения'))));
        $helper->addCsvButton('catalog-unit');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                    new TableType\Checkbox('id'),
                    new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC,'ThAttr' => array('width' => '20'))),                    
                    new TableType\Text('title', t('Полное название'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                    new TableType\Text('stitle', t('Сокращенное название'),array('Sortable' => SORTABLE_BOTH)),
                    new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Клонировать'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),
                            )),
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
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
                                                            new Filter\Type\Text('stitle',t('Короткое обозначение'), array('SearchType' => '%like%')),
                                                        )
                                    ))
                                ),
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        return $helper;
    }    
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать единицу измерения {title}') : t('Добавить единицу измерения'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    /**
    * Сортировка в списке
    * 
    */
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
    
}


