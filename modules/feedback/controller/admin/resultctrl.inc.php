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

class ResultCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $dir,
        $dirapi;
    
    function __construct()
    {
        
        parent::__construct(new \Feedback\Model\ResultApi());
        $this->dirapi = new \Feedback\Model\FormApi();
        
    }
    
    function actionIndex()
    {
       
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->dir > 0 && !$this->dirapi->getById($this->dir)) $this->dir = 0;
        if ($this->dir >0) $this->api->setFilter('form_id', $this->dir);               
        $this->getHelper()->setTopTitle(t('Результаты формы обратной связи'));
        

        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $collection = parent::helperIndex();
        $this->dir = $this->url->request('dir', TYPE_STRING); 
        $dir = $this->dirapi->getOneItem($this->dir);
        $dir_count = $this->dirapi->getListCount(); //Получим количество форм в списке всего       
        if (!$dir && $dir_count) {
            $dir = $this->dirapi->getFirst();
            $this->dir = $dir->id;
        }

        $collection->setTopHelp(t('Здесь сохраняется история зополнения форм обратной связи посетителями вашего сайта. В данном разделе также можно отправить быстрый ответ пользователю, задавшему вопрос(если в форме присутствовало поле Email).'));
        $collection->setTopToolbar(null);
        
        //Параметры таблицы в админке 
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => '20'), 'TdAttr' => array('align' => 'center'))),
                new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),                
                new TableType\Text('dateof', t('Дата создания'), array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Text('status', t('Статус'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('id', '№', array('Sortable' => SORTABLE_BOTH)),                
                new TableType\Actions('id', array(
                            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')),null, array(
                                'attr' => array(
                                    '@data-id' => '@id'
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
                                                            new Filter\Type\Datetime('dateof',t('Дата создания')),
                                                            new Filter\Type\Select('status',t('Статус'),array(
                                                                'new'    => t('Новый'),
                                                                'viewed' => t('Просморен'),
                                                            )),
                                                        )
                                    ))
                                )
                            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));
        
        //Настройки таблицы дерева форм
        $collection['treeListFunction'] = 'listWithAll'; 
        $collection->setTree(new Tree\Element( array(
            'sortIdField' => 'sortn',
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'noCheckbox' => true,
            'noExpandCollapseButton' => true,
            'sortable' => false,
            'unselectedTitle' => t('Не создано ни одной формы'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id')))),           
            'headButtons' => array(
                array(
                    'text' => t('Название формы'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                )
            ),
        )), $this->dirapi);
         
        $collection->setBottomToolbar($this->buttons(array('multiedit', 'delete')));        
        $collection->viewAsTableTree();
        return $collection;
    }
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        /**
        * @var \Feedback\Model\Orm\ResultItem
        */
        $elem = $this->api->getElement();        
        if (!$primaryKeyValue) {
            $dir_id = $this->url->request('dir', TYPE_INTEGER);
            $elem->form_id = $dir_id;            
        }

        if (empty($elem['answer'])) {  //Смотрим можно ли отправлять ответ
            $elem['send_answer'] = 1;
        }        
            
            
          
        if (!$elem->hasEmail()) { //Если нет хоть одного поля с указанием E-mail
            $elem['__send_answer']->setReadOnly(true);
            $elem['__send_answer']->setHint(t('У формы нет ни одного поля Email'));
            $elem['__answer']->setDescription(t('Комментарий к результату'));
        }
        
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }
    
}


