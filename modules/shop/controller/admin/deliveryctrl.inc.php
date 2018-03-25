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
    \RS\Html\Filter,
    \RS\Html\Toolbar,
    \RS\Html\Tree,
    \RS\Html\Table;
    
/**
* Контроллер Управление скидочными купонами
*/
class DeliveryCtrl extends \RS\Controller\Admin\Crud
{

    /**
     * @var \Shop\Model\DeliveryDirApi $dir_api
     */
    protected $dir_api;
    protected $dir;//Категория
    /**
     * @var \Shop\Model\DeliveryApi $api
     */
    protected $api;

    function __construct()
    {
        parent::__construct(new \Shop\Model\DeliveryApi());
        $this->dir_api = new \Shop\Model\DeliveryDirApi();
    }

    /**
     * Страница доставок
     *
     * @return mixed
     */
    function actionIndex()
    {
        if ($this->dir >= 0){ //Если категория задана
            if (!$this->dir_api->getOneItem($this->dir)) {
                $this->dir = 0; //Если категории не существует, то выбираем пункт "Все"
            }
            $this->api->setFilter('parent_id', $this->dir);
        }

        return parent::actionIndex();
    }

    /**
     * Подготавливаем helper для отображения
     *
     * @return \RS\Controller\Admin\Helper\CrudCollection
     */
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('Добавить способ доставки'))));
        $helper->addCsvButton('shop-delivery');
        $helper->setTopTitle(t('Способы доставки'));
        $helper->setTopHelp($this->fetch('delivery/top_help.tpl'));
        $this->dir = $this->url->request('dir', TYPE_INTEGER, -1);

        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Sort('sortn', t('Порядок'),array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC,'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id') ), 'LinkAttr' => array('class' => 'crud-edit') )),
                new TableType\Image('picture', t('Логотип'), 30, 30, 'xy', array('Hidden' => true, 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id') ), 'LinkAttr' => array('class' => 'crud-edit') )),
                new TableType\Text('description', t('Описание'), array('Hidden' => true)),
                new TableType\Text('min_price', t('Мин. сумма заказа'), array('Hidden' => true)),
                new TableType\Text('max_price', t('Макс. сумма заказа'), array('Hidden' => true)),
                new TableType\Text('min_cnt', t('Мин. количество товаров в заказе'), array('Hidden' => true)),
                new TableType\Userfunc('extrachange_discount', t('Наценка/скидка на доставку'), function($value, $field){    
                    $row = $field->getRow();
                    switch($row['extrachange_discount_type']){
                        case 0: $extrachange_discount_type = t('ед.');
                            break;
                        case 1: $extrachange_discount_type = '%';
                            break;
                    }
                    return $value ? $value." ".$extrachange_discount_type : t('Нет');
                }, array('Hidden' => true)),
                new TableType\Text('class', t('Тип рассчета')),
                new TableType\Text('user_type', t('Доступен для')),
                new TableType\Yesno('public', t('Видим.'), array('Sortable' => SORTABLE_BOTH, 'toggleUrl' => $this->router->getAdminPattern('ajaxTogglePublic', array(':id' => '@id'))
                )),
                new TableType\Actions('id', array(
                      new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                            'attr' => array(
                                '@data-id' => '@id'
                            ))
                      ),
                      new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('Клонировать способ доставки'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),                                 
                      )),
                  ),
                  array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                )
            ),
            
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )
        )));

        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTableTree();

        $helper->setTreeListFunction('selectTreeList');
        $helper->setTree(new Tree\Element( array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'disabledField' => 'hidden',
            'disabledValue' => '1',
            'activeValue' => $this->dir,
            'noExpandCollapseButton' => true,
            'rootItem' => array(
                'id' => -1,
                'title' => t('Все'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'sortable' => true,
            'sortUrl'  => $this->router->getAdminUrl('move_dir'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id', 'c' => $this->url->get('c', TYPE_ARRAY))) )),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('edit_dir', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    ))),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('Клонировать категорию'),
                        'attr' => array(
                            'class' => 'crud-add',
                            '@href' => $this->router->getAdminPattern('clonedir', array(':id' => '~field~')),
                        )
                    ),
                )),
            )),
            'headButtons' => array(
                array(
                    'text' => t('Название группы'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),
                array(
                    'attr' => array(
                        'title' => t('Создать категорию'),
                        'href' => $this->router->getAdminUrl('add_dir'),
                        'class' => 'add crud-add'
                    )
                ),
            ),
        )), $this->dir_api);

        //Устанавливаем нижнюю полосу категорий
        $helper->setTreeBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Delete(null, null, array('attr' =>
                    array('data-url' => $this->router->getAdminUrl('del_dir'))
                )),
            ))));

        //Устанавливаем фильтры по категории
        $helper->setTreeFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                    )
                    ))
                ),
            )),
            'ToAllItems' => array('FieldPrefix' => $this->dir_api->defAlias()),
            'filterVar' => 'c',
            'Caption' => t('Поиск по группам')
        )));
        
        return $helper;
    }

    function actionAdd_dir($primaryKey = null)
    {
        return parent::actionAdd($primaryKey);
    }

    function helperAdd_Dir()
    {
        $this->api = $this->dir_api;
        return parent::helperAdd();
    }

    function actionEdit_dir()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);
        if ($id) $this->dir_api->getElement()->load($id);
        return $this->actionAdd_dir($id);
    }

    function helperEdit_Dir()
    {
        return $this->helperAdd_dir();
    }

    /**
     * Перемещение категорий
     *
     * @return mixed
     */
    function actionMove_dir()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $direction = $this->url->request('flag', TYPE_STRING);
        return $this->result->setSuccess( $this->dir_api->moveElement($from, $to, $direction) )->getOutput();
    }


    /**
     * Удаление категорий
     *
     * @return mixed
     */
    function actionDel_dir()
    {
        $ids = $this->url->request('chk', TYPE_ARRAY, array(), false);
        $this->dir_api->del($ids);
        return $this->result->setSuccess(true)->getOutput();
    }

    /**
     * Клонирование директории
     *
     * @return bool|\RS\Controller\Result\Standard
     * @throws \RS\Controller\ExceptionPageNotFound
     */
    function actionCloneDir()
    {
        $this->setHelper( $this->helperAdd_dir() );
        $id = $this->url->get('id', TYPE_INTEGER);
        $elem = $this->dir_api->getElement();

        if ($elem->load($id)) {
            $clone = $elem->cloneSelf();
            $this->dir_api->setElement($clone);
            $clone_id = $clone['id'];

            return $this->actionAdd_dir($clone_id);
        } else {
            $this->e404();
        }
    }

    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if ($primaryKey === null) {
            $type_keys = array_keys($this->api->getTypes());
            if ($first = reset($type_keys)) {
                $this->api->getElement()->class = $first;
            }
        }
        else{
            $this->api->getElement()->fillZones();
        }
        
        if ($primaryKey == 0 ) $primaryKey = null;
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
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
    * Получает форму для типа доставки
    * 
    */
    function actionGetDeliveryTypeForm()
    {
        $type = $this->url->request('type', TYPE_STRING);
        if ($type_object = $this->api->getTypeByShortName($type)) {
            $this->view->assign('type_object', $type_object);
            $this->result->setTemplate( 'form/delivery/type_form.tpl' );
        }
        return $this->result;
    }
    
    /**
    * Выполняет пользовательский метод доставки, возвращая полученный ответ
    * 
    */
    function actionUserAct(){                                        
       $act          = $this->request('userAct', TYPE_STRING, false); 
       $delivery_obj = $this->request('deliveryObj', TYPE_STRING, false); 
       $params       = $this->request('params', TYPE_ARRAY, array()); 
       $module       = $this->request('module', TYPE_STRING, 'Shop');
       
       if ($act && $delivery_obj){
          $delivery = '\\'.$module.'\Model\DeliveryType\\'.$delivery_obj;
          $data = $delivery::$act($params);
          
          return $this->result->setSuccess(true)
                    ->addSection('data',$data); 
       }else{
          return $this->result->setSuccess(false)
                    ->addEMessage(t('Не установлен метод или объект доставки')); 
       }
    }
    
    /**
    * Включает/выключает флаг "публичный"
    */
    function actionAjaxTogglePublic()
    {

        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        $id = $this->url->get('id', TYPE_STRING);
        
        $delivery = $this->api->getOneItem($id);
        $delivery-> fillZones();
        if ($delivery) {
            $delivery['public'] = !$delivery['public'];
            $delivery->update();
        }
        return $this->result->setSuccess(true);
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
            $clone_id = 0;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = (int)$clone['id']; 
            }
            unset($elem['id']);
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
}
