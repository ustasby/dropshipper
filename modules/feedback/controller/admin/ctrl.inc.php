<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Controller\Admin;
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
        
        parent::__construct(new \Feedback\Model\FieldApi());
        $this->dirapi = new \Feedback\Model\FormApi();
        
    }
    
    function actionIndex()
    {
       
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->dir > 0 && !$this->dirapi->getById($this->dir)) $this->dir = 0;
        if ($this->dir >0) $this->api->setFilter('form_id', $this->dir);               
        $this->getHelper()->setTopTitle(t('Формы связи'));
        

        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        
        $collection = parent::helperIndex();
        $this->dir = $this->url->request('dir', TYPE_INTEGER);

        $dir = $this->dirapi->getOneItem($this->dir);
        $dir_count = $this->dirapi->getListCount(); //Получим количество форм в списке всего       
        if (!$dir && $dir_count) {
            $dir = $this->dirapi->getFirst();
            $this->dir = $dir->id;
        }
        
        //Верхние правые кнопки добавления
        $collection->setTopHelp(t('В этом разделе можно создать формы с произвольными полями, чтобы получать необходимую обратную связь от ваших пользователей. Для каждой формы обратной связи будет сгенерирована ссылка на персональную страницу. Добавьте данную ссылку в пункт Меню или добавьте Блок "Кнопка обратной связи" на интересующую страницу через раздел <i>Веб-сайт &rarr; Конструктор сайта</i>.'));
        $collection->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    $dir_count > 0 ? //Если форм ещё нету, то скроем кнопку добавления поля
                    array(
                        'title' => t('добавить поле'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add', array('dir' => $this->dir)),                        
                            'class' => 'btn-success crud-add'
                        )
                    ) : null,                    
                    array(
                        'title' => t('добавить форму'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add_dir'),                        
                            'class' => 'crud-add '. (($dir_count==0) ? 'btn-success' :'')
                        )
                    )
                )),
            ))
        ));   
        $collection->addCsvButton('feedback-forms');
        //Параметры таблицы в админке 
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => '20'), 'TdAttr' => array('align' => 'center'))),
                new TableType\Sort('sortn', t('Порядок'),array('sortField' => 'id', 'Sortable' => SORTABLE_ASC,'CurrentSort' => SORTABLE_ASC,'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH,'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('show_type', t('Тип')),
                new TableType\StrYesno('required', t('Обязательное')),
                new TableType\Text('hint', t('Подпись'), array('hidden' => true)),
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
                                )
                            ))
                        ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ),                 
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            ) 
        )));
        
        //Параметры фильтра
        $collection->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array( 
                                'Lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('id','№', array('Attr' => array('size' => 4))),
                                                            new Filter\Type\Text('title',t('Название'), array('SearchType' => 'like%')),
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
        
        //Настройки таблицы дерева форм
        $collection['treeListFunction'] = 'listWithAll'; 
        $collection->setTree(new Tree\Element( array(
            'sortIdField' => 'sortn',
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'noExpandCollapseButton' => true,
            'unselectedTitle' => t('Не создано ни одной формы'),
            'sortable' => false,
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('edit_dir', array(':id' => '~field~')),null,array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                new TableType\Action\DropDown(array(               
                        array(
                            'title' => t('показать форму на сайте'),
                            'attr' => array(
                                '@href' => $this->router->getUrlPattern('feedback-front-form', array(':form_id' => '~field~'), true),
                                'target' => '_blank'
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
                    'text' => t('Название формы'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),  
                array(
                    'attr' => array(
                        'title' => t('Создать форму'),
                        'href' => $this->router->getAdminUrl('add_dir'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        )), $this->dirapi);
         
        //Нижняя панель дерева
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
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        if (!$primaryKeyValue) {
            $dir_id = $this->url->request('dir', TYPE_INTEGER);
            $this->api->getElement()->form_id = $dir_id;
        }
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }
    
    //***** Методы категорий
    
    function actionAdd_dir($primaryKey = null)
    {
        if ($primaryKey === null) {
            $pid = $this->url->request('pid', TYPE_STRING, '');
            $this->dirapi->getElement()->offsetSet('form_id', $pid);
        }
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать форму {title}') : t('Добавить форму'));
        
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
}


