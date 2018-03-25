<?php
namespace SeoControl\Model;

use RS\Router\Manager;

class Api extends \RS\Module\AbstractModel\EntityList
{
    private static $cache_uri = array();
    
    function __construct()
    {
        parent::__construct(new Orm\SeoRule,
        array(
            'multisite' => true,
            'sortField' => 'sortn',
            'defaultOrder' => 'sortn'
        ));
    }
    
    /**
    * Возвращает объект SeoRule для заданного URI, если нет совпадений по маске ни с одним правилом, то возвращается false
    * 
    * @param string $uri - URI для которого нужно вернуть объект 
    * @return Orm\SeoRule | false
    */
    function getRuleForUri($uri)
    {
        $uri = urldecode($uri);
        if (!isset(self::$cache_uri[$uri])) {
            $all_rules = $this->getList(); 

            self::$cache_uri[$uri] = null;
            foreach($all_rules as $rule) {
                /**
                 * @var \SeoControl\Model\Orm\SeoRule $rule
                 */
                if ($rule->match($uri)) {
                    self::$cache_uri[$uri] = $rule;
                    break;
                }
            }
        }
        return self::$cache_uri[$uri];
    }

    /**
     * Возвращает правильный путь для редактирования типа страницы
     *
     * @param string $request_uri - адрес запроса
     */
    function getEditAdminUrlForRequestUrl($request_uri)
    {
        $controller = \RS\Router\Manager::getCurrentRoute()->getController();


    }
}