<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Templates\Model;

/**
* API отвечает за страницу с секциями и блоками
* @ingroup Templates
*/
class PageApi extends \RS\Module\AbstractModel\EntityList
{
    const
        DEFAULT_CONTEXT = 'theme';
    
    function __construct()
    {
        parent::__construct(new \Templates\Model\Orm\SectionPage, 
        array(
            'multisite' => true,
            'loadOnDelete' => true
        ));
    }

    /**
    * Возвращает true, если у текущего пользователя нет прав на запись.
    * Текст ошибки можно получить через getErrors
    * 
    * @return bool
    */
    function noWriteRights()
    {
        if ($acl_err = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            $this->addError($acl_err);
            return true;
        }
        return false;
    }    
    
    /**
    * Производит импорт структуры блоков из XML файла
    * 
    * @param array $post_file_arr - массив одного файла из $_FILES
    * @param string $context - идентификатор контекста темы оформления
    * @return bool(true) | string возвращает true, в случае успеха, иначе текст ошибки
    */
    function importXML($post_file_arr, $context = null)
    {
        $upload_error = \RS\File\Tools::checkUploadError($post_file_arr['error']);
        if ($upload_error !== false) {
            return $this->addError($upload_error);
        }
        
        return $this->importFromXmlFile($post_file_arr['tmp_name'], null, $context);
    }
    
    /**
    * Импортирует структуру блоков из xml файла
    * 
    * @param string $file - Путь к xml файлу
    * @param integer $site_id - ID сайта
    * @param string $context - идентификатор структуры блоков
    * @return bool(true) | string возвращает true, в случае успеха, иначе текст ошибки
    */
    public function importFromXmlFile($file, $site_id = null, $context = null)
    {
        if ($this->noWriteRights()) return false;
        
        \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
        $context = $context ?: self::DEFAULT_CONTEXT;
        
        $dom = @simplexml_load_file($file);

        //Устанавливаем параметры темы оформления
        $theme = \RS\Theme\Item::makeByContext($context);       
        $theme->resetContextOptions($site_id, $dom);        
        
        //Удаляем текущие данные
        if ($site_id) {
            $this->setSiteContext($site_id);
        }               
        
        $this->clearFilter();
        $this->setFilter('context', $context);
        $pages = $this->getList();
        foreach($pages as $page) {
            $page->delete();
        }
        
        if ($dom) {
            foreach($dom->page as $page) {
                
                $sectionPage = new Orm\SectionPage();
                $sectionPage->getFromArray( $this->attrToArray( $page->attributes() ) );
                if ($site_id) {
                    $sectionPage['site_id'] = $site_id; 
                }
                $sectionPage['context'] = $context;
                $sectionPage->insert();

                foreach($page->container as $container) {
                    $sectionContainer = new Orm\SectionContainer();
                    $sectionContainer->getFromArray( $this->attrToArray( $container->attributes() ) );
                    $sectionContainer['page_id'] = $sectionPage['id'];
                    $sectionContainer->insert();
                    
                    $this->importSectionsRecursive($container, -$sectionContainer['type'], $sectionPage['id']);
                }
            }
            
            if (isset($dom->hooks)) { //Импортируем сведения о порядке обработки модулями хуков в шаблонах
                $hook_sort_data = array();
                if (isset($dom->hooks->hook)) {
                    foreach($dom->hooks->hook as $hook) {
                        $hook_sort_data[(string)$hook['name']][] = (string)$hook;
                    }
                }
                $hook_sort_api = new TemplateHookSortApi($site_id, $context);
                $hook_sort_api->resetBySortData($hook_sort_data, true);
                $hook_sort_api->saveSortData($hook_sort_data);
            }
        }
        
        return true;
    }
    
    /**
    * Конвертирует атрибуты SimpleXML в обычный array
    * 
    * @param \SimpleXMLElement $attrs - Атрибуты 
    * @param bool $unserialize - Если true, значит требуется десериализация данных
    * @return array
    */
    protected function attrToArray($attrs, $unserialize = false)
    {
        $result = array();
        foreach($attrs as $key => $val) {
            if ($unserialize) {
                $val = unserialize(base64_decode((string)$val));
            } else {
                $val = (string)$val;
            }
            $result[(string)$key] = $val;
        }
        return $result;
    }
    
    /**
    * Рекурсивно импортирует секции в базу
    * 
    * @param \DomElement $sections - указатель на элемент секций
    * @param integer $parent_id - родительский элемент
    * @param integer $page_id - ID страницы
    */
    protected function importSectionsRecursive($sections, $parent_id, $page_id)
    {
        foreach($sections->section as $section) {
        
            $dbSection = new Orm\Section();
            $dbSection->getFromArray( $this->attrToArray( $section->attributes() ) );
            $dbSection['page_id'] = $page_id;
            $dbSection['parent_id'] = $parent_id;
            $dbSection->insert();
                        
            if (isset($section->section)) {
                $this->importSectionsRecursive($section, $dbSection['id'], $page_id);
            }
            if (isset($section->block)) {
                $params = array();
                foreach($section->block as $block) {
                    $params = $this->attrToArray($block, true);
                    
                    $sectionModule = new Orm\SectionModule();
                    $sectionModule->getFromArray( $this->attrToArray( $block->attributes() ) );
                    $sectionModule['page_id'] = $page_id;
                    $sectionModule['section_id'] = $dbSection['id'];
                    $sectionModule->setParams($params);
                    $sectionModule->insert();
                }
            }
        }
    }
    
    /**
    * Возвращает xml со структурой блоков
    * 
    * @param string $context - идентификатор схемы блоков
    * @return void
    */
    function getBlocksXML($context = null)
    {
       $dom = $this->getBlocksDom($context);
       $dom->formatOutput = true;
       return $dom->saveXML();
    }
    
    /**
    * Возвращает XML структуры блоков для всех страниц
    * @return DomDocument
    */
    function getBlocksDom($context = null)
    {
        $context = $context ?: self::DEFAULT_CONTEXT;        
        $this->clearFilter();
        $this->setFilter('context', $context);
        
        //Загружаем параметры контекста
        $theme = \RS\Theme\Item::makeByContext($context);
        $gs = $theme->getGridSystem();
        
        $pages = $this->getList();
        $dom = new \DOMDocument('1.0', 'utf-8');
        $root = $dom->createElement('structure');
        $root->setAttribute('grid_system', $gs);
        
        foreach($pages as $page) {
            $dom_page = $dom->createElement('page'); //page
            $this->addXmlAttributes($dom_page, $page);
            
            foreach($page->getContainers() as $container) {
                if ($container['object']) {
                    
                    $dom_container = $dom->createElement('container'); //container
                    $this->addXmlAttributes($dom_container, $container['object']);
                    $this->addXmlSections($dom, $dom_container, $container['object']->getSections());
                    $dom_page->appendChild($dom_container);
                }
            }
            
            $root->appendChild($dom_page);
        }
        
        $this->addTemplateHookSort($dom, $root, $context);
            
        $dom->appendChild($root);        
        return $dom;        
    }
    
    /**
    * Добавляет информацию о сортировке модулей, при обработке хуков в шаблонах
    * 
    * @param \DomDocument $dom
    * @param \DomElement $root
    * @param mixed $context
    */
    protected function addTemplateHookSort(\DomDocument $dom, \DomElement $root, $context)
    {
        $api = new \Templates\Model\TemplateHookSortApi(null, $context);
        
        if ($sort_item = $api->getSortData()) {
            $dom_hooks = $dom->createElement('hooks'); //container
            foreach($sort_item as $hook => $items) {
                foreach($items as $item) {
                    $dom_hook = $dom->createElement('hook');
                    $dom_hook->setAttribute('name', $hook);
                    $dom_hook->appendChild($dom->createCDATASection( $item['module'] ));
                    $dom_hooks->appendChild($dom_hook);
                }
            }
            $root->appendChild($dom_hooks);
            return true;
        }
        return false;
    }
    
    /**
    * Добавляет узлы секций и блоков в $element
    * 
    * @param \DomDocument $dom
    * @param \DomElement $element
    * @param mixed $sections
    */
    protected function addXmlSections(\DomDocument $dom, \DomElement $element, $sections)
    {
        static 
            //Технические параметры, которые нужно исключать из blocks.xml
            $exclude_block_params = array('_block_id', 'generate_by_grid'); 
        
        foreach($sections as $item) {
            $dom_section = $dom->createElement('section');
            $this->addXmlAttributes($dom_section, $item['section']);
            if (!empty($item['childs'])) {
                $this->addXmlSections($dom, $dom_section, $item['childs']);
            }
            $blocks = $item['section']->getBlocks();
            if (!empty($blocks)) {
                foreach($blocks as $block) {
                    $dom_block = $dom->createElement('block');
                    $this->addXmlAttributes($dom_block, $block);
                    foreach($block->getParams() as $key => $value) {
                        if (in_array($key, $exclude_block_params)) continue;
                        $param = $dom->createElement($key);
                        $param->appendChild($dom->createCDATASection( base64_encode(serialize($value)) ));
                        $dom_block->appendChild($param);
                    }
                    $dom_section->appendChild($dom_block);
                }
            }
            
            $element->appendChild($dom_section);
        }
    }
    
    /**
    * Добавляет атрибуты из свойств ORM объекта к узлу XML
    * 
    * @param DomElement $dom_element
    * @param \RS\Orm\AbstractObject $orm_object
    */
    protected function addXmlAttributes(\DomElement $dom_element,\RS\Orm\AbstractObject $orm_object)
    {
        foreach($orm_object as $key => $property) {
            if (empty($property->no_export) && $key != 'id') {
                $dom_element->setAttribute($key, $orm_object[$key]);
            }
        }
    }
    
    /**
    * Сохраняет текущее распололожение блоков на страницах в файле blocks.xml текущей темы
    * 
    * @return boolean
    */
    function saveThemeBlocks()
    {
        $config = \RS\Config\Loader::getSiteConfig();
        $theme = new \RS\Theme\Item($config['theme']);
        $file = $theme->getBlocksXmlFilename();
        $xml = $this->getBlocksXML();
        if (file_put_contents($file, $xml)) {
            return true;
        }
        $this->addError(t('Не удалось сохранить xml файл'));
        return false;
    }
    
    /** 
    * Позволяет настроить параметры блока(ов)
    * 
    * @param string | null $route_id - ID маршрута(страница, на которой нужно править блок)
    * @param string $block_class - имя класса блочного контроллера
    * @param array $params - параметры, которые необходимо установить.
    * @param string $section_alias - псевдоним секции в которой нужно искать модуль
    * @return bool Возвращает true, если параметры установлены, иначе - false
    */
    public static function setupModule($route_id, $block_class, array $params, $section_alias = null)
    {
        $page = Orm\SectionPage::loadByRoute($route_id);
        $q = \RS\Orm\Request::make()
            ->from(new Orm\SectionModule())
            ->where(array(
                'module_controller' => strtolower(ltrim($block_class, '\\'))
            ));
        
        if ($route_id !== null) {
            $q->where(array(
                'page_id' => $page['id'],
            ));
        }
        
        if ($section_alias !== null) {
            $sections_id = \RS\Orm\Request::make()
                ->from(new Orm\Section())
                ->where(array(
                    'alias' => $section_alias,
                ) + ($page['id'] ? array('page_id' => $page['id']) : array()) )
                ->exec()->fetchSelected(null, 'id');
            if (!$sections_id) {
                return false; //Не обновляем ничего, если секция не найдена
            }
            $q->whereIn('section_id', $sections_id);
        }
        
        $blocks = $q->objects();
        if ($blocks) {
            foreach($blocks as $block) {
                $block['params'] = serialize($params);
                $block->update();
            }
            return true;
        }
        return false;
    }
    
    /**
    * Возвращает тип сеточного фреймворка, используемого на странице
    * 
    * @param Integer $page_id
    * @return string
    */
    public function getPageGridSystem($page_id)
    {
        $page = $this->getOneItem($page_id);
        return \RS\Theme\Item::makeByContext($page['context'])->getGridSystem();
    }
    
}

