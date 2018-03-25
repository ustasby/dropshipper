<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Block;
use \RS\Orm\Type;

/**
* Блок-контроллер збранные товары
*/
class Favorite extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title       = 'Избранное',
        $controller_description = 'Отображает избранные товары';

    protected
        $default_params = array(
            'indexTemplate' => 'blocks/favorite/favorite.tpl',
        );      
    
    function actionIndex()
    {
        $countFavorites = \Catalog\Model\FavoriteApi::getFavoriteCount();
        
        $this->view->assign('countFavorite', $countFavorites);
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }

}