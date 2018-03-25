<?php
namespace SeoControl\Controller\Block;

class SeoText extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'SEO текст',
        $controller_description = 'Отображает SEO текст, подготовленный для заданных страниц';
        
    
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/seotext/seotext.tpl', //Должен быть задан у наследника
        );            
    
    function actionIndex()
    {
        $api = new \SeoControl\Model\Api();
        $rule = $api->getRuleForUri(\RS\Http\Request::commonInstance()->server('REQUEST_URI'));
        $this->view->assign('seo_rule', $rule);
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}
