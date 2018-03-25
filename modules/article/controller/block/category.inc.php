<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Controller\Block;
use \RS\Orm\Type;

/**
* Блок-контроллер Список категорий статей
*/
class Category extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Список категорий статей',
        $controller_description = 'Отображает список категорий статей';
    
    public
        $api;

    protected
        $pathids = array(),
        $default_params = array(
            'indexTemplate' => 'blocks/category/category.tpl',
            'root' => 0,
        );   
        
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'root' => new Type\Varchar(array(
                'description' => t('Корневая директория'),
                'list' => array(array('\Article\Model\CatApi', 'selectList'))
            ))
        ));
    }

    function init()
    {
        $this->api = new \Article\Model\CatApi();
        $this->api->setFilter('public', 1);
    }              
    
    function actionIndex()
    {
        $root_id = (int)$this->getParam('root', 0);
        
        //Определяем текущую категорию
        $route = $this->router->getCurrentRoute();
        if ($route && ($route->getId() == 'article-front-previewlist' || $route->getId() == 'article-front-view')) {
            //Получаем информацю о текущей категории из URL
            $category = $this->url->request('category', TYPE_STRING);
        }
        
        $cache_id = $root_id. $category. $this->api->queryObj()->toSql(); 

        //Кэшируем список категорий.
        if (!$this->view->cacheOn($cache_id)->isCached($this->getParam('indexTemplate')) ) {
            if (!is_numeric($root_id) && $dir = $this->api->getById( $root_id )) {
                $root_id = $dir['id'];
            }
            
            $item = false;
            if ($category) {
                //Определяем активные элементы категорий вплоть до корневого элемента
                $full_api = \Article\Model\Catapi::getInstance();
                $item = $full_api->getById($category);
                if ($item) {
                    $this->path = $full_api->getPathToFirst($item['id']);
                    foreach ($this->path as $one) {
                        $this->pathids[] = $one['id'];
                    }
                }
            }
            
            if ($debug_group = $this->getDebugGroup()) {
                $create_href = $this->router->getAdminUrl('add_dir', array(), 'article-ctrl');
                $debug_group->addDebugAction(new \RS\Debug\Action\Create($create_href));
                $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
            }
            
            $this->view->assign(array(
                'dirlist' => $this->api->getTreeList( $root_id ),
                'current_item' => $item,
                'pathids' => $this->pathids
            ));
        }
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }  
}