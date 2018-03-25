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
 * Блок-контроллер Свежие новости
 */
class LastNews extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Список свежих новостей',
        $controller_description = 'Отображает N последних новостей и ссылку на все новости';

    protected
        $default_params = array(
        'indexTemplate' => 'blocks/lastnews/lastnews.tpl',
        'pageSize' => 5,
        'order' => 'id desc'
    );

    public
        $article_api,
        $category_api;

    function init()
    {
        $this->article_api  = new \Article\Model\Api();
        $this->category_api = new \Article\Model\Catapi();
    }

    /**
     * Возвращает ORM объект, содержащий настриваемые параметры или false в случае,
     * если контроллер не поддерживает настраиваемые параметры
     * @return \RS\Orm\ControllerParamObject | false
     */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'category' => new Type\Varchar(array(
                'description' => t('Новости из какой рубрики отображать?'),
                'list' => array(array('\Article\Model\CatApi', 'selectList'), false)
            )),
            'pageSize' => new Type\Integer(array(
                'description' => t('Количество новостей на страницу')
            )),
            'order' => new Type\Varchar(array(
                'description' => t('Сортировка'),
                'listFromArray' => array(array(
                    'id DESC' => t('По ID в обратном порядке'),
                    'dateof DESC' => t('По дате в обратном порядке'),
                    'rand()' => t('В произвольном порядке')
                ))
            )),
            'show_subdirs_news' => new Type\Integer(array(
                'description' => t('Показывать новости из подкатегорий'),
                'checkboxView' => array(1,0),
            )),
        ));
    }

    function actionIndex()
    {
        $page = $this->myGet('p', TYPE_INTEGER, 1);
        $category = $this->getParam('category');
        
        $this->article_api->setOrder($this->getParam('order'));

        if (!$this->view->cacheOn($page.$category)->isCached($this->getParam('indexTemplate')))
        {
            $this->category_api = new \Article\Model\Catapi();
            $dir = $this->category_api->getById($category);
            if ($dir) {
                $this->article_api->setFilter('public', 1);
                if ($this->getParam('show_subdirs_news')) {
                    $cat_ids = $this->category_api->getChildsId($dir['id']);
                    $this->article_api->setFilter('parent', $cat_ids, 'in');
                } else {
                    $this->article_api->setFilter('parent', $dir['id']);
                }
                $news = $this->article_api->getList($page, $this->getParam('pageSize'));

                if ($debug_group = $this->getDebugGroup()) {
                    $create_href = $this->router->getAdminUrl('add', array('dir' => $dir['id']), 'article-ctrl');
                    $debug_group->addDebugAction(new \RS\Debug\Action\Create($create_href));
                    $debug_group->addTool('create', new \RS\Debug\Tool\Create($create_href));
                }

                $this->view->assign(array(
                    'news' => $news,
                    'category_id' => $dir['id'],
                    'category' => $dir
                ));
            }
        }
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}