<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller\Admin\Helper;
use \RS\Html,
    \RS\Html\Toolbar\Button as ToolbarButton;

/**
* Используется для организации элементов на стандартных страницах административной панели.
* Содержит список визуальных объектов, которые будут находиться на странице, организовывает их взаимодействие
*/
class CrudCollection implements \ArrayAccess
{
    const
        VIEW_CAT_VAR = 'viewcat',
        VIEW_CAT_LEFT = 'left',
        VIEW_CAT_TOP = 'top';
    
    private
        $api,
        $other_api = array(),
        $url,
        $appendModuleOptionsButton = true,
        $controller,
        $router,
        $collection = array(
            'filter' => null,
            'treeFilter' => null,
            'table' => null,
            'tree' => null,
            'paginator' => null,
            'topHelp' => null,
            'formTemplate' => null,
            'bottomToolbar' => null,
            'treeBottomToolbar' => null,
            'topToolbar' => null,
            'hiddenFields' => array(),
            'listFunction' => 'getList',
            'treeListFunction' => 'getTreeList'
        );
    
    function __construct(\RS\Controller\AbstractModule $controller, $api = null, \RS\Http\Request $url = null, array $options = array())
    {
        $this->api = $api;
        $this->url = $url;
        $this->controller = $controller;
        $this->controller->view->assign('elements', $this);
        
        foreach($options as $key=>$parameters) {
            if (is_numeric($key)) {
                $this[$parameters] = null;
            } else {
                $this[$key] = $parameters;
            }
        }
    }
    
    /**
    * Устанавливает объект API, который будет использоваться для вывода табличных данных
    * 
    * @param mixed $api
    * @return CrudCollection
    */
    function setApi($api)
    {
        $this->api = $api;
        return $this;
    }
    
    /**
    * Устанавливает, добавлять ли к topToolbar кнопку с настройкой модуля
    * 
    * @param mixed $bool
    * @return CrudCollection
    */
    function setAppendModuleOptionsButton($bool)
    {
        $this->appendModuleOptionsButton = $bool;
        return $this;
    }
    
    function controllerNameStr()
    {
        return str_replace('\\', '-', $this->controller->getControllerName());
    }
    
    /**
    * Устанавливает как будет выглядеть страница. В виде таблицы, формы, древовидного списка?
    * Вид меняется установкой соответствующего шаблона
    * 
    * @param string $what
    */
    function setViewAs($what)
    {
        $func = 'viewAs'.$what;
        return call_user_func(array($this, $func));
    }
    
    /**
    * Устанавливает таблицу в отображение
    * 
    * @param \RS\Html\Table\Element $table
    * @param mixed $id
    * @return CrudCollection
    */
    function setTable(Html\Table\Element $table, $id = null)
    {
        if ($id === null) {
            $site_id = \RS\Site\Manager::getSiteId();
            $id = $site_id.'-'.md5($this->controllerNameStr().'-index');
        }
        $this->collection['table'] = new Html\Table\Control(array(
            'Id' => $id,
            'Table' => $table,
            'AutoFill' => false
        ));
        return $this;
    }

    /**
     * Возвращает объект управления таблицей
     *
     * @return \RS\Html\Table\Control | null
     */
    function getTableControl()
    {
        return $this->collection['table'];
    }
    
    /**
    * Добавляет пагинатор на страницу
     *
    * @return CrudCollection
    */
    function setPaginator()
    {
        $this->collection['paginator'] = new Html\Paginator\Control(array(
            'Paginator' => new Html\Paginator\Element(),
            'AutoFill' => false
        ));        
        return $this;
    }

    /**
     * Возвращает объект управления пагинатором
     *
     * @return \RS\Html\Paginator\Control | null
     */
    function getPaginatorControl()
    {
        return $this->collection['paginator'];
    }
    
    /**
    * Устанавливает текст подсказки для текущей страницы
    * 
    * @param string $help
    * @return CrudCollection
    */
    function setTopHelp($help)
    {
        $this->collection['topHelp'] = $help;
        return $this;
    }

    /**
     * Возвращает подсказку для текущей страницы
     *
     * @return string | null
     */
    function getTopHelp()
    {
        return $this->collection['topHelp'];
    }
    
    /**
    * Устанавливает фильтр на страницу
    * 
    * @param \RS\Html\Filter\Control $filter
    * @return CrudCollection
    */
    function setFilter(Html\Filter\Control $filter)
    {
        $this->collection['filter'] = $filter;
        return $this;
    }

    /**
     * Возвращает фильтр, установленный на странице
     *
     * @return \RS\Html\Filter\Control | null
     */
    function getFilter()
    {
        return $this->collection['filter'];
    }

    /**
    * Устанавливает имя функции из API, которая будет вызвана для получения данных для таблицы
    * 
    * @param string $name
    * @return CrudCollection
    */
    function setListFunction($name)
    {
        $this->collection['listFunction'] = $name;
        return $this;
    }

    /**
     * Возвращает имя функции из API, которая будет вызвана для получения данных для таблицы
     *
     * @return string | null
     */
    function getListFunction()
    {
        return $this->collection['listFunction'];
    }
    
    /**
    * Устанавливает имя функции из API, которая будет вызвана для получения данных для древовидного списка
    * 
    * @param string $name
    * @return CrudCollection
    */
    function setTreeListFunction($name) 
    {
        $this->collection['treeListFunction'] = $name;
        return $this;
    }

    /**
     * Возвращает имя функции из API, которая будет вызвана для получения данных для древовидного списка
     *
     * @return string | null
     */
    function getTreeListFunction()
    {
        return $this->collection['treeListFunction'];
    }
    
    /**
    * Устанавливает имя шаблона, который будет использован для генерации формы ORM объекта
    * 
    * @param string $tpl
    * @return CrudCollection
    */
    function setFormTemplate($tpl)
    {
        $this->collection['formTemplate'] = $tpl;
        return $this;
    }

    /**
     * Возвращает имя шаблона, который будет использован для генерации формы ORM объекта
     *
     * @return string | null
     */
    function getFormTemplate()
    {
        return $this->collection['formTemplate'];
    }
    
    /**
    * Устанавливает произвольный шаблон формы
    * 
    * @param string $html шаблон
    * @return CrudCollection
    */
    function setForm($html)
    {
        $this->collection['form'] = $html;
        return $this;
    }
    
    /**
    * Устанавливает шаблон, который используется для сборки страницы
    * 
    * @param string $tpl
    * @return CrudCollection
    */
    function setTemplate($tpl)
    {
        $this->collection['template'] = $tpl;
        return $this;
    }

    /**
     * Возвращает шаблон, который используется для сборки страницы
     *
     * @return string | null
     */
    function getTemplate()
    {
        return $this->collection['template'];
    }
    
    /**
    * Устанавливает нижнюю строку инструментов
    * 
    * @param \RS\Html\Toolbar\Element $toolbar
    * @return CrudCollection
    */
    function setBottomToolbar(Html\Toolbar\Element $toolbar = null)
    {
        $this->collection['bottomToolbar'] = $toolbar;
        return $this;
    }

    /**
     * Возвращает нижнюю панель инструментов
     *
     * @return \RS\Html\Toolbar\Element | null
     */
    function getBottomToolbar()
    {
        return $this->collection['bottomToolbar'];
    }
    
    /**
    * Устанавливает нижнюю строку инструментов для древовидного списка
    * 
    * @param \RS\Html\Toolbar\Element $toolbar
    * @return CrudCollection
    */
    function setTreeBottomToolbar(Html\Toolbar\Element $toolbar = null)
    {
        $this->collection['treeBottomToolbar'] = $toolbar;
        return $this;
    }

    /**
     * Возвращает нижнюю панель инструментов для древовидного списка
     *
     * @return \RS\Html\Toolbar\Element | null
     */
    function getTreeBottomToolbar()
    {
        return $this->collection['treeBottomToolbar'];
    }
    
    /**
    * Устанавливает верхнюю строку инструментов
    * 
    * @param \RS\Html\Toolbar\Element $toolbar
    * @return CrudCollection
    */
    function setTopToolbar(Html\Toolbar\Element $toolbar = null)
    {
        $this->collection['topToolbar'] = $toolbar;
        return $this;
    }

    /**
     * Возвращает верхнюю панель инструментов
     *
     * @return \RS\Html\Toolbar\Element
     */
    function getTopToolbar()
    {
        return $this->collection['topToolbar'];
    }
    
    /**
    * Добавляет в верхний toolbar пункт Импорт/экспорт в CSV
    * 
    * @param string $schema - схема для экспорта и импорта
    * @param array $additional_export_params - массив дополнительных параметров для экспорт
    * @return CrudCollection
    */
    function addCsvButton($schema)
    {
        $this['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
            ),
            array(
                'title' => t('Экспорт CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('exportCsv', array('schema' => $schema, 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт CSV'),
                'attr' => array(
                    'href' => \RS\Router\Manager::obj()->getAdminUrl('importCsv', array('schema' => $schema, 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            )
        )), 'import');
        return $this;
    }
    
    /**
    * Устанавливает фильтр для древовидного списка
    * 
    * @param \RS\Html\Filter\Control $filter
    * @return CrudCollection
    */
    function setTreeFilter(Html\Filter\Control $filter)
    {
        $this->collection['treeFilter'] = $filter;
        return $this;
    }

    /**
     * Возвращает фильтр для древовидного списка
     *
     * @return \RS\Html\Filter\Control | null
     */
    function getTreeFilter()
    {
        return $this->collection['treeFilter'];
    }
    
    /**
    * Устанавливает древовидный список для страницы
    * 
    * @param \RS\Html\Tree\Element $tree
    * @param mixed $api
    * @return CrudCollection
    */
    function setTree(Html\Tree\Element $tree, $api = null)
    {
        if ($api) {
            $this->other_api['tree'] = $api;
        }
        
        $tree->setOption('uniq', $this->getApi('tree')->uniq);
        $this->collection['tree'] = new Html\Tree\Control(array(
            'Tree' => $tree,
            'AutoFill' => false
        ));
        return $this;
    }

    /**
     * Возвращает объект управления деревом
     *
     * @return \RS\Html\Tree\Control
     */
    function getTreeControl()
    {
        return $this->collection['tree'];
    }


    function setBeforeTableContent($html)
    {
        $this->collection['beforeTableContent'] = $html;
    }


    function getBeforeTableContent()
    {
        return $this->collection['beforeTableContent'];
    }
    
    /**
    * Устанавливает заголовок
    * 
    * @param string $form_title - заголовок
    * @param array | null $data - массив со значениями для замены
    * @return CrudCollection
    */
    function setTopTitle($form_title, $data = null)
    {
        $this->collection['formTitle'] = $form_title;
        $this->collection['formTitleData'] = $data;
        return $this;
    }
    
    /**
    * Возвращает заголовок формы
    */
    function getFormTitle()
    {
        $left = '&laquo;<strong>';
        $right = '</strong>&raquo;';
        
        if (strpos($this->collection['formTitle'], '{') !== false) {
            $element = isset($this->collection['formTitleData']) ? $this->collection['formTitleData'] : $this->api->getElement();
            $result = preg_replace_callback('/\{(.*?)\}/', function($match) use ($element, $left, $right) {
                return $left.$element[$match[1]].$right;
            }, $this->collection['formTitle']);
        }
        $result = isset($result) ? $result :$this->collection['formTitle'];
        $this->controller->app->title->addSection(strip_tags($result), 'crud-form-title');
        return $result;
    }
    
    /**
    * Переключает на новый шаблон, в который войдут свойства aliasVisible имеющие значение true 
    * 
    * @param string $alias
    * @return CrudCollection
    */
    function setFormSwitch($alias)
    {
        $this->collection['formSwitch'] = $alias;
        return $this;
    }
    
    /**
    * Устанавливает объект, из которого будет сгенерирована форма
    * @param \RS\Orm\AbstractObject $form_object
    * @return CrudCollection
    */
    function setFormObject(\RS\Orm\AbstractObject $form_object)
    {
        $this->collection['formObject'] = $form_object;
        return $this;
    }

    /**
    * Возвращает форму ORM объекта
    * 
    * @return string
    */
    function getForm()
    {
        if (isset($this->collection['form'])) return $this->collection['form'];
        
        $form_object = isset($this->collection['formObject']) ? $this->collection['formObject'] : $this->api->getElement();
        $switch = isset($this->collection['formSwitch']) ? $this->collection['formSwitch'] : null;
        return $form_object->getForm($this->controller->view->getTemplateVars(), $switch, !empty($this->collection['multieditMode']));
    }
    
    /**
    * Устанавливает, какую форму генерировать методу getForm. В режиме мультиредактирования форма отличается от обычной
    * 
    * @param mixed $bool
    * @return CrudCollection
    */
    function setMultieditMode($bool)
    {
        $this->collection['multieditMode'] = $bool;
        return $this;
    }
    
    /**
    * Добавляет скрытые поля данных (input[type=hidden]) в форму на страницу
    * 
    * @param array $keyval
    * @return CrudCollection
    */
    function addHiddenFields(array $keyval)
    {
        $this->collection['hiddenFields'] = array_merge($keyval);
        return $this;
    }
    
    /**
    * Возвращает скрытые поля
    * 
    * @return array
    */
    function getHiddenFields()
    {
        return (array)$this->collection['hiddenFields'];
    }
    
    /**
    * Возвращает объект API для нужного контекста
    * 
    * @param mixed $key - контекст
    * @return object
    */
    function getApi($key)
    {
        if (isset($this->other_api[$key])) {
            return $this->other_api[$key];
        }
        return $this->api;
    }
    
    /**
    * Возвращает ошибки для отобажения в форме
    * 
    * @return array
    */
    function getFormErrors()
    {
        if ($this->collection['formObject']) {
            $form_object = $this->collection['formObject'];
        } elseif ($this->api !== null) {
            $form_object = $this->api->getElement();
        } else {
            return array();
        }

        return $form_object->getDisplayErrors();
    }
    
    /**
    * Удаляет секцию $section
    * 
    * @param string $section
    * @return CrudCollection
    */
    function removeSection($section) {
        unset($this[$section]);
        return $this;
    }

    /**
     * Установка дополнительного текста вверху страницы
     *
     * @param string $html
     * @return CrudCollection
     */
    function setHeaderHtml($html)
    {
        $this->collection['headerHtml'] = $html;
        return $this;
    }
    
    /**
    * Инициализирует страницу перед отображением
    * @return CrudCollection
    */
    function active()
    {
        if ($this->appendModuleOptionsButton) {
            $module_name = \RS\Module\item::nameByObject($this->controller, false);
            if (!$this['topToolbar']) {
                $this->setTopToolbar(new \RS\Html\Toolbar\Element(array()));
            }
            $this['topToolbar']->addItem(new ToolbarButton\ModuleConfig(\RS\Router\Manager::obj()->getAdminUrl('edit', array('mod' => $module_name), 'modcontrol-control')));
        }
        
        if ($this['filter']) {
            $this['filter']->fill();            
            $this->api->addFilterControl($this['filter']);
        }
        
        if ($this['treeFilter']) {
            $this['treeFilter']->fill();
            $treelist = $this->getApi('tree')->addFilterControl($this['treeFilter']);
        }
        
        if ($this['paginator']) {
            $totalCount = $this->api->getListCount();
            $this['paginator']->fill();            
            $this['paginator']->getPaginator()->setTotal($totalCount);
        }
        
        if ($this['tree']) {
            $this['tree']->fill();
            $treelist = $this->getApi('tree')->{$this['treeListFunction']}();
            $this['tree']->getTree()->setData($treelist);
        }

        if ($this['table']) {
            $this['table']->fill();
            if ($this->api) {
                $this->api->addTableControl($this['table']);
            
                if ($this['paginator']) {
                    $page = $this['paginator']->getPage();
                    $page_size = $this['paginator']->getPageSize();
                } else {
                    $page = $page_size = null;
                }                
                if ($this['listFunction']) {
                    $list = $this->api->{$this['listFunction']}($page, $page_size);
                    $this['table']->getTable()->setData($list);
                }
            }
        }
        return $this;
    } 
    
    /**
    * Возвращает тип отображения дерева относительно таблицы
    *
    * @deprecated Теперь существует только один вид. В следующих версиях функция будет удалена
    * @return string
    */
    public function getTreeViewType()
    {
        //Приоритеты: GET, Cookie
        $viewtype = $this->url->get(self::VIEW_CAT_VAR, TYPE_STRING, false);
        if ($viewtype === false) {
            $viewtype = $this->url->cookie(self::VIEW_CAT_VAR, TYPE_STRING, false);
            if ($viewtype === false) {
                $viewtype = self::VIEW_CAT_LEFT; //по умолчанию
            }
        } else {
            //Пишем в cookie
            setcookie(self::VIEW_CAT_VAR, $viewtype, time()+(60*60*365*10)); //Работает только на текущем адресе
        }
        return $viewtype;
    }

    /**
    * Отображать страницу в виде произвольной страницы с данными
    * 
    * @return CrudCollection
    */
    public function viewAsAny()
    {
        $this->setTemplate('%system%/admin/crud_any.tpl');
        return $this;
    }
    
    /**
    * Отображать страницу в виде формы ORM объекта
    * 
    * @return CrudCollection
    */
    public function viewAsForm()
    {
        $this->setTemplate('%system%/admin/crud_form.tpl');
        $this->setAppendModuleOptionsButton(false);
        return $this;
    }

    /**
    * Отображать страницу в виде таблицы с данными
    * 
    * @return CrudCollection
    */    
    public function viewAsTable()
    {
        $this->setTemplate('%system%/admin/crud_table.tpl');
        return $this;
    }    
    
    /**
    * Отображать страницу в виде таблицы с данными и дерева категорий (дерево категорий свернуто)
    * 
    * @return CrudCollection
    */    
    public function viewAsTableTree()
    {
        $this->setTemplate('%system%/admin/crud_table_tree.tpl');
        return $this;
    }
    
    /**
    * Отображать страницу в виде дерева
    * 
    * @return CrudCollection
    */        
    public function viewAsTree()
    {
        $this->setTemplate('%system%/admin/crud_tree.tpl');
        return $this;
    }            

    /**
    * ArrayAccess
    */
    public function offsetSet($offset, $value) 
    {
        $method = 'set'.$offset;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->collection[$offset] = $value;
        }
    }

    /**
    * ArrayAccess
    */    
    public function offsetExists($offset) 
    {
        $method = 'get'.$offset;
        return (isset($this->collection[$offset]) && $this->collection[$offset] !== null);
    }

    /**
    * ArrayAccess
    */    
    public function offsetUnset($offset) 
    {
        unset($this->collection[$offset]);
    }
    
    /**
    * ArrayAccess
    */
    public function offsetGet($offset) 
    {
        $method = 'get'.$offset;
        if (method_exists($this, $method)) {
                return $this->$method();
        } else {
            if (isset($this->collection[$offset])) {
                return $this->collection[$offset];
            }
        }
        return null;
    }       
}

