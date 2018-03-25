<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;
/**
* Класс определяет объект - избранные товары
*/
class FavoriteApi extends \RS\Module\AbstractModel\EntityList
{
    protected static
        $user_id,
        $guest_id;
    
    function __construct()
    {
        parent::__construct(new Orm\Favorite,
        array(
            'multisite' => true,
            'defaultOrder' => 'id dasc'
        ));
    }
    
    /**
    * Добавляет товар в избранное
    * 
    * @return void
    */
    function addToFavorite($product_id)
    {
        if(!$this->alreadyInFaforite($product_id)){
            $favorite = new Orm\Favorite();
            $favorite['guest_id'] = self::getGuestId();
            $favorite['user_id'] = self::getUserId();
            $favorite['product_id'] = $product_id;
            $favorite->insert();
        }   
    }
    
    /**
    * Возвращает true если данный продукт уже в избранном
    * 
    * @return bool
    */
    static public function alreadyInFaforite($product_id)
    {
        $res = \RS\Orm\Request::make()
                        ->from(new Orm\Favorite(), 'F')
                        ->where("(F.guest_id = '#guest' OR F.user_id = '#user') AND F.product_id = '#product'",
                            array(
                                'guest' => self::getGuestId(),
                                'user' => self::getUserId(),
                                'product' => $product_id
                            ))
                        ->exec();
        
        return (bool) $res->rowCount();
    }
    
    /**
    * Возвращает установленный $guest_id
    * если $guest_id не установлен - возврщает идентификатор текущего гостя
    * 
    * @return string
    */
    static public function getGuestId()
    {
        return (self::$guest_id === null) ? \RS\Http\Request::commonInstance()->cookie('guest', TYPE_STRING, false) : self::$guest_id;
    }
    
    /**
    * Возвращает установленный $user_id
    * если $user_id не установлен - возврщает id текущего пользователя 
    * 
    * @return integer
    */
    static public function getUserId()
    {
        return (self::$user_id === null) ? \RS\Application\Auth::getCurrentUser()->id : self::$user_id;
    }
    
    /**
    * Возвращает количество товаров в избранном
    * 
    * @return integer
    */
    static public function getFavoriteCount()
    {
        $res = \RS\Orm\Request::make()
                        ->select('count(F.id) AS count')
                        ->from(new Orm\Product(), 'P')
                        ->join(new Orm\Favorite(), 'P.id = F.product_id' , 'F')
                        ->where("(F.guest_id = '#guest' OR F.user_id = '#user') AND F.site_id ='#site' AND P.public = 1",
                            array(
                                'guest' => self::getGuestId(),
                                'user' => self::getUserId(),
                                'site' => \RS\Site\Manager::getSiteId()
                            ))
                        ->exec()->fetchRow(); 

        return $res['count'];
    }
    
    /**
    * Возвращает список товаров, находящихся в списке избранных
    * 
    * @param integer $page номер страницы
    * @param integer $pageSize количество элементов на странице
    * @return array
    */
    function getFavoriteList($page = 1, $pageSize = null)
    {
         $q = \RS\Orm\Request::make()
                        ->select("P.*, '1' as isInFavorite")
                        ->from(new Orm\Product(), 'P')
                        ->join(new orm\Favorite(), 'P.id = F.product_id', 'F')
                        ->where("(F.guest_id = '#guest' OR F.user_id = '#user') AND F.site_id = '#site' AND P.public = 1",
                            array(
                                'guest' => self::getGuestId(),
                                'user' => self::getUserId(),
                                'site' => \RS\Site\Manager::getSiteId()
                            ))
                        ->orderby('F.id DESC');
         if ($pageSize){
             $q->limit(($page-1) * $pageSize, $pageSize);
         }
        
         return $q->objects();
    }
    
    /**
    * Сливает избранные товары гостя с избранным пользователя
    * вызывается при авторизации
    */
    static public function mergeFavorites()
    {
        // Переносим избранное гостя к авторизованному пользователю
        \RS\orm\Request::make()
            ->update(new Orm\Favorite(), true)
            ->set(array(
                'user_id' => self::getUserId()
            ))
            ->where(array(
                'guest_id' => self::getGuestId()
            ))
            ->exec();
    }
    
    /**
    * Удаляет товар из избранного
    */
    function removeFromFavorite($product_id)
    {
        \RS\Orm\Request::make()
                    ->delete()
                    ->from(new Orm\Favorite())
                    ->where("(guest_id = '#guest' OR user_id = '#user') AND product_id = '#product'",
                        array(
                            'guest' => self::getGuestId(),
                            'user' => self::getUserId(),
                            'product' => $product_id
                        ))
                    ->exec();
    }
    
    /**
    * Устанавливает $guest_id для дальнейшего использования
    */
    static public function setGuestId($guest_id)
    {
        self::$guest_id = $guest_id;
    }
    
    /**
    * Устанавливает $user_id для дальнейшего использования
    */
    static public function setUserId($user_id)
    {
        self::$user_id = $user_id;
    }
}
?>
