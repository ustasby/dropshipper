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
    \RS\Html\Filter,
    \RS\Html\Toolbar,
    \RS\Html\Tree,
    \RS\Html\Table;

class Propctrl extends \RS\Controller\Admin\Crud
{
    protected
        $form_tpl = 'form_prop.tpl',
        $me_form_tpl = 'me_prop.tpl',
        $action_var = 'do',
        $dir_api,
        $dir,
        $api;
        
    function __construct()
    {
        parent::__construct(new \Catalog\Model\PropertyApi());
        $this->dir_api = new \Catalog\Model\PropertyDirApi();
    }
    
    function addResource()
    {
        $this->app->addCss($this->mod_css.'property.css', null, BP_ROOT);
    }
    
    function actionIndex()
    {
        if (!$this->dir_api->getOneItem($this->dir)) {
            $this->dir = 0; //Если категории не существует, то выбираем пункт "Все"
        }
        $this->api->setFilter('parent_id', $this->dir);            
        
        return parent::actionIndex();
    }
    
    function helperIndex()
    {        
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Характеристики'));
        $this->dir = $this->url->request('dir', TYPE_INTEGER);
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить характеристику'))));
        //$helper->addCsvButton('catalog-property');
        $helper['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт характеристик в CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-property', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт значений характеристик в CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-propertyvalue', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт характеристик из CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-property', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт значений характеристик из CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-propertyvalue', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            )
        )), 'import');
        
        $helper->setTopHelp($this->view->fetch('help/propctrl_index.tpl'));
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTableTree();
        
        $helper->setTable(new Table\Element(array(
                'Columns' => array(
                    new TableType\Checkbox('id', array('showSelectAll' => true)),
                    new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC,'ThAttr' => array('width' => '20'))),                    
                    new TableType\Text('title', t('Название'), array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),
                    new TableType\Text('unit', t('Ед. изм')),
                    new TableType\Text('type', t('Тип')),
                    new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                    new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~', 'dir' => $this->dir)), null, 
                        array(
                            'attr' => array(
                            '@data-id' => '@id'
                        ))),
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('Клонировать характеристику'),
                                'attr' => array(
                                    'class' => 'crud-add',
                                    '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                )
                            ),  
                            array(
                                'title' => t('удалить'),
                                'class' => 'crud-get',
                                'attr' => array(
                                    '@href' => $this->router->getAdminPattern('del', array(':chk[]' => '@id')),
                                )
                            ),
                        ))),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                        ),                                        
                ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )                
        )));
        
        $helper->setTreeListFunction('selectTreeList');
        $helper->setTree(new Tree\Element( array(        
            'sortIdField' => 'id',
            'activeField' => 'id',
            'disabledField' => 'hidden',
            'disabledValue' => '1',
            'activeValue' => $this->dir,
            'noExpandCollapseButton' => true,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Без группы'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'sortable' => true,
            'sortUrl'       => $this->router->getAdminUrl('move_dir'),
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
        
        $helper->setTreeBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del_dir'))
                )),
        ))));

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

        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                        new Filter\Type\Select('type', t('Тип'), array('' => t('Любой')) + \Catalog\Model\Orm\Property\Item::getAllowTypeValues()),
                    )
                    ))
                ))),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias()),
            'AddParam' => array('hiddenfields' => array('dir' => $this->dir)),
            'Caption' => t('Поиск по характеристикам')
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
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $dir = $this->url->request('dir', TYPE_INTEGER);
        if ($primaryKey === null) {
            $elem = $this->api->getElement();
            $elem['parent_id'] = $dir;
            $elem->setTemporaryId();            
        }
        
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать характеристику {title}') : t('Добавить характеристику'));
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    
    /**
    * AJAX
    */
    function actionSaveProperty()
    {
        return $this->result->setSuccess( $this->api->saveProperty() )->getOutput();
    }
    
    /**
    * AJAX
    */
    function actionDelProperty()
    {
        $aliases = $this->url->request('aliases', TYPE_ARRAY, array());
        return $this->result->setSuccess( $this->api->del($aliases) )->getOutput();
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
    
    function actionMove_dir()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $direction = $this->url->request('flag', TYPE_STRING);
        $result = $this->dir_api->moveElement($from, $to, $direction);
        if ($result) {
            $prop_api = new \Catalog\Model\Propertyapi();
            $prop_api->updateParentSort($from, $to);
        }
        $this->result->setSuccess($result);
        return $this->result->getOutput();
    }
    
    function actionDel_dir()
    {
        $ids = $this->url->request('chk', TYPE_ARRAY, array(), false);
        $this->dir_api->del($ids);
        return $this->result->setSuccess(true)->getOutput();
    }
    
    /**
    * Возвращает список категорий характеристик
    */
    function actionAjaxGetPropertyList()
    {
        $dir_api = new \Catalog\Model\PropertyDirApi();
        $groups = array(0 => array('title' => t('Без группы'))) + $dir_api->getListAsResource()->fetchSelected('id');

        $propapi = new \Catalog\Model\PropertyApi();
        $propapi->setOrder('parent_id, sortn');
        $proplist = $propapi->getListAsResource()->fetchAll();
        
        $types = \Catalog\Model\Orm\Property\Item::getAllowTypeData();

        $result = array(
            'properties_sorted' => $proplist,
            'groups' => $groups,
            'types' => $types
        );
        
        return json_encode($result);
    }
    
    /**
    * Возвращает информацию о возможных значениях характеристики
    */
    function actionAjaxGetPropertyValueList()
    {
        $prop_id = $this->url->request('prop_id', TYPE_INTEGER);
        
        $property_value_api = new \Catalog\Model\PropertyValueApi();
        $property_value_api->setFilter('prop_id', $prop_id);
        $property_value_api->setFilter('value', '', '!=');
        
        $values = $property_value_api->getListAsResource()->fetchAll();
        
        return $this->result->addSection(array(
            'property_values' => $values
        ));
    }
    
    /**
    * Добавляет одно значение характеристики
    */
    function actionAjaxAddPropertyValue()
    {
        $data = array(
            'prop_id' => $this->url->post('prop_id', TYPE_INTEGER),
            'value' => $this->url->post('value', TYPE_STRING)
        );
        
        $item_value = \Catalog\Model\Orm\Property\ItemValue::loadByWhere($data);
        
        if ($item_value['id']) {
            return $this->result->addEMessage(t('Такое значение уже присутствует в списке'));
        }
        
        $item_value->getFromArray($data);
        $this->result->setSuccess($item_value->save(null, array(), $data));
        
        if ($this->result->isSuccess()) {
            $this->result->addSection('item_value', $item_value->getValues());
        } else {
            $this->result->addEMessage($item_value->getErrorsStr());
        }
        
        return $this->result;
    }
    
    /**
    * Создает или обноляет характеристику
    */
    function actionAjaxCreateOrUpdateProperty()
    {
        $item = $this->url->request('item', TYPE_ARRAY);
        
        $prop_api = new \Catalog\Model\PropertyApi();
        $this->result->setSuccess( $result = $prop_api->createOrUpdate($item) );
        
        if ($result) {
            $this->view->assign('group', array(
                'group' => $result['group'],
            ));
            $this->view->assign(array(
                'properties' => array($result['property']),
                'owner_type' => $item['owner_type']
            ));
            
            $this->result->addSection('group', $result['group']->getValues());
            $this->result->addSection('prop', $result['property']->getValues());
            $this->result->addSection('group_html', $this->view->fetch('property_group_product.tpl'));
            $this->result->addSection('property_html', $this->view->fetch('property_product.tpl'));
        } else {
            $this->result->setErrors($prop_api->getElement()->getDisplayErrors());
        }
        return $this->result->getOutput();
    }
    
    /**
    * Удаляет значение характеристики
    */
    function actionAjaxRemovePropertyValue()
    {
        $value_id = $this->url->request('id', TYPE_INTEGER);
        $item_value = new \Catalog\Model\Orm\Property\ItemValue($value_id);
        
        return $this->result->setSuccess($item_value->delete());
    }
    
    /**
    * Возвращает список подготовленных свойств для вставки на страницу
    */
    function actionAjaxGetSomeProperties()
    {
        $ids = $this->url->request('ids', TYPE_ARRAY);
        
        $prop_api = new \Catalog\Model\PropertyApi();
        $list = $prop_api->getPropertiesAndGroup($ids);
        
        $result = array();
        foreach($list['groups'] as $group_id => $group) {
            if (isset($list['properties'][$group_id])) {
                
                $this->view->assign(array(
                    'group' => array('group' => $group),
                    'owner_type' => 'group'
                ));
                $group_html = $this->view->fetch('property_group_product.tpl');
                
                foreach($list['properties'][$group_id] as $property) {
                    $property['is_my'] = true;
                    $this->view->assign(array(
                        'properties' => array($property)
                    ));
                    
                    $property_html = $this->view->fetch('property_product.tpl');
                    
                    $result[] = array(
                        'group' => $group->getValues(),
                        'prop' => $property->getValues(),
                        'group_html' => $group_html,
                        'property_html' => $property_html
                    );
                }

            }
        }
        $this->result->addSection('result', $result);
        return $this->result->getOutput();
    }
    
    /**
    * Клонирование директории
    * 
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
}

