<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Model\ExternalApi\Menu;
use ExternalApi\Model\Utils;
use \RS\Orm\Type;

/**
* Возвращает список из пунктов меню
*/
class GetList extends \ExternalApi\Model\AbstractMethods\AbstractGetTreeList
{
    const
        RIGHT_LOAD = 1;
    
    protected
        $token_require = false, //Токен не обязателен
        $view, //Объект движка шаблонизатора
        $site; //Текущий объект сайта
        
    function __construct()
    {
        parent::__construct();
        $this->view = new \RS\View\Engine();
        $this->site = \RS\Site\Manager::getSite();
    }
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_LOAD => t('Загрузка списка объектов')
        );
    }
    
    /**
    * Возвращает объект, который позволит производить выборку меню
    * 
    * @return \Menu\Model\Api
    */
    public function getDaoObject()
    {
        return new \Menu\Model\Api();
    }
    

    
    /**
    * Проходится по дереву и добавляет HTML для отображения
    * 
    * @param array $tree - массив с пунктами меню
    *
    * @return array
    */
    private function appendArticleHtmlContent($tree)
    {
        if (!empty($tree)){
            foreach ($tree as $k=>$menu_item){
                /**
                * @var \Menu\Model\Orm\Menu $menu_item 
                */
                if ($menu_item['fields']['typelink'] != 'link'){
                    $template = $menu_item['fields']->getTypeObject()->getTemplate();
                    
                    if ($menu_item['fields']['typelink'] == 'article'){ //Если статья то допишем путь к файлы модуля меню
                        $template = '%menu%/'.$template;
                    }
                    $this->view->assign( 
                        array('menu_item' => $menu_item['fields']) +
                        $menu_item['fields']->getTypeObject()->getTemplateVar() 
                    );
                    //Проверим есть ли относительные ссылки и картинки, чтобы их заменить на абсолютные
                    $html = (!empty($template)) ? Utils::prepareHTML($this->view->fetch($template)) : "";
                    
                    $tree[$k]['fields']['html'] = $html;
                }else{
                    $tree[$k]['fields']['link'] = $this->site->getAbsoluteUrl($tree[$k]['fields']->getTypeObject()->getHref());
                }
                if (!empty($menu_item['child'])){
                    $tree[$k]['child'] = $this->appendArticleHtmlContent($menu_item['child']);
                }    
            }
        }
        
        
        return $tree;
    }
    
    /**
    * Возвращает список объектов
    * 
    * @param \RS\Module\AbstractModel\TreeList $dao
    * @param integer $parent_id id родительской категории
    * @return array
    */
    public function getResultList($dao, $parent_id)
    {
        $menu = new \Menu\Model\Orm\Menu();
        $menu->getPropertyIterator()->append(array(
            'html' => new Type\Richtext(array(
                'description' => t('HTML представляющий страницу'),
                'appVisible' => true,
            )), 
            'link' => new Type\Text(array(
                'description' => t('Ссылка на страницу'),
                'appVisible' => true,
            )), 
        ));
        $tree = $dao->getTreeList($parent_id);
        $tree = $this->appendArticleHtmlContent($tree);
        
        return \ExternalApi\Model\Utils::extractOrmTreeList( $tree );
    }
    
    /**
    * Возвращает возможный ключи для фильтров
    * 
    * @return [
    *   'поле' => [
    *       'title' => 'Описание поля. Если не указано, будет загружено описание из ORM Объекта'
    *       'type' => 'тип значения',
    *       'func' => 'постфикс для функции makeFilter в текущем классе, которая будет готовить фильтр, например eq',
    *       'values' => [возможное значение1, возможное значение2]
    *   ]
    * ]
    */
    public function getAllowableFilterKeys()
    {
        return array(
            'id' => array(
                'title' => t('ID меню. Одно значение или массив значений'),
                'type' => 'integer[]',
                'func' => self::FILTER_TYPE_IN
            ),
            'title' => array(
                'title' => t('Название меню, частичное совпадение'),
                'type' => 'string',
                'func' => self::FILTER_TYPE_LIKE
            ),
            'typelink' => array(
                'title' => t('Тип ссылки. ("article" - статья, "empty" - страница, "link" - ссылка)'),
                'type' => 'string[]',
                'func' => self::FILTER_TYPE_IN
            ),
            'public' => array(
                'title' => t('Только публичные пункты? 1 или 0'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ),   
            'mobile_public' => array(
                'title' => t('Только пункты для мобильных приложений (Флаг в пункте меню.) 1 или 0'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ), 
        );
    }    
    
    /**
    * Возвращает возможные значения для сортировки
    * 
    * @return array
    */
    public function getAllowableOrderValues()
    {
        return array('id', 'id desc', 'title', 'title desc', 'sortn', 'sortn desc');
    }
    
    /**
    * Возвращает список доступных пунктов меню. Доступны только типы статья и страница. 
    * 
    * @param string $token Авторизационный токен
    * @param integer $parent_id id родительской категории
    * @param array $filter фильтр меню по параметрам. Возможные ключи: #filters-info
    * @param string $sort Сортировка товаров по параметрам. Возможные значения: #sort-info
    * 
    * @example GET /api/methods/menu.getlist
    * 
    * GET /api/methods/menu.getlist?parent_id=0&filter[mobile_public]=1
    * 
    * Ответ:
    * <pre>
    * {
    *     "response": {
    *        "list": [
    *            {
    *                "id": "1",
    *                "title": "главная",
    *                "hide_from_url": null,
    *                "alias": "main",
    *                "parent": "0",
    *                "public": "1",
    *                "typelink": "link",
    *                "mobile_public": "0",
    *                "mobile_image": null,
    *                "html": null,
    *                "child": []
    *            },
    *            {
    *                "id": "2",
    *                "title": "оплата",
    *                "hide_from_url": null,
    *                "alias": "payment",
    *                "parent": "0",
    *                "public": "1",
    *                "typelink": "article",
    *                "mobile_public": "0",
    *                "mobile_image": "heart",
    *                "html": "Текст HTML для отоюражения",
    *                "child": []
    *            },
    *            ....
    *            {
    *                "id": "5",
    *                "title": "нижнее меню",
    *                "hide_from_url": null,
    *                "alias": "bottom",
    *                "parent": "0",
    *                "public": "0",
    *                "typelink": "link",
    *                "mobile_public": "0",
    *                "mobile_image": null,
    *                "html": null,
    *                "child": [
    *                    {
    *                        "id": "6",
    *                        "title": "Акции",
    *                        "hide_from_url": null,
    *                        "alias": "akcii",
    *                        "parent": "5",
    *                        "public": "1",
    *                        "typelink": "article",
    *                        "mobile_public": "0",
    *                        "mobile_image": null,   
    *                        "html": "Текст HTML для отоюражения",
    *                        "child": []
    *                    },
    *                    ...
    *                ]
    *            },
    *  }
    * </pre>
    * 
    * @return array Возвращает список объектов и связанные с ним сведения.
    */
    protected function process($token = null, 
                               $parent_id = 0, 
                               $filter = array(), 
                               $sort = 'sortn')
    {
        //Если запрос пришёл из мобильного приложения, то смотрим parent_id в модуле
        if (isset($filter['mobile_public'])){
            $mobile_config = \RS\Config\Loader::byModule('mobilesiteapp');
            $parent_id     = $mobile_config['menu_root_dir'] ? $mobile_config['menu_root_dir'] : 0;
        }
        return parent::process($token, $parent_id, $filter, $sort);
    }
}