<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar,
    \RS\Html\Tree,
    \RS\Html\Table;


class Ctrl extends \RS\Controller\Admin\Crud
{
    public
        $zone,
        $zone_api;
    
    function __construct()
    {
        parent::__construct(new \Banners\Model\BannerApi());
        $this->zone_api = new \Banners\Model\ZoneApi();
    }
    
    function actionIndex()
    {
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->zone > 0 && !$this->zone_api->getById($this->zone)) $this->zone = 0;
        if ($this->zone > 0) $this->api->setFilter('zone_id', $this->zone);
        $this->getHelper()->setTopTitle(t('Баннеры'));

        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $collection = parent::helperIndex();
        
        $this->zone = $this->url->request('zone', TYPE_STRING);        
        //Параметры таблицы
        $collection->setTopHelp(t('Используйте баннеры, чтобы в яркой форме донести до ваших пользователей важную информацию. Баннеры - это картинки, которые могут размещаться в баннерных зонах. Баннерная зона - это группа банеров. На сайте можно размещать баннерные зоны в удобных местах с помощью раздела <i>Веб-сайт &rarr; Конструктора сайта</i>. Устанавливая различный вес баннерам, можно управлять их сортировкой или вероятностью их отображения в определенных случаях.'), array(), 'Баннеры. Описание раздела');
        $collection->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить баннер'))));
        $collection['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт зон в CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'banners-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт баннеров в CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => 'banners-banner', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт зон из CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'banners-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),            
            array(
                'title' => t('Импорт баннеров из CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => 'banners-banner', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            )
        )), 'import');                
        
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => '20'), 'TdAttr' => array('align' => 'center'))),
                new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Yesno('public', t('Публичный'), array('toggleUrl' => $this->router->getAdminPattern('ajaxTogglePublic', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('link', t('Ссылка')),
                new TableType\Text('weight', t('Вес'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
                                )
                            )),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('клонировать баннер'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),  
                            )) 
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ),                 
            )
        )));
        
        $collection->setTreeListFunction('selectTreeList');
        $collection->setTree(new Tree\Element( array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->zone,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'noExpandCollapseButton' => true,
            'sortable' => false,
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':zone' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit_dir', array(':id' => '~field~')),null,array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('клонировать зону'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clonedir', array(':id' => '~field~')),
                            )
                        ),  
                    )) 
                )
            ),
            'headButtons' => array(
                array(
                    'attr' => array(
                        'title' => t('Создать зону'),
                        'href' => $this->router->getAdminUrl('add_dir'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        )), $this->zone_api);
        
        $collection->setTreeBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Multiedit($this->router->getAdminUrl('multiedit_dir')),
                new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del_dir'))
                )),
        ))));
        
        $collection->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        
        $collection->viewAsTableTree();
        return $collection;
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $zone_id = $this->url->request('zone', TYPE_INTEGER);
        $obj = $this->api->getElement();
        
        if ($primaryKey === null) {
            if ($zone_id) {
                $obj['xzone'] = array($zone_id);
            }
        } else {
            $obj->fillZones();
        }
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    function actionAjaxTogglePublic()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        
        $id = $this->url->get('id', TYPE_STRING);
        
        $banner = $this->api->getOneItem($id);
        if ($banner) {
            $banner['public'] = !$banner['public'];
            $banner->update();
        }
        return $this->result->setSuccess(true);
    }
        
    
    //***** Методы работы с зонами
    function helperAdd_Dir()
    {
        $this->api = $this->zone_api;
        return parent::helperAdd();
    }
    
    function actionAdd_dir($primaryKey = null)
    {
        return parent::actionAdd($primaryKey);
    }
    
    function actionEdit_dir()
    {
        $id = $this->url->get('id', TYPE_STRING, 0);
        if ($id) $this->zone_api->getElement()->load($id);
        return $this->actionAdd_dir($id);
    }
    
    function helperEdit_Dir()
    {
        $this->api = $this->zone_api;
        return parent::helperEdit();
    }    
    
    function actionDel_dir()
    {
        $ids = $this->url->request('chk', TYPE_ARRAY, array(), false);
        $this->zone_api->del($ids);
        return $this->result->setSuccess(true);
    }
    
    function actionMultiedit_dir()
    {
        $this->api = $this->zone_api;
        //Устанавливаем функцию проверки корректности.
        $this->multiedit_check_func = array($this->zone_api, 'multiedit_dir_check');
        return parent::actionMultiedit();        
    }
    
    function helperMultiedit_dir()
    {
        $this->api = $this->zone_api;
        return $this->helperMultiedit();
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
        
        $elem = $this->zone_api->getElement();
        
        if ($elem->load($id)) {
            $clone = $elem->cloneSelf();
            $this->zone_api->setElement($clone);
            $clone_id = $clone['id'];

            return $this->actionAdd_dir($clone_id);
        } else {
            return $this->e404();
        }
    }
}
