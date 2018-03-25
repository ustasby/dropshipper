<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Model\Orm;
use \RS\Orm\Type;

class Article extends \RS\Orm\OrmObject
{
    const
        MAX_RATING  = 5, //Максимальный рейтинг статьи
        IMAGES_TYPE = 'article',
        TAG_TYPE = 'article';
        
    protected
        $attach_products = null,                 //Прикреплённые товары
        $fast_mark_attached_products_use = null; //Флаг используются ли прикреплённые товары
        
    protected static
        $table = "article";

    protected function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                    'site_id' => new Type\CurrentSite(),
                    'title' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'description' => t('Название'),
                        'Checker' => array('chkEmpty',t('Необходимо заполнить поле название')),
                        'attr' => array(array(
                            'data-autotranslit' => 'alias'
                        ))
                    )),
                    'alias' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'index' => true,
                        'description' => t('Псевдоним(Ан.яз)'),
                        'meVisible' => false
                    )),
                    'content' => new Type\Richtext(array(
                        'description' => t('Содержимое'),
                    )),
                    'parent' => new Type\Integer(array(
                        'index' => true,
                        'description' => t('Рубрика'),
                        'List' => array(array(new \Article\Model\Catapi(), 'selectList')),
                        'Attr' => array(array('size' => 0)),
                    )),
                    'dateof' => new Type\Datetime(array(
                        'maxLength' => '19',
                        'description' => t('Дата и время'),
                        'index' => true,
                    )),
                    'image' => new Type\Image(array(
                        'maxLength' => '255',
                        'max_file_size' => 10000000,
                        'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
                        'description' => t('Картинка'),
                    )),
                    'user_id' => new Type\User(array(
                        'description' => t('Автор'),
                    )),
                    'rating' => new Type\Decimal(array(
                        'maxLength' => '3',
                        'decimal' => '1',
                        'default' => 0,
                        'visible' => false,
                        'description' => t('Средний балл(рейтинг)'),
                        'hint' => t('Расчитывается автоматически, исходя из поставленных оценок, если установлен блок комментариев на странице статьи.')
                    )),
                    'comments' => new Type\Integer(array(
                        'maxLength' => '11',
                        'description' => t('Кол-во комментариев к статье'),
                        'default' => 0,
                        'visible' => false,
                    )),
                    'public' => new Type\Integer(array(
                        'description' => t('Публичный'),
                        'maxLength' => 1,
                        'checkboxView' => array(1,0),
                        'allowEmpty' => false,
                        'default' => 1
                    )),
                    'attached_products' => new Type\Varchar(array(
                        'maxLength' => 4000,
                        'description' => t('Прикреплённые товары'),
                        'visible' => false,
                    )),
                    'attached_products_arr' => new Type\ArrayList(array(
                        'visible' => false,
                        'appVisible' => true
                    )),
            t('Расширенные'),
                    'short_content' => new Type\Richtext(array(
                        'description' => t('Краткий текст'),
                    )),

            t('Мета тэги'),
                    'meta_title' => new Type\Varchar(array(
                        'maxLength' => 1000,
                        'description' => t('Заголовок'),
                    )),
                    'meta_keywords' => new Type\Varchar(array(
                        'maxLength' => 1000,
                        'description' => t('Ключевые слова'),
                    )),
                    'meta_description' => new Type\Varchar(array(
                        'maxLength' => 1000,
                        'viewAsTextarea' => true,
                        'description' => t('Описание'),
                    )),
            t('Фото'),
                    '_photo_' => new Type\UserTemplate('%article%/form/article/photos.tpl'),
            '_tmpid' => new Type\Mixed()
            
        ));
        
        if (\RS\Module\Manager::staticModuleExists('catalog')) {
            $this->getPropertyIterator()->append(array(
                t('Прикреплённые товары'),
                    '_attached_products_' => new Type\UserTemplate('%article%/form/products/attachedproducts.tpl')
            ));
        }

        //Включаем в форму hidden поле id.
        $this['__id']->setVisible(true);
        $this['__id']->setMeVisible(false);
        $this['__id']->setHidden(true);
                
        $this->addIndex(array('site_id', 'parent'));
    }
    
    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return RS\Debug\Action[]
    */
    function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'article-ctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'article-ctrl'))
        );
    }
    
    /**
    * Вызывается после загрузки объекта
    * @return void
    */
    function afterObjectLoad()
    {
        if (!empty($this['attached_products'])) {
            $this['attached_products_arr'] = unserialize($this['attached_products']);
        }
    }
    
   
    
    function beforeWrite($flag)
    {
        //Прикреплённые товары
        $this['attached_products'] = serialize($this['attached_products_arr']);
        
        if ($this['id']<0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }        
        
        if (empty($this['alias'])) $this['alias'] = null;
        if (empty($this['user_id'])) {
            $this['user_id'] = \RS\Application\Auth::getCurrentUser()->id;
        }
    }          
    
    
    
    
    /**
    * Функция срабатывает перед записью
    * 
    * @param string $flag - флаг какое действие происходит, insert или update
    * @return void
    */
    function afterWrite($flag)
    {
        //Переносим временные объекты, если таковые имелись
        if ($this['_tmpid']<0) {
            
            //Получим id слов, которые прикреплены
            $tags = \RS\Orm\Request::make()
                ->from(new \Tags\Model\Orm\Link())
                ->where(array(
                    'type' => self::TAG_TYPE,
                    'link_id' => $this['_tmpid'],
                ))
                ->exec()->fetchSelected('word_id', 'word_id');
                
            //Удалим предварительные денные по тегам и слову
            if ($tags){
              \RS\Orm\Request::make()
                  ->from(new \Tags\Model\Orm\Link())
                  ->where(array(
                      'type' => self::TAG_TYPE,
                      'link_id' => $this['id'],
                  ))
                  ->whereIn('word_id', $tags)
                  ->delete()
                  ->exec();  
            }
            
            \RS\Orm\Request::make()
                ->update(new \Tags\Model\Orm\Link())
                ->set(array('link_id' => $this['id']))
                ->where(array(
                    'type' => self::TAG_TYPE,
                    'link_id' => $this['_tmpid']
                ))->exec();
            
            \RS\Orm\Request::make()
                ->update(new \Photo\Model\Orm\Image())
                ->set(array('linkid' => $this['id']))
                ->where(array(
                    'type' => self::IMAGES_TYPE,
                    'linkid' => $this['_tmpid']
                ))->exec();            
        }
        
        $this->updateSearchIndex();
    }
    
    /**
    * Возвращает список фотографий, привязанных к статье
    * 
    * @return array of \Photo\Model\Orm\Photo
    */
    function getPhotos()
    {
        $photoapi = new \Photo\Model\PhotoApi();
        return $photoapi->getLinkedImages($this['id'],self::IMAGES_TYPE);   
    }
    
    /**
    * Возвращает краткий текст заданный пользователем, 
    * а если он не задан, то сформированный из основного текста
    * 
    * @param integer $length - длинна текста
    * @param boolean $html - выводить как HTML? 
    */
    function getPreview($length = 500, $html = true)
    {
        $preview = $this['short_content'];
        if (!$html) $preview = strip_tags($preview);
        if (empty($preview)) {
            $text = preg_replace("/\<br.*?\>/iu","\n",strip_tags($this['content'], '<br>'));
            $preview = nl2br(\RS\Helper\Tools::teaser($text, $length));
        }
        return $preview;
    }
    
    /**
    * Возвращает объект категории, к которой принадлежит статья
    * 
    * @return Category
    */
    function getCategory()
    {
        return new Category($this['parent']);
    }


    

    /**
    * Возвращает url на страницу просмотра новости
    * 
    * @return string
    */
    function getUrl($absolute = false)
    {
        $id = !empty($this['alias']) ? $this['alias'] : $this['id'];
        $dir = $this->getCategory();
        $dir_alias = $dir['alias'] ?: $this['parent'];
        
        return \RS\Router\Manager::obj()->getUrl('article-front-view', array('category' => $dir_alias, 'id' => $id), $absolute);
    }
    
    /**
    * Удаляет статью
    * 
    * @return bool
    */
    function delete()
    {
        if (empty($this['id']))
            return false;
                    
        //Удаляем фотографии, при удалении товара
        $photoapi = new \Photo\Model\PhotoApi();
        $photoapi->setFilter('linkid', $this['id']);
        $photoapi->setFilter('type', self::IMAGES_TYPE);
        $photo_list = $photoapi->getList();
        foreach ($photo_list as $photo) {
            $photo->delete();
        }
        
        //Удалим тэги
        $tags_api = new \Tags\Model\Api();
        $tags_api->delByLinkAndType($this['id'],self::TAG_TYPE);
        
        return parent::delete();
    }
    
    function getUser()
    {
        return new \Users\Model\Orm\User($this['user_id']);
    }
    
    /**
    * Возвращает HTML код для блока "Прикреплённые товары"
    */
    function getAttachProductsDialog()
    {
        return new \Catalog\Model\ProductDialog('attached_products_arr', true, @(array) $this['attached_products_arr']);
    }
    
    /**
    * Возвращает товары, рекомендуемые вместе с текущим
    *
    * @param bool $only_available - Если true, то возвращаются только те товары, что есть в наличии
    * @return array of Product
    */
    function getAttachedProducts($only_available = false)
    {
        if ($this->attach_products === null){
           $this->attach_products = array();
           $this['attached_products_arr'] = unserialize($this['attached_products']);
           if (isset($this['attached_products_arr']['product'])) {
                foreach ($this['attached_products_arr']['product'] as $id) {
                    $product = new \Catalog\Model\Orm\Product($id);
                    if (isset($product['id']) && (!$only_available || $product['num'] >0) ){//Если товар существует
                       $this->attach_products[] = new \Catalog\Model\Orm\Product($id); 
                    } 
                }
                if (!empty($this->attach_products)){ //Если товары подгружены
                  $this->fast_mark_attached_products_use = true;  
                }
           }else{
                $this->fast_mark_attached_products_use = false;
           } 
        }
        
        return $this->attach_products;
    }
    
    /**
    * Возвращает true или false проверяю есть ли прикреплённые товары или нет.
    * 
    * @return boolean
    */
    function isAttachedProductsUse(){
       if ($this->fast_mark_attached_products_use !== null) {
            return $this->fast_mark_attached_products_use;
       }        
       $this->getAttachedProducts();
       return $this->fast_mark_attached_products_use;
    }
    
    /**
    * Возвращает райтинг статьи в процентах от 0 до 100
    * 
    * @return integer
    */
    function getRatingPercent()
    {
        return round($this['rating'] / self::MAX_RATING, 1) * 100;
    }

    /**
    * Возвращает средний балл товара
    * 
    * @return float
    */
    function getRatingBall()
    {
        return round(self::MAX_RATING * ($this->getRatingPercent() / 100), 2);
    }
    
    /**
    * Возврщает количество комментариев
    * 
    * @return integer
    */
    function getCommentsNum()
    {
        return (int) $this['comments'];
    }
    
    /**
    * Возвращает клонированный объект статьи
    * @return Article
    */
    function cloneSelf()
    {
        //Получим id слов, которые прикреплены
        $tags = \RS\Orm\Request::make()
            ->from(new \Tags\Model\Orm\Link())
            ->where(array(
                'type' => self::TAG_TYPE,
                'link_id' => $this['id'],
            ))
            ->objects();
        
        /**
        * @var \Shop\Model\Orm\Payment
        */
        $clone = parent::cloneSelf();

        //Клонируем фото, если нужно
        if ($clone['image']){
           /**
           * @var \RS\Orm\Type\Image
           */
           $clone['image'] = $clone->__image->addFromUrl($clone->__image->getFullPath());
        }
        $clone->setTemporaryId();
        //Привяжем фото
        $images = $this->getPhotos();
        foreach ($images as $image) {
            $image['linkid'] = $clone['id'];
            $image['id']     = null;
            $image->insert();
        }
        
        //Привяжем теги
        foreach($tags as $tag){
            $tag['id'] = null;
            $tag['link_id'] = $clone['id'];
            $tag->insert();
        }
        
        unset($clone['alias']);
        return $clone;
    }
    
    /**
    * Обновляет поисковый индекс
    * 
    * @return bool
    */
    function updateSearchIndex()
    {
        $config = \RS\Config\Loader::byModule($this);
        if ($config['search_fields']) {
            $title = in_array('title', $config['search_fields']) ? $this['title'] : '';
            \Search\Model\IndexApi::updateSearch($this, $this['id'], $title, $this->getSearchText());
            return true;
        }
        return false;
    }
    
    /**
    * Возвращает текст, который попадет в индекс для данной статьи
    * 
    * @return string
    */
    function getSearchText()
    {
        $config = \RS\Config\Loader::byModule($this);
        $index_text = array();
        if (in_array('short_content', $config['search_fields'])) {
            $index_text[] = $this['short_content'];
        }
        if (in_array('content', $config['search_fields'])) {
            $index_text[] = $this['content'];
        }
        if (in_array('meta_keywords', $config['search_fields'])) {
            $index_text[] = $this['meta_keywords'];
        }
        
        return implode(' ', $index_text);
    }
}

