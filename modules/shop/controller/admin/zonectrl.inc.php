<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Admin;

use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar,
    \RS\Html\Filter,
    \RS\Html\Table;
    
/**
* Контроллер Управление скидочными купонами
*/
class ZoneCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $parent,
        $api;
    
    function __construct()
    {
        parent::__construct(new \Shop\Model\ZoneApi());
    }
    
    function helperIndex()
    {
        $this->parent = $this->url->request('pid', TYPE_INTEGER, 0);
        
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('Зона - это пользовательское объединение географических регионов, например, Москву, Санкт-Петербург, Краснодар можно объеденить в зону "Крупные города". Зоны используются для формирования правил и формул расчета доставки, для определения видимости способов доставок и оплат и в других разделах.'));
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Add($this->router->getAdminUrl('add', array('pid' => $this->parent)), t('Добавить'))
            )
        )));
        $helper->addCsvButton('shop-region');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'linkAttr' => array('class' => 'crud-add') )),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH)),                
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                            'attr' => array(
                                '@data-id' => '@id'
                            ))),
                        new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Клонировать зону'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),                                 
                        )),
                    )
                ),                
            )        
        )));
        
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array(
                            new Filter\Type\Text('id','№', array('attr' => array('class' => 'w100'))),
                            new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                        )
                    ))
                )
            )),
            'Caption' => t('Поиск по зонам'),
            'AddParam' => array('hiddenfields' => array('pid' => $this->parent))
        )));
        
        $helper['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт в CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'shop-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт из CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'shop-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),            
        )), 'import');        
        
        
        return $helper;
    }
    
    function actionIndex()
    {
        $helper = $this->getHelper();
        $helper->setTopTitle(t('Зоны'));            
        return parent::actionIndex();
    }
    
    /**
    * Добавление купонов
    */
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if (!$primaryKey) {
            $this->getHelper()->setTopTitle(t('Добавить зону'));
        } else {            
            $this->api->getElement()->fillRegions();
            $this->getHelper()->setTopTitle(t('Редактировать зону').' {title}');        
        }
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
}