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
    \RS\Html\Table,
    \RS\Html\Tree,
    \Shop\Model;
    
/**
* Контроллер Управление заказами
*/
class OrderCtrl extends \RS\Controller\Admin\Crud
{
    const
        QUICK_VIEW_PAGE_SIZE = 20;

    protected
        $status,
        $status_api;
        
    function __construct()
    {
        parent::__construct(new Model\OrderApi());
        $this->status_api = new \Shop\Model\UserStatusApi();
    }



    function helperIndex()
    {
        $helper = parent::helperIndex();
        
        $edit_href = $this->router->getAdminPattern('edit', array(':id' => '@id'));
        $this->status = $this->url->request('status', TYPE_INTEGER);
        
        if ($this->status>0 && $current_status = $this->status_api->getOneItem($this->status)) {
            $this->api->setFilter('status', $this->status);
        } else {
            $this->status = 0;
            $current_status = null;
        }
        
        $helper
            ->setTopHelp(t('Здесь отображаются оформленные пользователями и администраторами заказы. Используйте статусы для информирования клиентов о ходе выполнения заказов и внутреннего контроля исполнения заказов. Напоминаем, что заказы могут оплачиваться пользователями только в статусе <i>Ожидает оплату</i>. Используйте статус <i>Новый</i>, если заказ требует модерации или проверки менеджером. Завершенные заказы следует переводить в статус <i>Выполнен и закрыт</i>. Переводите заказ в статус <i>Отменен</i>, чтобы вернуть остатки на склады и отметить, что заказ не следует исполнять (только если включен контроль остатков). Корректное назначение статусов поможет системе верно строить графики и показывать отчеты. Вы всегда можете переименовать системные статусы или назначить им дублеров с отличными именами. Создавайте произвольные статусы, чтобы более точно информировать пользователей о текущем положении заказа в цепочке ваших бизнесс процессов.'))
            ->setTopToolbar(new Toolbar\Element( array(
                'Items' => array(
                    new ToolbarButton\Dropdown(array(
                        array(
                            'title' => t('создать заказ'),
                            'attr' => array(
                                'href' => $this->router->getAdminUrl('add'),
                                'class' => 'btn-success'
                            )
                        ),
                        
                        array(
                            'title' => t('добавить статус'),
                            'attr' => array(
                                'href' => $this->router->getAdminUrl('addStatus'),
                                'class' => 'crud-add'
                            )
                        )
                    )),
                ))
            ))
            ->viewAsTableTree()
            ->setTopTitle(t('Заказы'))
            ->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Viewed(null, $this->api->getMeterApi()),
                new TableType\Text('order_num', t('Номер'), array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href) ),
                new TableType\Usertpl('user_id', t('Покупатель'), '%shop%/order_user_cell.tpl', array('href' => $edit_href)),
                new TableType\Usertpl('status', t('Статус'), '%shop%/order_status_cell.tpl', array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href)),
                new TableType\Datetime('dateof', t('Дата оформления'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Datetime('dateofupdate', t('Дата обновления'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Usertpl('totalcost', t('Сумма'), '%shop%/order_totalcost_cell.tpl', array('Sortable' => SORTABLE_BOTH)),
                new TableType\StrYesno('is_payed', t('Оплачен'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Userfunc('user_phone', t('Телефон покупателя'), function($user_phone, $_this){
                    /**
                    * @var \Users\Model\Orm\User
                    */
                    $user  = $_this->getRow()->getUser(); //Пользователь совершивший покупку
                    return $user['phone'];
                }, array('hidden' => true)),
                new TableType\Text('payment', t('Способ оплаты'), array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href, 'hidden' => true) ),
                new TableType\Text('delivery', t('Способ Доставки'), array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href, 'hidden' => true) ),
                new TableType\Text('track_number', t('Трек-номер'), array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href, 'hidden' => true) ),
                new TableType\StrYesno('is_mobile_checkout', t('Заказ через моб.приложение'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true) ),
                new TableType\Userfunc('e_mail', t('Email'), function($user_mail, $_this){
                    /**
                     * @var \Users\Model\Orm\User
                     */
                    $user  = $_this->getRow()->getUser(); //Пользователь совершивший покупку
                    return $user['e_mail'];
                }, array('hidden' => true)),
                new TableType\Text('manager_user_id', t('Менеджер заказа'), array('Sortable' => SORTABLE_BOTH, 'href' => $edit_href, 'hidden' => true) ),

                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'noajax' => true,
                        'attr' => array(
                            '@data-id' => '@id'
                        ))),
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('Повторить заказ'),
                                'attr' => array(
                                    'class' => ' ',
                                    '@href' => $this->router->getAdminPattern('add', array(':from_order' => '@id')),
                                )
                            ),
                            array(
                                'title' => t('Оформить возврат'),
                                'attr' => array(
                                    'class' => 'crud-add',
                                    '@href' => $this->router->getAdminPattern('add', array(':order_id'=>'@id'), 'shop-returnsctrl'),
                                )
                            ),
                        ))
                    ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));
        
        $helper['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Экспорт/Отчёт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт заказов в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'shop-order', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт заказанных товаров в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'shop-orderitems', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Показать отчёт'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('ordersReport'),
                    'class' => 'crud-add'
                )
            ),
        )));
        
        $tree = new Tree\Element( array(
            'classField' => '_class',
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->status,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'sortable' => false,
            'noExpandCollapseButton' => true,
            'mainColumn' => new TableType\Usertpl('title', t('Название'), '%shop%/order_tree_cell.tpl', array(
                'href' => $this->router->getAdminPattern(false, array(':status' => '@id'))
            )),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('editStatus', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    )
                )))
            ),
            'headButtons' => array(
                array(
                    'text' => t('Статус'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),                        
                array(
                    'attr' => array(
                        'title' => t('Добавить статус'),
                        'href' => $this->router->getAdminUrl('addStatus'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        ));        
        
        $helper
            ->setTreeListFunction('getAdminTreeList')
            ->setTree($tree, $this->status_api)
            ->setTreeBottomToolbar(new Toolbar\Element( array(
                'Items' => array(
                    new ToolbarButton\Delete(null, null, array('attr' => 
                        array('data-url' => $this->router->getAdminUrl('delStatus'))
                    )),
            ))));

        $payments = array('' => t('Любая')) + \Shop\Model\PaymentApi::staticSelectList();
        $deliveries = array('' => t('Любая')) + \Shop\Model\DeliveryApi::staticSelectList();
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\DropUp(array(
                    array(
                        'title' => t('Печать'),
                        'attr' => array(
                            'class' => 'button',
                            'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                        )
                    ),
                    array(
                        'title' => t('Заказ'),
                        'attr' => array(
                            'data-url' => \RS\Router\Manager::obj()->getAdminUrl('massPrint', array('type' => 'orderform')),
                            'class' => 'crud-post'
                        )
                    ),
                    array(
                        'title' => t('Товарный чек'),
                        'attr' => array(
                            'data-url' => \RS\Router\Manager::obj()->getAdminUrl('massPrint', array('type' => 'commoditycheck')),
                            'class' => 'crud-post'
                        )
                    ),
                    array(
                        'title' => t('Лист доставки'),
                        'attr' => array(
                            'data-url' => \RS\Router\Manager::obj()->getAdminUrl('massPrint', array('type' => 'deliverynote')),
                            'class' => 'crud-post'
                        )
                    ),
                )),
                new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del'))
                )),
            )
        )));   
        
        $helper->setFilter(new Filter\Control( array(
             'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('Items' => array(
                                                            new Filter\Type\Text('order_num', '№'),
                                                            new Filter\Type\DateRange('dateof', t('Дата оформления')),
                                                            new Filter\Type\Text('totalcost', t('Сумма'), array('showtype' => true))
                                                        )
                                    )),
                                    
                                ),
                                'SecContainer' => new Filter\Seccontainer( array(
                                    'Lines' => array(
                                        new Filter\Line( array('Items' => array(
                                                new Filter\Type\User('user_id', t('Пользователь')),
                                                //Поиск по добавленной таблице с пользователями
                                                new \Shop\Model\HtmlFilterType\UserFIO('user_fio', t('ФИО пользователя'), array('searchType' => '%like%')),
                                                //Поиск по добавленной таблице с товарами заказа
                                                new \Shop\Model\HtmlFilterType\Product('PRODUCT.title', t('Наименование товара'), array('searchType' => '%like%')),
                                                new \Shop\Model\HtmlFilterType\Product('PRODUCT.barcode', t('Артикул товара'), array('searchType' => '%like%')),
                                                //Поиск по добавленной таблице с пользователями
                                                new \Shop\Model\HtmlFilterType\UserPhone('USER.phone', t('Телефон пользователя'), array('searchType' => '%like%')),
                                        ))),
                                        new Filter\Line( array('Items' => array(
                                                new Filter\Type\User('manager_user_id', t('Менеджер')),
                                                new Filter\Type\Select('payment', t('Оплата'), $payments),
                                                new Filter\Type\Select('delivery', t('Доставка'), $deliveries),
                                                new Filter\Type\Select('is_mobile_checkout', t('Заказ через моб. приложение'), array(
                                                    '' => t('Не важно'),
                                                    1 => t('Да'),
                                                    0 => t('Нет')
                                                )),
                                        ))),                                        
                                    )
                                ))
                            )),
            'Caption' => t('Поиск по заказам')
        )));
              
        return $helper;
    } 
    
    /**
    * Отбработка хелпера, подготовка обёртки 
    * 
    */
    function helperEdit()
    {
        $id     = $this->url->request('id', TYPE_STRING, 0);
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, $this->api, $this->url, array(
            'bottomToolbar' => $this->buttons(array($id > 0 ? 'saveapply' : 'apply', 'cancel')),
            'viewAs' => 'form'
        ));
        if ($id>0){ //Если заказ уже существует
           $helper['bottomToolbar']->addItem(
               new ToolbarButton\delete( $this->router->getAdminUrl('delOrder', array('id' => $id, 'dialogMode' => $this->url->request('dialogMode', TYPE_INTEGER))), null, array(
                    'noajax' => true,
                    'attr' => array(
                        'class' => 'btn-danger delete crud-get crud-close-dialog',
                        'data-confirm-text' => t('Вы действительно хотите удалить заказ?')
                    )
               )),
               'delete'
           );
           //Добавим ещё повотр заказа
           $helper['bottomToolbar']->addItem(
                new ToolbarButton\Cancel( $this->router->getAdminUrl('add', array('from_order' => $id)), t('Повторить заказ'), array(
                    'noajax' => true,
                    'attr' => array(
                        'class' => 'btn btn-alt btn-primary m-l-30',
                    )
                ))
           );
        }
        return $helper;
    }
    
    
    /**
    * Обработывает заказ - страница редактирования
    * 
    */
    function actionEdit()
    {
        $helper = $this->getHelper();
        
        $id           = $this->url->request('id', TYPE_STRING, 0);
        $order_id     = $this->url->request('order_id', TYPE_INTEGER, false); 
        $refresh_mode = $this->url->request('refresh', TYPE_BOOLEAN);
        
        /**
        * @var Model\Orm\Order $order
        */
        $order = $this->api->getElement();
        
        if ($id){
            $order->load($id);
            $this->api->getMeterApi()->markAsViewed($id);

        }elseif ($order_id){ //Если идёт только создание
            $order['id'] = $order_id;
        }
        $show_delivery_buttons = 1; //Флаг показа дополнительных кнопок при смене доставки

        if ($this->url->isPost()) {
            //Подготавливаем заказ с учетом правок
            $user_id            = $this->url->request('user_id', TYPE_INTEGER, 0); //id пользователя
            $post_address       = $this->url->request('address', TYPE_ARRAY); //Сведения адреса
            $items              = $this->url->request('items', TYPE_ARRAY);  //Товары 
            $warehouse          = $this->url->request('warehouse', TYPE_INTEGER); //Склад
            $delivery_extra     = $this->url->request('delivery_extra', TYPE_ARRAY, false); //Дополнительные данные для доставки
            
            //Если склад изменили
            if ($order['warehouse'] != $warehouse){ 
               $order['back_warehouse'] = $order['warehouse']; //Запишем склад на который надо вернуть остатки 
            }

            //Если включено уведомлять пользователя, то сохраним сведения о прошлом адресе, который ещё не перезаписан
            if ($this->url->request('notify_user', TYPE_INTEGER, false)){
                $order->before_address = $order->getAddress();
            }
            
            $old_extra = $order['extra']; // записыаем extra для последующей проверки
            //Получаем данные из POST
            $order->removeConditionCheckers();
            if (!$order->checkData()) {
                return $this->result
                            ->setErrors($order->getDisplayErrors());
            }
            // checkData стираетют свойства ArrayList если они не пришли в post, если extra стёрлась - восстанавливаем её
            if (!empty($old_extra) && empty($order['extra'])) {
                $order['extra'] = $old_extra;
            }
            
            if ($delivery_extra){
                $order->addExtraKeyPair('delivery_extra', $delivery_extra);
            }
            
            $address = new Model\Orm\Address();
            $address->getFromArray($post_address + array('user_id' => $order['user_id']));
            $address->updateAddressTitles(); // Скоректируем названия страны и региона перед выводом (как при сохранении)
            
            //Если включено уведомлять пользователя, то сохраним сведения о прошлом адресе, который ещё не перезаписан
            if ($this->url->request('notify_user', TYPE_INTEGER, false)){
               $order->before_address = $order->getAddress();  
            }
            if ($order['use_addr']) { //Если есть заданный адрес
               $order->setAddress($address);
            }

            //Если цена задана уже у заказа
            if ($this->url->post('user_delivery_cost', TYPE_STRING) === '') {
                $order['user_delivery_cost'] = null;
            }
            //Если нужно пересчитать доставку
            if ($refresh_mode && ($this->url->post('user_delivery_cost', TYPE_STRING) === '')){
                $order['delivery_new_query'] = 1;
            }
            //Если заказ ещё не создан, то считаем доставку всегда
            if ($order['id'] < 0) {
                $order['user_delivery_cost'] = null;
                $order['delivery_new_query'] = 1;
            }
            
            //Если заказ создан установим флаг показа дополнительных кнопок доставки, если они существуют
            if ($order['id'] > 0) {
               $show_delivery_buttons = $this->url->post('show_delivery_buttons', TYPE_INTEGER, 1);
               //Если мы поменяли в доставке что-либо, то тоже запросим внешёнюю доставку, после сохранения
               if (!$show_delivery_buttons){
                   $order['delivery_new_query'] = 1; 
               }
            }

            
            $order->getCart()->updateOrderItems($items);
            
            if (!$refresh_mode) {
                //Проверяем права на запись для модуля Магазин
                if ($error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
                    $this->api->addError($error);
                    return $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
                }
                
                //Обновляем или вставим запись если сменился пользователь
                if ($order['use_addr']) { 
                    //Проверим пользователя у Адреса, и если пользователь поменялся, то задублируем адрес, иначе обновим
                    $address_api = new \Shop\Model\AddressApi();
                    $address_api->setFilter('id', $order['use_addr']);
                    $old_address = $address_api->getFirst();

                    if ($old_address && ($old_address['user_id']!=$user_id)){
                        unset($address['id']);
                        $address->insert(); 
                        $order['use_addr'] = $address['id'];
                    }else{
                       $address['id'] = $order['use_addr'];  
                       $address->update(); 
                    }
                }
                $order['is_exported'] = 0; //Устанавливаем флаг, что заказ нужно заново выгрузить в commerceML

                //Если нужно создать заказ
                if (isset($order['id']) && $order['id']<0) {
                    $order->setCurrency( \Catalog\Model\CurrencyApi::getCurrentCurrency() );     
                    if ($save_result = $order->insert()){ //Перенаправим на ректирование, если создался заказ
                        $order->getCart()->saveOrderData();   
                        return $this->result->setSuccess(true)
                                        ->setSuccessText(t('Заказ успешно создан'))
                                        ->setHtml(false)
                                        ->addSection('windowRedirect', $this->router->getAdminUrl('edit', array('id'=>$order['id']), 'shop-orderctrl'));  
                    }
                } elseif ($save_result = $order->update( $id )) {
                    $order->getCart()->saveOrderData();  
                }

                $this->result->setSuccess($save_result);

                if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                    if (!$this->result->isSuccess()) {
                        $this->result->setErrors($order->getDisplayErrors());
                    } else {
                        $this->result->setSuccessText(t('Изменения успешно сохранены'));
                    }
                    return $this->result->getOutput();
                }
                
                if ($this->result->isSuccess()) {
                    $this->successSave();
                } else {
                    $helper['formErrors'] = $order->getDisplayErrors();
                }
            }
        }
        $user_num_of_order = $this->api->getUserOrdersCount($order['user_id']); 
        
        //Склады
        $warehouses = \Catalog\Model\WareHouseApi::getWarehousesList();
        $couriers = \Shop\Model\DeliveryApi::getCourierSelectList(array());

        $user_status_api = new Model\UserStatusApi();
        $return_api = new \Shop\Model\ProductsReturnApi();
        $returned_items = $return_api->getReturnItemsByOrder($id);

        $this->view->assign(array(
            'order_footer_fields' => $order->getForm(null, 'footer', false, null, '%shop%/order_footer_maker.tpl'),
            'order_depend_fields' => $order->getForm(null, 'depend', false, null, '%shop%/order_depend_maker.tpl'),
            'order_user_fields' => $order->getForm(null, 'user', false, null, '%shop%/order_depend_maker.tpl'),
            'order_info_fields' => $order->getForm(null, 'info', false, null, '%shop%/order_info_maker.tpl'),
            'order_delivery_fields' => $order->getForm(null, 'delivery', false, null, '%shop%/order_info_maker.tpl'),
            'order_payment_fields' => $order->getForm(null, 'payment', false, null, '%shop%/order_info_maker.tpl'),

            'catalog_folders' => \RS\Module\Item::getResourceFolders('catalog'),
            'elem' => $order,
            'user_id' => $order['user_id'],
            'warehouse_list' => $warehouses,
            'courier_list' => $couriers,
            'status_list' => $user_status_api->getTreeList(),
            'default_statuses' => $user_status_api->getStatusIdByType(),
            'refresh_mode' => $refresh_mode,
            'show_delivery_buttons' => $show_delivery_buttons,
            'user_num_of_order' => $user_num_of_order,
            'returned_items' => $returned_items,
        ));
        $helper['form'] = $this->view->fetch('%shop%/orderview.tpl');
        $helper->setTopTitle(null);

        if ($refresh_mode) { //Если режим обновления
            return $this->result->setHtml( $helper['form'] );
        } else { //Если режим редактирования
            $this->view->assign(array(
                'elements' => $helper->active(),
            ));
            return $this->result->setTemplate($helper['template']);
        }
    }
    
    /**
    * Возращает объект обёртки для создания
    * 
    */
    function helperAdd()
    {          
        return $this->helperEdit();
    }
    
    /**
    * Форма добавления элемента
    * 
    * @param mixed $primaryKeyValue - id редактируемой записи
    * @param boolean $returnOnSuccess - Если true, то будет возвращать ===true при успешном сохранении,
    *                                   иначе будет вызов стандартного _successSave метода
    * @param mixed $helper - переданный объект helper
    *
    * @return \RS\Controller\Result\Standard|bool
    */
    public function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {  
        if ($this->url->isPost()){ //Если был передан POST запрос
           return $this->actionEdit(); 
        }
        $from_order = $this->url->request('from_order', TYPE_INTEGER, false);

        $helper = $this->getHelper();
        //Создадим заказ с отрицательным идентификатором
        $order = new \Shop\Model\Orm\Order();
        //Посмотрим, не повтор ли это предыдущего заказа
        if ($from_order){
            $order_api = new \Shop\Model\OrderApi();
            $order = $order_api->repeatOrder($from_order);
        } else {
            $order->setTemporaryId();
        }

        //Склады
        $warehouses = \Catalog\Model\WareHouseApi::getWarehousesList();
        $couriers = \Shop\Model\DeliveryApi::getCourierSelectList(array());
        
        $user_status_api = new Model\UserStatusApi();
        $this->view->assign(array(
           'elem' => $order,
           'order_footer_fields' => $order->getForm(null, 'footer', false, null, '%shop%/order_footer_maker.tpl'),
           'order_user_fields' => $order->getForm(null, 'user', false, null, '%shop%/order_depend_maker.tpl'),
           'catalog_folders' => \RS\Module\Item::getResourceFolders('catalog'),
           'warehouse_list' => $warehouses,
           'courier_list' => $couriers,
           'status_list' => $user_status_api->getTreeList(),
           'user_num_of_order' => 0,
           'refresh_mode' => false
        ));
        $helper['form'] = $this->view->fetch('%shop%/orderview.tpl');
        return $this->result->setTemplate($helper['template']);
    }
    
    
    /**
    * Получает диалоговое окно с поиском пользователя для добавления или создания нового пользователя
    */
    function actionUserDialog()
    {
        $helper  = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper
            ->setTopTitle(t('Добавление пользователя'))
            ->viewAsForm(); 
            
        $refresh = $this->request('refresh', TYPE_INTEGER, false); //Признак обновления 
        $order   = $this->api->getNewElement();    
        $user    = new \Users\Model\Orm\User();
        
        //Если нужно обновить блок 
        if ($this->url->isPost()){
            $is_reg_user = $this->request('is_reg_user', TYPE_INTEGER, 0); //Смотрим, нужно ли регистривать или указать существующего пользователя
                       
            if ($is_reg_user){ //Если нужно регистрировать
               //Проверим  
               $user['is_company']  = $this->request('is_company', TYPE_INTEGER, false);
               $user['company']     = $this->request('company', TYPE_STRING, false);
               $user['company_inn'] = $this->request('company_inn', TYPE_STRING, false);
               $user['name']        = $this->request('name', TYPE_STRING, false);
               $user['surname']     = $this->request('surname', TYPE_STRING, false);
               $user['midname']     = $this->request('midname', TYPE_STRING, false);
               $user['phone']       = $this->request('phone', TYPE_STRING, false);
               $user['login']       = $this->request('e_mail', TYPE_STRING, false);
               $user['e_mail']      = $this->request('e_mail', TYPE_STRING, false);
               $user['openpass']    = $this->request('pass', TYPE_STRING, false);
               $user['data']        = $this->request('data', TYPE_ARRAY, false);
               $user['changepass']  = 1;
               if ($user->save()){
                  $user_num_of_order = $this->api->getUserOrdersCount($user['id']); 
                  $this->view->assign(array(
                      'user' => $user,
                      'user_num_of_order' => $user_num_of_order
                  )); 
                  return $this->result->setSuccess(true)
                                    ->addSection('noUpdateTarget', true)
                                    ->addSection('user_id', $user['id'])
                                    ->addSection('insertBlockHTML', $this->view->fetch('%shop%/form/order/user.tpl'));
               }else{
                  foreach ($user->getErrors() as $error){
                     $this->api->addError($error); 
                  }
               }
            }else{ //Если не нужно регистрировать, а указать конкретного пользователя
               $user_id = $this->request('user_id', TYPE_INTEGER, false);
               if ($user_id){
                   $user = new \Users\Model\Orm\User($user_id);
                   $user_num_of_order = $this->api->getUserOrdersCount($user_id);
                   $this->view->assign(array(
                      'user' => $user,
                      'user_num_of_order' => $user_num_of_order
                   ));
                   return $this->result->setSuccess(true)
                                    ->addSection('noUpdateTarget', true)
                                    ->addSection('user_id', $user_id)
                                    ->addSection('insertBlockHTML', $this->view->fetch('%shop%/form/order/user.tpl'));
               }else{
                   $this->api->addError(t('Не выбран пользователь для добавления'));
               } 
            }
            return $this->result->setSuccess(false)
                                ->setErrors($this->api->getDisplayErrors());
        }
        $conf_userfields = \RS\Config\Loader::byModule('users')->getUserFieldsManager()
            ->setErrorPrefix('userfield_')
            ->setArrayWrapper('data');
        
        $this->view->assign(array(
           'user' => $user,
           'elem' => $order,
           'conf_userfields' => $conf_userfields
        ));
        
        $helper
            ->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                'save' => new ToolbarButton\SaveForm(null, t('применить')),
                'cancel' => new ToolbarButton\Cancel(null, t('отмена')),
            )
        )));
        
        $helper['form'] = $this->view->fetch('%shop%/form/order/user_dialog.tpl');
        return $this->result->setTemplate($helper['template']);
    }


    /**
     * Получает диалоговое окно доставки заказа
     *
     * @return \RS\Controller\Result\Standard
     * @throws \Exception
     * @throws \SmartyException
     */
    function actionDeliveryDialog()
    {
        $delivery_id  = $this->url->request('delivery', TYPE_INTEGER); //Тип доставки
        $helper  = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->viewAsForm(); 
        
        $order_id = $this->url->request('order_id', TYPE_INTEGER, 0);
        /**
        * @var \Shop\Model\Orm\Order
        */
        $order = $this->api->getNewElement();
        if ($order_id<0){ //Если заказ только должен создатся 
           $order['id'] = $order_id; 
           $user_id     = $this->request('user_id', TYPE_INTEGER, 0);
           $helper->setTopTitle(t('Добавление доставки'));
        }else{ //Если уже заказ создан
           $order->load($order_id); 
           $user_id = $order['user_id'];
           $helper->setTopTitle(t('Редактирование доставки'));
        }

        $delivery_api = new Model\DeliveryApi();
        $dlist        = $delivery_api->getListForOrder();
        
        //Получим список адресов
        $address_api = new Model\AddressApi();
        if ($user_id){ //Если пользователь указан
            $address_api->setFilter('user_id', $user_id);
            $address_list = $address_api->getList(); 
        }else{ //Если есть только сведения о заказе
            $address_api->setFilter('order_id', $order_id);
            $address_list = $address_api->getList(); 
        }
        
        
        //Если задан конкретный адрес
        if (isset($order['use_addr']) && $order['use_addr']){
            $this->view->assign(array(
                'current_address' => $address_api->getOneItem($order['use_addr']),
                'address_part' => $this->actionGetAddressRecord($order['use_addr'])->getHtml()
            ));
        }else{ //Если адресов нет, или они не заданы
            $use_addr = 0; //Выбранный адрес
            if (isset($address_list[0]['id'])){ //Если адреса пользователя существуют
                $use_addr = $address_list[0]['id'];
            }
            $this->view->assign(array(
                'address_part' => $this->actionGetAddressRecord($use_addr)->getHtml()
            ));
        }
        
        if ($this->url->isPost()){ //Если пришёл запрос
            //Получим данные
            $use_addr           = $this->url->request('use_addr', TYPE_INTEGER); //Использовать адрес пользователя
            $post_address       = $this->url->request('address', TYPE_ARRAY);  //Сведения об адресе
            $warehouse          = $this->url->request('warehouse', TYPE_INTEGER, false); //Склад 
            $user_delivery_cost = $this->url->request('user_delivery_cost', TYPE_STRING); //Своя цена доставки
            $delivery_id        = $this->url->post('delivery', TYPE_INTEGER); //Тип доставки
            
            //Назначим значения
            $order['delivery'] = $delivery_id; 
            $order['use_addr'] = $use_addr; 
            
            $order_orm = new \Shop\Model\Orm\Order($order_id);
            $order['warehouse'] = $order_orm['warehouse'];
            
            if (!$use_addr){ //Если нужно создать новый адрес для доставки
                $address = new Model\Orm\Address();
            }else{ //Если используется существующий адрес
                $address = new Model\Orm\Address($use_addr);
                $address['region_id'] = 0; // Для ситуации когда region_id указан у адреса, но его нет в post 
            }
            
            $address->getFromArray($post_address + array('user_id' => $user_id));
            
            if (!$use_addr){ //Вставим
                if (!$user_id){ //Если пользователь не указан, запишем адрес к заказу
                    $address['order_id'] = $order['id']; 
                }
                $address->insert();
                $use_addr = $address['id'];
            }else{ //Обновим
                $address->update();
            }
            
            $order->setAddress($address);
            if ($user_delivery_cost === '') {
                $order['user_delivery_cost'] = null;
            }
            //$order->update();
            
            $delivery   = new \Shop\Model\Orm\Delivery($delivery_id); //Назначенная доставка
            $warehouses = \Catalog\Model\WareHouseApi::getWarehousesList();//Склады
            $this->view->assign(array(
                'elem' => $order,
                'delivery' => $delivery,
                'user_id' => $user_id,
                'address' => $address,
                'warehouse_list' => $warehouses,
                'user_delivery_cost' => $user_delivery_cost,
                'order_delivery_fields' => $order->getForm(null, 'address', false, null, '%shop%/order_info_maker.tpl'),
            ));
            
            return $this->result->setSuccess(true)
                    ->setHtml(false)
                    ->addSection('noUpdateTarget', true)
                    ->addSection('delivery', $delivery_id)
                    ->addSection('address', $post_address)
                    ->addSection('use_addr', $use_addr)
                    ->addSection('user_delivery_cost', $user_delivery_cost)
                    ->addSection('insertBlockHTML', $this->view->fetch('%shop%/form/order/delivery.tpl'));
            
        }
        
        $this->view->assign(array(
            'dlist' => $dlist,
            'order' => $order,
            'delivery_id' => $delivery_id,
            'address_list' => $address_list,
        ));
        
        $helper
            ->setBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                'save' => new ToolbarButton\SaveForm(null, t('применить')),
                'cancel' => new ToolbarButton\Cancel(null, t('отмена')),
            )
        )));
        
        $helper['form'] = $this->view->fetch('%shop%/form/order/delivery_dialog.tpl');
        return $this->result->setTemplate($helper['template']);
    }
    
    
    /**
    * Открывает диалоговое окно оплаты
    */ 
    function actionPaymentDialog()
    {
        $helper  = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->viewAsForm(); 
        
        $order_id = $this->url->request('order_id', TYPE_INTEGER);
        /**
        * @var \Shop\Model\Orm\Order
        */
        $order = $this->api->getNewElement();
        if ($order_id<0){ //Если заказ только должен создатся 
           $order['id'] = $order_id; 
           $helper->setTopTitle(t('Добавление оплаты'));
        }else{ //Если уже заказ создан
           $order->load($order_id); 
           $helper->setTopTitle(t('Редактирование оплаты'));
        }
        
        
         
        if ($this->url->isPost()){ //Если пришёл запрос
            $pay_id = $this->url->request('payment', TYPE_INTEGER);
            $order['payment'] = $pay_id; //Установим оплату
        
            $this->view->assign(array(
                'elem' => $order,
                'payment_id' => $pay_id,
                'pay' => $order->getPayment(),
                'order_payment_fields' => $order->getForm(null, 'payment', false, null, '%shop%/order_info_maker.tpl'),
            ));
            
            return $this->result->setSuccess(true)
                            ->setHtml(false)
                            ->addSection('noUpdateTarget', true)
                            ->addSection('payment', $pay_id)
                            ->addSection('insertBlockHTML', $this->view->fetch('%shop%/form/order/payment.tpl'));
        }    
        
        $pay_api = new Model\PaymentApi();
        $plist   = $pay_api->getList();
                
        
        $this->view->assign(array(
            'order' => $order,
            'plist' => $plist
        ));
        
        $helper
            ->setBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                'save' => new ToolbarButton\SaveForm(null, t('применить')),
                'cancel' => new ToolbarButton\Cancel(null, t('отмена')),
            )
        )));
        
        $helper['form'] = $this->view->fetch('%shop%/form/order/payment_dialog.tpl');
        return $this->result->setTemplate($helper['template']);
    }
    
    /**
    * Удаление заказа
    * 
    */
    function actionDelOrder()
    {
        //Проверяем права на запись для модуля Магазин
        if ($error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result
                            ->setSuccess(false)
                            ->addSection('noUpdate', true)
                            ->addEMessage($error);
        }
        
        $id = $this->url->request('id', TYPE_INTEGER);
        if (!empty($id))
        {
            $obj = $this->api->getElement();
            $obj->load($id);
            $obj->delete();
        }
        if (!$this->url->request('dialogMode', TYPE_INTEGER)) {
            $this->result->setAjaxWindowRedirect( $this->url->getSavedUrl($this->controller_name.'index') );
        }
        
        return $this->result
            ->setSuccess(true)
            ->addSection('noUpdate', true)            
            ->setNoAjaxRedirect($this->url->getSavedUrl($this->controller_name.'index'));
    }    
    
    
    
    function actionGetAddressRecord($_address_id = null)
    {
        $address_id = $this->url->request('address_id', TYPE_INTEGER, $_address_id);
        $country_list = Model\RegionApi::countryList();
        $address = new Model\Orm\Address($address_id);
        
        if ($address_id == 0) {
            $address['country_id'] = key($country_list);
        }
        
        if ($address['country_id']) {
            $region_api = new Model\RegionApi();
            $region_api->setFilter('parent_id', $address['country_id']);
            $region_list = $region_api->getList();
        } else {
            $region_list = array();
        }
        
        $this->view->assign(array(
            'address' => $address,
            'country_list' => $country_list,
            'region_list' => $region_list
        ));
        
        return $this->result->setTemplate('form/order/order_delivery_address.tpl');
    }    
    
    function actionGetCountryRegions()
    {
        $parent = $this->url->request('parent', TYPE_INTEGER);        
        
        $this->api = new Model\RegionApi();
        $result = array();
        if ($parent) {
            $this->api->setFilter('parent_id', $parent);
            $list = $this->api->getAssocList('id', 'title');
            foreach($list as $key => $value) {
                $result[] = array('key' => $key, 'value' => $value);
            }
        }
        return $this->result->addSection('list', $result);
    }    
    
    function actionGetOfferPrice()
    {
        $product_id     = $this->url->request('product_id', TYPE_INTEGER);        
        $offer_index    = $this->url->request('offer_index', TYPE_INTEGER);        
        
        $product = new \Catalog\Model\Orm\Product($product_id);
        $product->fillCost();
        $cost_ids = array_keys($product['xcost']);
        $offer_costs = array();
        foreach($cost_ids as $cost_id){
            $cost_obj = new \Catalog\Model\Orm\Typecost($cost_id);
            $offer_costs[$cost_id] = array(
                'title' => $cost_obj->title,
                'cost' => $product->getCost($cost_id, $offer_index, false),
            );
        }
        
        return $this->result->addSection('costs', $offer_costs);
    }
    
    function actionGetUserAddresses()
    {
        $parent = $this->url->request('user_id', TYPE_INTEGER);        
        
        $this->api = new Model\AddressApi();
        $result = array();
        if ($parent) {
            $this->api->setFilter('user_id', $parent);
            $list = $this->api->getList();
            foreach($list as $value) {
                $result[] = array('key' => $value->id, 'value' => $value->getLineView());
            }
        }
        return $this->result->addSection('list', $result);
    }    
    
    
    
    /**
    * Печать заказа
    */
    function actionPrintForm()
    {
        $order_id = $this->url->request('order_id', TYPE_MIXED);
        $selectall = $this->url->request('selectAll', TYPE_STRING);
        $type = $this->url->request('type', TYPE_STRING);
        
        // если передан массив order_id загрузим выбранный список заказов
        if (is_array($order_id)) {
            if ($selectall) {
                $order = \Shop\Model\OrderApi::getSavedRequest('Shop\Controller\Admin\OrderCtrl_list')
                    ->limit(null)->objects();
            } else {
                $this->api->setFilter('id', $order_id, 'in');
                $order = $this->api->getList();
            }
        } else {
            $order = $this->api->getOneItem($order_id);
        }
        if (!empty($order)) {
            $print_form = Model\PrintForm\AbstractPrintForm::getById($type, $order);
            if ($print_form) {
                $this->wrapOutput(false);
                return $print_form->getHtml();
            } else {
                return t('Печатная форма не найдена');
            }
        }
        return t('Заказ не найден');
    }

    
    /**
    * Действие которое вызывает окно с дополнительной информацией в заказе
    * 
    */
    function actionOrderQuery()
    {
       $type = $this->request('type',TYPE_STRING,false);
       
       if (!$type){
          return $this->result
            ->setSuccess(false)
            ->addSection('close_dialog', true)
            ->addEMessage(t('Не указан параметр запроса - type (delivery или payment)'));
       } 
       $order_id = $this->request('order_id',TYPE_STRING,0);
       
       if (!$order_id){
          return $this->result
            ->setSuccess(false)
            ->addSection('close_dialog', true)
            ->addEMessage(t('id заказа указан неправильно')); 
       }
       
       /**
       * @var \Shop\Model\Orm\Order
       */
       $order = new \Shop\Model\Orm\Order($order_id);
       
       if (!$order['id']){
          return $this->result
            ->setSuccess(false)
            ->addSection('close_dialog', true)
            ->addEMessage(t('Такой заказ не найден')); 
       }
       
       switch($type){
          case "delivery":
                if ($delivery_id = $this->url->request('delivary_id', TYPE_INTEGER)) {
                  $order['delivery'] = $delivery_id;
                }

                return $this->result
                            ->setSuccess(true)
                            ->setHtml($order->getDelivery()->getTypeObject()->actionOrderQuery($order));      
                break;
          case "payment":
                if ($payment_id = $this->url->request('payment_id', TYPE_INTEGER)) {
                    $order['payment'] = $payment_id;
                }

                return $this->result->addSection($order->getPayment()->getTypeObject()->actionOrderQuery($order));
                break;  
       }
       
    }
    
    /**
    * Строит отчёт по заказам и выдаёт в отдельном окне 
    * 
    */
    function actionOrdersReport()
    {
        //Получим из сесии сведения по отбору
        $where_conditions = isset($_SESSION['where_conditions']['Shop\Controller\Admin\OrderCtrl_list']) ? clone $_SESSION['where_conditions']['Shop\Controller\Admin\OrderCtrl_list'] : false;
        if ($where_conditions){
            //Получим данные в массив для отчёта
            $order_report_arr = $this->api->getReport($where_conditions);
        
            $this->view->assign(array(
               'order_report_arr' => $order_report_arr,
               'currency' => \Catalog\Model\CurrencyApi::getBaseCurrency(),//Базовая валюта
               'payments' => \Shop\Model\PaymentApi::staticSelectList(),
               'deliveries' => \Shop\Model\DeliveryApi::staticSelectList(),
            ));     
        }

        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->setTopTitle(t('Статистика по заказам'));
        $helper->viewAsAny();
        $helper['form'] = $this->view->fetch('orders_report.tpl');

        return $this->result->setTemplate( $helper['template'] );
    }
    
    
    /**
    * Подбирает город по совпадению в переданной строке
    */
    function actionSearchCity()
    {
        $query       = $this->request('term', TYPE_STRING, false);
        $region_id   = $this->request('region_id', TYPE_INTEGER, false);
        $country_id  = $this->request('country_id', TYPE_INTEGER, false);
        
        if ($query!==false && $this->url->isAjax()){ //Если задана поисковая фраза и это аякс
            $cities = $this->api->searchCityByRegionOrCountry($query, $region_id, $country_id);
            
            $result_json = array();  
            if (!empty($cities)){
                foreach ($cities as $city){
                    $region  = $city->getParent();
                    $country = $region->getParent();
                    $result_json[] = array(
                        'value'      => $city['title'],
                        'label'      => preg_replace("%($query)%iu", '<b>$1</b>', $city['title']),
                        'id'         => $city['id'],
                        'zipcode'    => $city['zipcode'],
                        'region_id'  => $region['id'],
                        'country_id' => $country['id']
                    );
                }
            }
            
            $this->wrapOutput(false);
            $this->app->headers->addHeader('content-type', 'application/json');
            return json_encode($result_json);
        }
    }


    /**
     * Отображает в side баре последние отфильтрованные заказы
     */
    function actionAjaxQuickShowOrders()
    {
        $page = $this->url->request('p', TYPE_INTEGER, 1);
        $exclude_id = $this->url->request('exclude_id', TYPE_INTEGER);

        $request_object = $this->api->getSavedRequest($this->controller_name.'_list');

        if ($request_object) {
            $this->api->setQueryObj($request_object);
            $this->api->setFilter("id", $exclude_id, '!=');
        }

        $paginator = new \RS\Helper\Paginator($page, $this->api->getListCount(), self::QUICK_VIEW_PAGE_SIZE);

        $this->view->assign(array(
            'orders' => $this->api->getList($page, self::QUICK_VIEW_PAGE_SIZE),
            'paginator' => $paginator
        ));

        return $this->result
                    ->addSection(array(
                        'title' => t('Быстрый просмотр заказов'),
                    ))
                    ->setTemplate('quick_show_orders.tpl');
    }
    /**
    * Прослойка для массовой печати документов к заказам
    */
    function actionMassPrint()
    {
        $type = $this->url->request('type', TYPE_STRING, null);
        $chk = $this->url->request('chk', TYPE_MIXED, null);
        $selectall = $this->url->request('selectAll', TYPE_STRING, null);
        $url = $this->router->getAdminUrl('printForm', array('type' => $type, 'order_id' => $chk, 'selectAll' => $selectall));
        return $this->result->setAjaxWindowRedirect($url);
    }

    // ----------------------- Действия для статусов ---------------------

    function helperAddStatus()
    {
        $this->api = $this->status_api;
        $helper = parent::helperAdd();
        return $helper;
    }

    function actionAddStatus($primaryKeyValue = null)
    {
        $helper = $this->getHelper();

        $elem = $this->api->getElement();
        if ($primaryKeyValue && $elem->isSystem()) {
            $elem->__type->setAttr(array('disabled' => true));
        }

        if (!$elem->isSystem()) {
            $helper->setFormSwitch('other');
        }

        if ($primaryKeyValue === null) {
            $helper->setTopTitle(t('Добавить статус'));
        } else {
            $helper->setTopTitle(t('Редактировать статус').' {title}');
        }

        return parent::actionAdd($primaryKeyValue);
    }

    function helperEditStatus()
    {
        return $this->helperAddStatus();
    }

    function actionEditStatus()
    {
        $this->edit_call_action = 'actionAddStatus';
        return parent::actionEdit();
    }

    function actionDelStatus()
    {
        $this->api = $this->status_api;
        return parent::actionDel();
    }

    /**
     * Создание пользователя из пользователя без регистрации
     *
     */
    function actionCreateUserFromNoRegister()
    {
        if ($this->url->isPost()){
            $user = new \Users\Model\Orm\User();
            $user->getFromArray($this->url->getSource(POST), "user_");
            //Уточним некоторые параметры
            $fio = explode(" ", $this->url->request('user_fio', TYPE_STRING, ""));
            if (isset($fio[0])){
                $user['surname'] = $fio[0];
            }
            if (isset($fio[1])){
                $user['name'] = $fio[1];
            }
            if (isset($fio[2])){
                $user['midname'] = $fio[2];
            }
            $user['login']    = $this->url->request('user_email', TYPE_STRING, "");
            $user['e_mail']   = $this->url->request('user_email', TYPE_STRING, "");
            $user['openpass'] = \RS\Helper\Tools::generatePassword(6);
            $user->save();

            //вставим запись если сменился пользователь

            $order_id = $this->url->get('order', TYPE_INTEGER, null);

            $order = new \Shop\Model\Orm\Order($order_id);
            if ($order_id && $order['use_addr']) {
                $address = $order->getAddress();
                if ($address['id']){
                    $address['user_id'] = $user['id'];
                    $address->update();
                }
            }

            if ($user->hasError()){
                return $this->result->setSuccess(false)->addEMessage($user->getErrorsStr());
            }
            $this->view->assign(array(
                'user' => $user
            ));
            return $this->result->setSuccess(true)->addSection('user_id', $user['id'])->setTemplate('%shop%/form/order/user.tpl');
        }
        return $this->result->setSuccess(false);
    }
}
