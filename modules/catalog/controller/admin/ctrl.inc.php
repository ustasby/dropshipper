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
    \RS\Html\Tree,
    \RS\Html\Table,
    \RS\Html\Filter;

/**
* Контроллер каталога товаров
* @ingroup Catalog
*/
class Ctrl extends \RS\Controller\Admin\Crud
{
    CONST 
        SHOW_CHILDS_VAR = 'showchild';
        
    public
        $dir,
        $brandlist,
        $brandapi,
        /**
        * @var \Catalog\Model\Dirapi $dirapi
        */
        $dirapi,
        /**
        * @var \Catalog\Model\Api $api
        */
        $api,
        $showchilds,
        $me_form_tpl_dir = 'me_form_dir.tpl';
    
    function __construct()
    {        
        parent::__construct(new \Catalog\Model\Api());
        $this->dirapi = new \Catalog\Model\Dirapi();
    }
    
    function actionIndex()
    {
        if ($this->dir>0) {
            if (!$this->dirapi->getOneItem($this->dir)) {
                $this->dir = 0; //Если категории не существует, то выбираем пункт "Все"
            } else {
                if ($this->showchilds) {
                    $child_dir_ids = \Catalog\Model\Dirapi::getInstance()->getChildsId($this->dir);
                    $this->api->setFilter('dir', $child_dir_ids, 'in');
                } else {
                    $this->api->setFilter('dir', $this->dir);
                }
            }
        }
        $this->getHelper()->setTopHelp($this->view->fetch('help/ctrl_index.tpl'));
        
        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Каталог товаров'));
        $this->dir = $this->url->get('dir', TYPE_INTEGER, 0);
        
        $this->showchilds = $this->url->get(self::SHOW_CHILDS_VAR, TYPE_INTEGER, false);
        if ($this->showchilds === false) {
            $this->showchilds = $this->url->cookie(self::SHOW_CHILDS_VAR, TYPE_INTEGER, 0);
        } else {
            $this->app->headers->addCookie(self::SHOW_CHILDS_VAR, $this->showchilds, time()+(60*60*365*10));
        }
        
        //Загружаем информацию о характеристиках текущей группы
        $property_api = new \Catalog\Model\PropertyApi();
        $group_properties_list = $property_api->getGroupProperty($this->dir);
        
        $group_properties = array();
        foreach($group_properties_list as $items) {
            $group_properties += $items['properties'];
        }
        
        $helper->viewAsTableTree();
        $dir = $this->dir;
        $this->api->queryObj()->select = 'DISTINCT A.*';
        
        //Добавляем в колонки с типами цен
        $cost_api = new \Catalog\Model\CostApi();
        $cost_api->setFilter('type', 'manual');
        $cost_types = $cost_api->getList();
        $cost_columns = array();
        foreach($cost_types as $cost_type) {
            $cost_columns[] = new TableType\Text('cost_'.$cost_type['id'], t('Цена ').$cost_type['title'], array(
                'Sortable' => SORTABLE_BOTH, 
                'hidden' => true, 
                'cost_type' => $cost_type));
        }
        
        $helper->setListFunction('getTableList');
        $helper->setTable(new Table\Element(array(
            'Columns' => array_merge(array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Text('title', t('Название'), array(
                'LinkAttr' => array(
                    'class' => 'crud-edit'
                ),
                'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $dir)), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)),
                new TableType\Image('images', t('Фото'), 30, 30, 'xy', array(
                    'LinkAttr' => array(
                        'class' => 'crud-edit',
                    ),
                    'Sortable' => SORTABLE_BOTH,'CurrentSort' => SORTABLE_ASC,
                    'href' =>  $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $dir)),
                    'TdAttr' => array(
                        'style' => 'padding-top:0; padding-bottom:0;'
                    )
                )),
                new TableType\Text('barcode', t('Артикул'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('num', t('Остаток'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Text('dateof', t('Дата поступления'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Text('brand_id', t('Бренд'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Text('sortn', t('Сорт. вес'), array('Sortable' => SORTABLE_ASC)),
                new TableType\Text('group_id', t('Группа'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Yesno('public', t('Видим.'), array('Sortable' => SORTABLE_BOTH, 'toggleUrl' => $this->router->getAdminPattern('ajaxTogglePublic', array(':id' => '@id'))
                )),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'), 'ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH)),

            ),
            $cost_columns,
            array(
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~', 'dir' => $dir)), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('удалить'),
                            'attr' => array(
                                'class' => 'crud-get',
                                'data-confirm-text' => t('Вы действительно хотите удалить данный товар?'),
                                '@href' => $this->router->getAdminPattern('del', array(':chk[]' => '@id')),
                            )
                        ),                    
                        array(
                            'title' => t('показать товар на сайте'),
                            'attr' => array(
                                'target' => '_blank',
                                '@href' => $this->router->getUrlPattern('catalog-front-product', array(':id' => '@_alias'), false),
                            )
                        ),
                        array(
                            'title' => t('клонировать товар'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '@id')),
                            )
                        )                        
                        
                    ))),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            ))
        )));
        
        //Добавляем условия для выборки цен, если колонки отображаются
        $helper['table']->fill();
        $add_cost_types = array();
        foreach($helper['table']->getTable()->getColumns() as $n => $col) {
            if (isset($col->property['cost_type'])) {
                if (!$col->isHidden()) {
                    $add_cost_types[] = $col->property['cost_type'];
                }
            }
        }
        $this->api->addCostQuery($add_cost_types);
        
        $helper->setTreeListFunction('listWithAll');
        $tree = new Tree\Element( array(
            'disabledField' => 'public',
            'classField' => '_class',
            'disabledValue' => '0',
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $dir,
            'rootItem' => array(
                'id' => 0,
                'name' => t('Все'),
                '_class' => 'root noDraggable',
                //Устанавливаем собственные инструменты
                'treeTools' => new \RS\Html\Table\Type\Actions('id', array(
                    new \RS\Html\Table\Type\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit_dir', array('id' => 0))),
                )),
                'noDraggable' => true,
                'noCheckbox' => true,
                'noRedMarker' => true
            ),
            'sortable' => true,
            'sortUrl'       => $this->router->getAdminUrl('move_dir'),
            'mainColumn' => new TableType\Usertpl('name', t('Название'), '%catalog%/tree_item_cell.tpl', array(
                'href' => $this->router->getAdminPattern(false, array(':dir' => '@id', 'c' => $this->url->get('c', TYPE_ARRAY) ))
            )),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('edit_dir', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    )
                )),
                new TableType\Action\DropDown(array(
                        array(
                            'title' => t('добавить дочернюю категорию'),
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern('add_dir', array(':pid' => '~field~')),
                                'class' => 'crud-add'
                            )
                        ),
                        array(
                            'title' => t('клонировать категорию'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clonedir', array(':id' => '~field~', ':pid' => '@parent')),
                            )
                        ),   
                        array(
                            'title' => t('показать на сайте'),
                            'attr' => array(
                                '@href' => $this->router->getUrlPattern('catalog-front-listproducts', array(':category' => '@_alias')),
                                'target' => 'blank'
                            )
                        ),                        
                        array(
                            'title' => t('удалить'),
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern('del_dir', array(':chk[]' => '~field~')),
                                'class' => 'crud-remove-one'
                            )
                        ),
                    )))
            ),
            'headButtons' => array(
                array(
                    'attr' => array(
                        'title' => t('Создать категорию'),
                        'href' => $this->router->getAdminUrl('add_dir', array('pid' => $dir)),
                        'class' => 'add crud-add'
                    )
                ),
                array(
                    'attr' => array(
                        'title' => t('Создать спец. категорию'),
                        'href' => $this->router->getAdminUrl('add_dir', array('spec' => 1)),
                        'class' => 'addspec crud-add'
                    )
                ),
                $this->showchilds ? 
                array(
                    'attr' => array(
                        'title' => t('Включено отображение товаров в подкатегориях. Нажмите, чтобы отключить'),
                        'href' => $this->url->replaceKey(array(self::SHOW_CHILDS_VAR => 0)),
                        'class' => 'showchilds-on call-update'
                    )
                ) : array(
                    'attr' => array(
                        'title' => t('Отключено отображение товаров в подкатегориях. Нажмите, чтобы включить'),
                        'href' => $this->url->replaceKey(array(self::SHOW_CHILDS_VAR => 1)),
                        'class' => 'showchilds-off call-update'
                    )                
                )
            ),
        ));
        $helper->setTree($tree, $this->dirapi);
        
        $helper->setTreeFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('Items' => array(
                                                            new Filter\Type\Text('name', t('Название'), array('SearchType' => '%like%', 'attr' => array('style' => 'width:300px'))),
                                                        )
                                    ))
                                ),
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->dirapi->defAlias()),
            'filterVar' => 'c',            
            'Caption' => t('Поиск по категориям')
        ))); 
        
        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('Items' => array(
                                                            new Filter\Type\Text('id','№'),
                                                            new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('barcode', t('Артикул'), array('SearchType' => '%like%')),
                                                            new Filter\Type\Text('num', t('Общий остаток'), array('Attr' => array('class' => 'w60'), 'showType' => true)),
                                                            new Filter\Type\Select('public', t('Публичный'), array(''=>t('Неважно'),'1' => t('Да'),'0'=>t('Нет')))
                                                        )
                                    ))
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer(array(
                                        'Lines' => array(
                                            new Filter\Line( array(
                                                'Items' => array(
                                                    new Filter\Type\Date('dateof', t('Дата поступления'), array('showtype' => true)),
                                                    new Filter\Type\Select('brand_id', t('Бренд'), array('' => t('Любой')) + \Catalog\Model\BrandApi::staticSelectList(false)),
                                                    new Filter\Type\Text('group_id', t('Группа'), array('SearchType' => '%like%'))
                                                )
                                            )),
                                            new Filter\Line( array(
                                                'Items' => array(
                                                    new \Catalog\Model\Filter\PropertyFilter($group_properties)
                                                )
                                            ))                                            
                                        )
                                ))
                            ))),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias()),
            'AddParam' => array('hiddenfields' => array('dir' => $dir)),
            'Caption' => t('Поиск по товарам')
        )));
        
        $dir_count = $this->dirapi->getListCount();
        
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    $dir_count > 0 ?
                        array(
                            'title' => t('добавить товар'),
                            'attr' => array(
                                'href' => $this->router->getAdminUrl('add', array('dir' => $dir)),                        
                                'class' => 'btn-success crud-add'
                            )
                        ) : null,
                    
                    array(
                        'title' => t('добавить категорию'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add_dir', array('pid' => $dir)),
                            'class' => 'crud-add'.($dir_count == 0 ? ' btn-success' : '')
                        )
                    ),
                    array(
                        'title' => t('добавить спецкатегорию'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add_dir', array('spec' => 1)),                        
                            'class' => 'crud-add'
                        )
                    )
                    
                )),
            ))
        ));
        
        
        $helper['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт')
            ),
            array(
                'title' => t('Экспорт категорий в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-dir', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт товаров в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-product', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт комплектаций в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-offer', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт остатков и цен в CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-simplepricestockupdate', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),    
            array(
                'title' => t('Импорт категорий из CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-dir', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),            
            array(
                'title' => t('Импорт товаров из CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-product', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт комплектаций из CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-offer', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),       
            array(
                'title' => t('Импорт остатков и цен из CSV'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-simplepricestockupdate', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),      
            array(
                'title' => t('Импорт изображений из ZIP-архива'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl(false, array('referer' => $this->url->selfUri()), 'catalog-importphotos'),
                    'class' => 'crud-add'
                )
            ),           
            array(
                'title' => t('Импорт товаров из YML'),
                'attr' => array(
                    'data-url' => \RS\Router\Manager::obj()->getAdminUrl(false, array('referer' => $this->url->selfUri()), 'catalog-importyml'),
                    'class' => 'crud-add'
                )
            ),         
        )), 'import');        
        
        $helper->addHiddenFields(array('dir' => $dir));
        
        $helper->setTreeBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\DropUp(array(
                    array(
                        'title' => t('редактировать'),
                        'attr' => array(
                            'data-url' => $this->router->getAdminUrl('multiedit_dir'),
                            'class' => 'btn-alt btn-primary crud-multiedit'
                        ),
                    )
                ), array('attr' => array('class' => 'edit'))),
                
                new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del_dir'))
                )),
        ))));
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\DropUp(array(
                    array(
                        'title' => t('редактировать'),
                        'attr' => array(
                            'data-url' => $this->router->getAdminUrl('multiedit'),
                            'class' => 'crud-multiedit'
                            ),
                        ),
                ), array('attr' => array('class' => 'edit'))),
                new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del'))
                )),
            )
        )));
        return $helper;
    }

    /**
     * Метод переключения флага публичности
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxTogglePublic()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        $id = $this->url->get('id', TYPE_STRING);
        
        $product = $this->api->getOneItem($id);
        if ($product) {
            $product['public'] = !$product['public'];
            $product->update();
        }
        return $this->result->setSuccess(true);
    }
    
    /**
    * Удаление товаров по параметру chk
    * 
    */
    function actionDel()
    {
        @set_time_limit(200);
        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array(), false) );
        $dir = $this->url->request('dir', TYPE_INTEGER, 0);
        
        $success = $this->api->multiDelete($ids, $dir);
        
        if(!$success){
            foreach($this->api->getErrors() as $error) {
                $this->result->addEMessage($error);
            }
        }
        return $this->result->setSuccess( $success )->getOutput();
    }
    
    function actionExport()
    {
        $ids = $this-> _modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array()) );
        if (!empty($ids))
        {
            if ($this->url->request($this->selectAllVar, TYPE_STRING) != '')
            {
                $where = $_SESSION[$this->controller_name.$this->sess_where];
                $this->api->setWhere($where['where']);
                $this->api->setFrom($where['from']);
            } else {
                $this->api->clearFilter();
                $this->api->setFilter('id', $ids, 'in');
            }
            $res = $this->api->getListAsResource();
            $this->view->assign('resource', $res);
            $this->view->assign('count', $res->rowCount());
        }

        return $this->fetch('export.tpl');        
    }
    
    /**
    * AJAX
    */
    function actionGetPropertyList()
    {
        $propapi = new \Catalog\Model\Propertyapi();
        $list = $propapi->getList();
        $this->view->assign('list', $list);
        return $this->view->fetch('property_full_list.tpl');
    }
    
    /**
    * Открытие окна добавления и редактирования категории
    * 
    * @param integer $primaryKeyValue - первичный ключ записи, передаётся для редактирования
    * @return sting
    */
    function actionAdd_dir($primaryKeyValue = null)
    {
        $spec = $this->url->request('spec', TYPE_INTEGER); //действие со спец категорией.        
        $elem = $this->dirapi->getElement();
        //Для SEO генерации подсказок заменяем HINT надписи
        $seoGen = new \Catalog\Model\SeoReplace\Dir();
        $seoGen->replaceORMHint($elem);
        
        $seoGenProduct = new \Catalog\Model\SeoReplace\Product();
        $seoGenProduct->hint_fields = array(
            'product_meta_title',
            'product_meta_keywords',
            'product_meta_description'
        );
        $seoGenProduct->replaceORMHint($elem);
        
        //Проверка на спецкатегорию
        if ($primaryKeyValue !== null && $elem['is_spec_dir'] == 'Y') $spec = 1;
        
        if ($spec) {
            $elem['is_spec_dir'] = 'Y';
            $elem['parent'] = 0;
            $elem['__alias']->setChecker('chkEmpty', t('Необходимо указать Псевдоним'));
        }
        
        if ($primaryKeyValue === null) {
            $this->getHelper()->setTopTitle(t('Добавить категорию товаров'));
            $elem['parent'] = $this->url->get('pid', TYPE_INTEGER, 0);
            $elem['public'] = 1;
        } else {
            $this->getHelper()->setTopTitle(t('Редактировать категорию').' {name}');
            if (empty($_POST)) $elem->fillProperty();
        }

        return parent::actionAdd($primaryKeyValue);
    }
    
    function helperAdd_dir()
    {
        $this->api = $this->dirapi;
        
        $helper = parent::helperAdd();
        $spec = $this->url->request('spec', TYPE_INTEGER); //действие со спец категорией.        
        $elem = $this->dirapi->getElement();
        if ($elem->id && $elem['is_spec_dir'] == 'Y') $spec = 1;
        
        if ($spec) {
            $helper->setFormSwitch('spec');
        } 
        
        return $helper;
    }

    function actionEdit_dir()
    {
        return $this->actionAdd_dir($this->dirapi->getElement()->id);
    }
    
    function helperEdit_Dir()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);
        if ($id) {
            $this->dirapi->getElement()->load($id);
        } else {
            $this->dirapi->getElement()->id = '0'; //Необходимо всвязи с редактирование категории - 0
            $this->dirapi->getElement()->name = t('Все');   
        }
        $helper = $this->helperAdd_dir();            
        if (!$id) $helper->setFormSwitch('root');
                
        return $helper;
    }
    
    function actionMove_dir()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $parent = $this->url->request('parent', TYPE_INTEGER);
        $flag = $this->url->request('flag', TYPE_STRING); //Указывает выше или ниже элемента to находится элемент from
        
        if ($this->dirapi->moveElement($from, $to, $flag, null, $parent)) {
            $this->result->setSuccess(true);
        } else {
            $this->result->setSuccess(false)->setErrors($this->dirapi->getErrors());
        }
        return $this->result->getOutput();
    }

    function actionDel_dir()
    {
        $ids = $this->url->request('chk', TYPE_ARRAY, array(), false);
        $success = $this->dirapi->del($ids);
        if(!$success){
            foreach($this->dirapi->getErrors() as $error) {
                $this->result->addEMessage($error);
            }
        }
        return $this->result
            ->setSuccess( $success )
            ->setNoAjaxRedirect( $this->url->getSavedUrl($this->controller_name.'index') )
            ->getOutput();
    }
    
    function actionMultiedit_dir()
    {
        $this->api = $this->dirapi; 
        $dir = $this->api->getElement();
        //Для SEO генерации подсказок заменяем HINT надписи
        $seoGen = new \Catalog\Model\SeoReplace\Dir();
        $seoGen->replaceORMHint($dir);
               
        $this->setHelper( $this->helperMultiedit() );
        $this->me_form_tpl = $this->me_form_tpl_dir;
        $this -> multiedit_check_func = array($this->dirapi, 'checkParent');
        
        return parent::actionMultiedit();
    }

    /**
    * Открытие окна добавления и редактирования товара
    * 
    * @param integer $primaryKeyValue - первичный ключ товара(если товар уже создан)
    * @return sting
    */
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        /**
        * @var \Catalog\Model\Orm\Product
        */
        $obj = $this->api->getElement();    
        $obj->useOffersUnconvertedPropsdata(true);
        //Для SEO генерации подсказок заменяем HINT надписи
        $seoGen = new \Catalog\Model\SeoReplace\Product();
        $seoGen->replaceORMHint($obj,"%catalog%/hint/seohint.tpl");
           
                
        if ($primaryKeyValue <= 0 )
        {
            if ($primaryKeyValue == 0) {
                $dir = $this->url->get('dir', TYPE_INTEGER);
                $spec_dirs = $obj->getSpecDirs();
                if (isset($spec_dirs[$dir])) {
                    $obj['xspec'] = array( $dir );
                } else {
                    $obj['xdir'] = array( $dir );
                }
                $obj['barcode'] = $this->api->genereteBarcode();
                
                $obj->setTemporaryId();
                $obj['dateof'] = date('Y-m-d H:i:s');
                $obj['public'] = 1;
            }
            $this->getHelper()->setTopTitle(t('Добавить товар'));
        } else {
            $obj->fillCategories();
            $obj->fillCost();
            $obj->fillOffers();
            $obj->fillOffersStock();
            $this->getHelper()->setTopTitle(t('Редактировать товар ').'{title}');
        }
        if (!$this->url->isPost()) $obj->fillProperty();        
        
        if ($this->url->isPost() && $this->url->request('prop', TYPE_ARRAY, null) === null) {
            $this->user_post_data = array('prop' => array()); //На случай, когда удалены все характеристики
        }
        
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }
    
    function helperEdit()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);
        $product = $this->api->getElement();
        $product->load($id);
        
        $helper = parent::helperEdit();
        $helper['bottomToolbar']
            ->addItem(
                new ToolbarButton\Button($this->router->getUrl('catalog-front-product', array('id' => $product['_alias']), false), t('Посмотреть на сайте'), array(
                    'attr' => array(
                        'target' => '_blank'
                    )
                )), 'view'
            )
            ->addItem(
                new ToolbarButton\delete( $this->router->getAdminUrl('delProd', array('id' => $id, 'dialogMode' => $this->url->request('dialogMode', TYPE_INTEGER))), null, array(
                    'noajax' => true,
                    'attr' => array(
                        'class' => 'btn-alt btn-danger delete crud-get crud-close-dialog',
                        'data-confirm-text' => t('Вы действтельно хотите удалить данный товар из всех категорий?')
                    )
                )), 'delete'
            );
        
        return $helper;
    }
    
    function actionDelProd()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        if (!empty($id))
        {
            $obj = $this->api->getElement();
            $obj['id'] = $id;
            $obj->delete();
        }
        
        if (!$this->url->request('dialogMode', TYPE_INTEGER)) {
            $this->result->setAjaxWindowRedirect($this->url->getSavedUrl($this->controller_name.'index'));
        }
        
        return $this->result
            ->setSuccess(true)
            ->setNoAjaxRedirect($this->url->getSavedUrl($this->controller_name.'index'));
    }
    
    /**
    * @deprecated
    */
    function successSave()
    {
        if ($this->url->request('addfotonext', TYPE_STRING,'no') == 'yes') {
            $obj = $this->api->getElement();
            
            header('location: '.$this->url->replaceKey(array($this->action_var => 'edit', 'id' => $obj['id']), array(), '', 'tab-tab2'));
            exit;
        }
        parent::successSave();
    }
    
    /**
    * Вызывает окно мультиредактирования
    * 
    */
    function actionMultiedit()
    {
        $costapi = new \Catalog\Model\Costapi();
        $this->param['addparam']['cost_list'] = $costapi->getList();
        //Для SEO генерации подсказок заменяем HINT надписи
        $elem   = $this->api->getElement();
        $seoGen = new \Catalog\Model\SeoReplace\Product();
        $seoGen->replaceORMHint($elem,"%catalog%/hint/seohint.tpl");
        
        $doedit = $this->url->request('doedit', TYPE_ARRAY, array());
        $xdir = $this->url->post('xdir', TYPE_ARRAY);
        if (in_array('xdir', $doedit) && !isset($xdir['notdelbefore'])) $doedit[] = 'maindir';
        if (in_array('num', $doedit)) $doedit[]  = 'unit';
        $this->url->set('doedit', $doedit, REQUEST);
                
        return parent::actionMultiedit(array('xspec'));
    }
    
    /**
    * Клонирование товара
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
            unset($elem['alias']);
            unset($elem['xml_id']);
            unset($elem['comments']);
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
    
    /**
    * Клонирование директории
    * 
    */
    function actionCloneDir()
    {
        $this->setHelper( $this->helperAdd_dir() );
        $id = $this->url->get('id', TYPE_INTEGER);
        
        $elem = $this->dirapi->getElement();
        
        if ($elem->load($id)) {
            $clone_id = null;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->dirapi->setElement($clone);
                $clone_id = $clone['id'];
            }
            unset($elem['id']);
            unset($elem['alias']);
            unset($elem['xml_id']);
            unset($elem['sortn']);
            
            return $this->actionAdd_dir($clone_id);
        } else {
            return $this->e404();
        }
    }
    
    /**
    * Возвращает форму одной характеристики для виртуальной категории
    */
    function actionAddVirtualDirPropery()
    {
        $prop_id = $this->url->request('prop_id', TYPE_INTEGER);
        
        $virtual_dir = new \Catalog\Model\VirtualDir();
        $form = $virtual_dir->getPropertyFilterForm($prop_id);
        
        if (!$form) {
            $this->e404(t('Характеристика не найдена'));
        }
        
        return $this->result->setSuccess(true)->setHtml($form);
    }
}
