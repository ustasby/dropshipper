<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Controller\Front;

/**
* Контроллер отвечает за просмотр статьи
*/
class View extends \RS\Controller\Front
{            
    public
        $api,
        $cat_api;
        
    function init()
    {
        $this->api = new \Article\Model\Api();
        $this->cat_api = new \Article\Model\Catapi();
    }
    
    function actionIndex()
    {
        $category = $this->url->get('category', TYPE_STRING);
        $id = urldecode( $this->url->get('id', TYPE_STRING) );
        $last_page = isset($_SESSION[PreviewList::SESSION_PAGE_KEY]) ? $_SESSION[PreviewList::SESSION_PAGE_KEY] : 1;
        
        $article = $this->api->getById($id);
        if (!$article || !$article['public']) return $this->e404(t('Статья не найдена'));

        $dir = $this->cat_api->getById($category);
        
        //Если есть alias и открыта страница с id вместо alias, то редирект
        $this->checkRedirectToAliasUrl($id, $article, $article->getUrl());
        $this->checkRedirectToAliasUrl($category, $dir, $article->getUrl());
        
        if (!$article || $article['alias']{0} == '#' || ($article['parent']>0 && $article['parent'] != $dir['id'])) $this->e404(t('Статья не найдена'));
        $this->router->getCurrentRoute()->article    = $article; //Сообщаем системе, страницу статьи
        $this->router->getCurrentRoute()->article_id = $article['id']; //Сообщаем системе, id просматриваемой статьи
        
        if ($debug_group = $this->getDebugGroup()) {
            $edit_href = $this->router->getAdminUrl('edit', array('id' => $article['id']), 'article-ctrl');
            $debug_group->addDebugAction(new \RS\Debug\Action\Edit($edit_href));
            $debug_group->addTool('create', new \RS\Debug\Tool\Edit($edit_href));
        }
        
        //Заполняем хлебные крошки
        $path     = $this->cat_api->getPathToFirst($dir['id']);
        $last_dir = array_pop($path);    
        if (!empty($path)){
           foreach($path as $one_dir) {  
                if ($one_dir['public']) {
                   $this->app->breadcrumbs->addBreadCrumb($one_dir['title'], $one_dir->getUrl());  
                }
           } 
        }

        if($last_dir !== null) {
            $this->app->breadcrumbs->addBreadCrumb($last_dir['title'], $this->router->getUrl('article-front-previewlist', array('category' => $category) + ($last_page > 1 ? array('p' => $last_page) : array())));
        }
        $this->app->breadcrumbs ->addBreadCrumb($article['title']);

       $this->app->title
            ->addSection($article['meta_title'] ?: $article['title']);
                
        $this->app->meta
            ->addKeywords($article['meta_keywords'])
            ->addDescriptions($article['meta_description']);
            
        $this->view->assign(array(
            'article' => $article
        ));
        
        return $this->result->setTemplate('view_article.tpl');
    }
}
