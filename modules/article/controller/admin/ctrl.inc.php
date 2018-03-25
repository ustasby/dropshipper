<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar,
    \RS\Html\Tree,
    \RS\Html\Filter,
    \RS\Html\Table;

class Ctrl extends \RS\Controller\Admin\Crud
{
    protected
        $dir,
        $dirapi;
    
    function __construct()
    {
        parent::__construct(new \Article\Model\Api());
        $this->dirapi = new \Article\Model\Catapi();
    }
    
    function actionIndex()
    {
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->dir > 0 && !$this->dirapi->getById($this->dir)) $this->dir = 0;
        if ($this->dir >0) $this->api->setFilter('parent', $this->dir);
        $this->getHelper()->setTopTitle(t('Статьи по тематикам'));

        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $collection = parent::helperIndex();
        
        $this->dir = $this->url->request('dir', TYPE_STRING);        
        //Параметры таблицы
        $collection->setTopHelp(t('В этом разделе вы можете размещать текстовую информацию (контент). Если у вас на сайте есть раздел «Новости», то все материалы закрепляются в соответствующей рубрике, здесь также можно размещать информацию.'));
        $collection->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    array(
                        'title' => t('добавить статью'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add', array('dir' => $this->dir)),                        
                            'class' => 'btn-success crud-add'
                        )
                    ),                    
                    array(
                        'title' => t('добавить категорию статей'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add_dir'),                        
                            'class' => 'crud-add'
                        )
                    )
                )),
            ))
        ));            
        $collection->addCsvButton('article-article');
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('short_content', t('Краткий текст'), array('hidden' => true)),
                new TableType\Datetime('dateof', t('Размещено'), array('hidden' => true, 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'TdAttr' => array('class' => 'cell-sgray'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
                                )
                            )),
                            new TableType\Action\DropDown(array(
                                array(
                                    'title' => t('клонировать'),
                                    'attr' => array(
                                        'class' => 'crud-add',
                                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                    )
                                ),
                                array(
                                    'title' => t('показать статью на сайте'),
                                    'attr' => array(
                                        'target' => '_blank',
                                        '@href' => function($row) {
                                            return $row->getUrl();
                                        }
                                    )
                                )
                            ))
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ),                 
            )
        )));
        
        //Параметры фильтра
        $collection->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('id','№', array('Attr' => array('size' => 4))),
                                                            new Filter\Type\Text('title',t('Название'), array('SearchType' => 'like%')),

                                                            new Filter\Type\Date('dateof',t('Дата'), array('ShowType' => true)),
                                                        )
                                    ))
                                ),
                                'SecContainers' => array(
                                    new Filter\Seccontainer(array(
                                        'Lines' => array( 
                                            new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('alias',t('Псевдоним'), array('SearchType' => 'like%'))
                                            ))))
                                    )))
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));

        $collection->setTree(new Tree\Element( array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'pathToFirst' => $this->dirapi->getPathToFirst($this->dir),
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                '_class' => 'root noDraggable',
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noFullValue' => true,
            ),
            'sortable' => true,
            'sortUrl'       => $this->router->getAdminUrl('move_dir'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('edit_dir', array(':id' => '~field~')),null,array(
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
                            'title' => t('клонировать'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clonedir', array(':id' => '~field~', ':pid' => '@parent')),
                            )
                        ),                
                        array(
                            'title' => t('показать на сайте'),
                            'attr' => array(
                                'target' => '_blank',
                                '@href' => function($row) {
                                    if ($row['id'] > 0) {
                                        return $row->getUrl();
                                    }
                                }
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
                        'href' => $this->router->getAdminUrl('add_dir'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        )), $this->dirapi);
        
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
        $parent = $this->url->request('dir', TYPE_INTEGER);
        $obj = $this->api->getElement();
        
        if ($primaryKey === null) {
            if ($parent) {
                $obj['parent'] = $parent;
            }
            if (!isset($primaryKey)) {
                $obj['dateof'] = date('Y-m-d H:i:s');
            }
            $obj->setTemporaryId();            
        }
        
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать статью {title}') : t('Добавить статью'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
    //***** Методы категорий
    
    function actionAdd_dir($primaryKey = null)
    {
        if ($primaryKey === null) {
            $pid = $this->url->request('pid', TYPE_STRING, '');
            $this->dirapi->getElement()->offsetSet('parent', $pid);
        }
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать категорию {title}') : t('Добавить категорию статей'));
        
        return parent::actionAdd($primaryKey);
    }
    
    function helperAdd_Dir()
    {
        $this->api = $this->dirapi;
        return parent::helperAdd();
    }
    
    function actionEdit_dir()
    {
        $id = $this->url->get('id', TYPE_STRING, 0);
        if ($id) $this->dirapi->getElement()->load($id);
        return $this->actionAdd_dir($id);
    }
    
    function helperEdit_Dir()
    {
        $this->api = $this->dirapi;
        return parent::helperEdit();
    }    
    
    function actionDel_dir()
    {
        $ids = $this->url->request('chk', TYPE_ARRAY, array(), false);
        $this->dirapi->del($ids);
        return $this->result->setSuccess(true)->getOutput();
    }
    
    function actionMove_dir()
    {
        $from = $this->url->request('from', TYPE_STRING);
        $to = $this->url->request('to', TYPE_STRING);
        $flag = $this->url->request('flag', TYPE_STRING); //Указывает выше или ниже элемента to находится элемент from
        $parent = $this->url->request('parent', TYPE_INTEGER);

        $this->dirapi->moveElement($from, $to, $flag, null, $parent);
        return $this->result->setSuccess(true)->getOutput();
    }    
    
    function actionMultiedit_dir()
    {
        $this->api = $this->dirapi;
        //Устанавливаем функцию проверки корректности.
        $this->multiedit_check_func = array($this->dirapi, 'multiedit_dir_check');
        return parent::actionMultiedit();        
    }
    
    function helperMultiedit_dir()
    {
        $this->api = $this->dirapi;
        return $this->helperMultiedit();
    }
    
    /**
    * Редактирование статьи
    * 
    */
    function actionEdit()
    {
        $byalias = $this->url->get('byalias', TYPE_STRING, false);
        if (!empty($byalias)) {
            $article = $this->api->getByAlias($byalias);
            $this->redirect($this->router->getAdminUrl('edit', array('id' => $article['id'])));
        }
        return parent::actionEdit();
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
        
        $elem = $this->dirapi->getElement();
        
        if ($elem->load($id)) {
            $clone = $elem->cloneSelf();
            $this->dirapi->setElement($clone);
            $clone_id = $clone['id'];

            return $this->actionAdd_dir($clone_id);
        } else {
            return $this->e404();
        }
    }
}


