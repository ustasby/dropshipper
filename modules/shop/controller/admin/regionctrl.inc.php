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
class RegionCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $parent,
        $api;
    
    function __construct()
    {
        parent::__construct(new \Shop\Model\RegionApi());
    }
    
    function helperIndex()
    {
        $this->parent = $this->url->request('pid', TYPE_INTEGER, 0);
        
        $helper = parent::helperIndex();
        $helper->setTopHelp(t('В этом разделе можно завести 3х уровневый (страна, область/край, город) справочник, который будет использован в формах при оформлении заказа. Укажите здесь те регионы, в которые вы желаете продавать свои товары. Клик по элементу переместит вас к следующему уровню вложенности.'));
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Add($this->router->getAdminUrl('add', array('pid' => $this->parent)), t('Добавить'))
            )
        )));
        
        $helper->addCsvButton('shop-region', array('whereCondition' => ''));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC, 'href' => $this->router->getAdminPattern(false, array(':pid' => '@id')), 'LinkAttr' => array('class' => 'call-update') )),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('sortn', 'Порядок', array('ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                            'attr' => array(
                                '@data-id' => '@id'
                            ))),
                        new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Клонировать регион'),
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
            'Caption' => t('Поиск по регионам'),
            'AddParam' => array('hiddenfields' => array('pid' => $this->parent))
        )));
        
        return $helper;
    }
    
    function actionIndex()
    {
        $helper = $this->getHelper();
        if ($this->parent > 0) {
            /**
            * @var \Shop\Model\Orm\Region
            */
            $parent_item = $this->api->getOneItem($this->parent);
            
            $columns = $helper['table']->getTable()->getColumns();    
            if (!$parent_item['parent_id']){ //Если это регион
                $helper
                ->setTopTitle($parent_item['title'].'. '.t('Регионы доставки'));
            
                $helper['table']->getTable()->insertAnyRow(array(
                    new TableType\Text(null, null, array('href' => $this->router->getAdminUrl(false, array('pid' => 0)), 'Value' => t('.. (к списку стран)'), 'LinkAttr' => array('class' => 'call-update'), 'TdAttr' => array('colspan' => 4)))
                ), 0);  
            }else{ //Если это города
                $helper
                ->setTopTitle($parent_item['title'].'. '.t('Города'));
                
                $columns[1]->setHref($this->router->getAdminPattern('edit', array(':id' => '@id')));
                $columns[1]->setLinkAttr(array('class' => 'crud-edit'));
                
                $helper['table']->getTable()->insertAnyRow(array(
                    new TableType\Text(null, null, array('href' => $this->router->getAdminUrl(false, array('pid' => $parent_item->getParent()->id)), 'Value' => t('.. (к списку регионов)'), 'LinkAttr' => array('class' => 'call-update'), 'TdAttr' => array('colspan' => 4)))
                ), 0);  
            }
        } else {
            $helper
                ->setTopTitle(t('Страны, регионы и города доставки'));            
        }
        
        $this->api->setFilter('parent_id', $this->parent);
                
        return parent::actionIndex();
    }
    
    /**
    * Добавление купонов
    */
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if ($primaryKey === null) {
            $this->api->getElement()->parent_id = $this->url->request('pid', TYPE_INTEGER);
        }
        if (!$primaryKey) { //0 или null
            $this->getHelper()->setTopTitle(t('Добавить регион'));
        } else {
            $this->getHelper()->setTopTitle(t('Редактировать регион').' {title}');        
        }
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    
    /**
    * Хелпер перед добавлением страны, региона или города
    */ 
    function helperAdd()
    {
        $helper = parent::helperAdd();
        $pid    = $this->request('pid', TYPE_INTEGER, 0);
        
        if ($pid){ //Если есть родитель и это регион, то отобразим нужные поля
            $elem    = new \Shop\Model\Orm\Region($pid);
            $country = $elem->getParent();
            if (isset($country['id']) && $country['id']){
                $helper->setFormSwitch('city');
            } 
        }
        return $helper;
    }
    
    /**
    * Окно редактирования страны, региона или города
    */
    function helperEdit()
    {
        $helper = parent::helperEdit();
        $id     = $this->request('id', TYPE_INTEGER, 0);
        
        if ($id){ //Если есть родитель и это регион, то отобразим нужные поля
            $elem    = new \Shop\Model\Orm\Region($id);
            $country = $elem->getParent()->getParent();
            if (isset($country['id']) && $country['id']){
                $helper->setFormSwitch('city');
            } 
        }
        return $helper;
    }
}