<?php
namespace SeoControl\Controller\Block;
use \RS\Orm\Type;

class SeoH1 extends \RS\Controller\Block
{
    protected static
        $controller_title = 'SEO H1 заголовок',
        $controller_description = 'Отображает SEO H1, подготовленный для заданных страниц';
        
    
    protected
        $default_params = array( //Значения по умолчанию
            'default' => '', 
        );       
        
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * @return \RS\Orm\ControllerParamObject | false
    */
    function getParamObject()
    {
        return new \RS\Orm\ControllerParamObject(
            new \RS\Orm\PropertyIterator(array(
                'default' => new Type\Varchar(array(
                    'maxlength' => 255,
                    'description' => t('Фраза по умолчанию'),
                ))
            ))
        );
    }
    
    function actionIndex()
    {
        $api = new \SeoControl\Model\Api();
        $rule = $api->getRuleForUri(\RS\Http\Request::commonInstance()->server('REQUEST_URI'));
        if ($rule){
            return $rule['h1'];
        }
        
        return $this->getParam('default');
    }
}
