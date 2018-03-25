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
    \RS\Html\Toolbar,
    \RS\Html\Filter,
    \RS\Html\Table;

/**
* Контроллер покупки в 1 клик в админ. панели   
*/
class OneClickCtrl extends \RS\Controller\Admin\Crud
{
    
    function __construct()
    {
        parent::__construct(new \Catalog\Model\OneClickItemApi());
    }
    
    function actionIndex()
    {          
        $this->getHelper()->setTopTitle(t('Покупки в 1 клик')); //Заголовок

        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $collection = parent::helperIndex();
        $collection->setTopHelp(t('В этом разделе указаны заявки на товары, которые пользователи оформили по упрощенной схеме. ReadyScript позволяет создавать "полноценный" заказ из покупки в 1 клик, для этого откройте к просмотру заявку на покупку и воспользуйтесь соответствующей кнопкой. Вы можете посмотреть, когда именно была совершена такая покупка, ее статус, а также при необходимости связаться с покупателем, который оформляя такую покупку, оставляет номер своего телефона.'));
        $collection->setTopToolbar(null);

        //Параметры таблицы в админке 
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Viewed(null, $this->api->getMeterApi()),
                new TableType\Text('title', t('Название'), array(
                                    'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 
                                    'LinkAttr' => array('class' => 'crud-edit'),
                                    'Sortable' => SORTABLE_BOTH, 
                )),
                new TableType\Datetime('dateof', t('Дата создания'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Text('status', t('Статус'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)),
                new TableType\Text('id', '№', array(
                    'TdAttr' => array('class'=> 'cell-sgray'),
                    'Sortable' => SORTABLE_BOTH, 
                    'CurrentSort' => SORTABLE_ASC
                )),
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
                                )
                            )),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('сформировать заказ'),
                                    'attr' => array(
                                        '@href' => $this->router->getAdminPattern('createorderfromoneclick', array(':oneclick_id' => '@id')),
                                    )
                                )
                            ))
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                )
            )
        )));



        //Параметры фильтра
        $collection->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                            new Filter\Type\Text('id','№', array('Attr' => array('size' => 4))),
                                            new Filter\Type\Text('title',t('Название'),array(
                                                'SearchType' => '%like%'
                                            )),
                                            new Filter\Type\Datetime('dateof',t('Дата создания')),
                                            new Filter\Type\Select('status',t('Статус'),array(
                                                ''       => t('-Не выбрано-'),
                                                'new'    => t('Новый'),
                                                'viewed' => t('Закрыт'),
                                            )))
                                    ))
                                )
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        
        
        
            
        $collection->viewAsTable();
        return $collection;
    }

    function helperEdit()
    {
        $helper = parent::helperEdit();

        //Добавим кнопки внизу, если модуль магазин установлен
        if (\RS\Module\Manager::staticModuleExists('shop')) {
            $id = $this->url->get('id', TYPE_INTEGER, false);
            $oneclick = $this->api->getById($id); //Получим сам элемент

            $helper->setBottomToolbar(new Toolbar\Element(array(
                    'Items' => array(
                        'save' => new ToolbarButton\SaveForm(null, t('сохранить')),
                        'cancel' => new ToolbarButton\Cancel($this->url->getSavedUrl($this->controller_name . 'index')),
                        'create' => new ToolbarButton\Button($this->router->getAdminUrl('createorderfromoneclick', array('oneclick_id' => $oneclick['id'])), t('создать заказ')),
                    ))
            ));
        }

        return $helper;
    }

    /**
    * AJAX
    */
    function actionMove()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $direction = $this->url->request('flag', TYPE_STRING);
        return $this->result->setSuccess( $this->api->moveElement($from, $to, $direction) )->getOutput();
    }
    
    
    /**
    * Создаёт заказ из Купить в один клик
    * 
    */
    function actionCreateOrderFromOneClick()
    {
        $oneclick_id       = $this->url->get('oneclick_id', TYPE_INTEGER, false);
        $oneclick_item_api = new \Catalog\Model\OneClickItemApi();
        /**
        * @var \Catalog\Model\Orm\OneClickItem
        */
        $oneclick     = $oneclick_item_api->getById($oneclick_id);  
        $oneclick_api = new \Catalog\Model\OneClickApi(); 
        
        $order = $oneclick_api->createOrderFromOneClick($oneclick);
        
        $this->redirect($this->router->getAdminUrl('edit', array('id'=>$order['id']), 'shop-orderctrl'));
    }
        
}


