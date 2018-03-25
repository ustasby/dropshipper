<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Model\Orm;
use \RS\Orm\Type;

class Category extends \RS\Orm\OrmObject
{
    protected static
        $table = "article_category";

    protected function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => t('Название'),
                    'Checker' => array('chkEmpty',t('Необходимо заполнить поле название')),
                )),
                'alias' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => t('Псевдоним(Ан.яз)'),
                )),
                'parent' => new Type\Integer(array(
                    'description' => t('Родительская категория'),
                    'List' => array(array('\\Article\\Model\\Catapi', 'selectList')),
                    'Attr' => array(array('size' => 1)),
                )),
                'public' => new Type\Integer(array(
                    'description' => t('Показывать на сайте?'),
                    'maxLength' => 1,
                    'default' => 1,
                    'checkboxView' => array(1,0)
                )),
                'sortn' => new Type\Integer(array(
                    'description' => t('Сортировочный индекс'),
                    'maxLength' => '11',
                    'visible' => false,
                )),
                'use_in_sitemap' => new Type\Integer(array(
                    'description' => t('Добавлять в sitemap'),
                    'checkboxView' => array(1,0)
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
        ));
        
        $this->addIndex(array('parent', 'site_id', 'alias'), self::INDEX_UNIQUE);
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $save_flag - insert или update
    * @return null
    */
    function beforeWrite($save_flag)
    {
        if ($save_flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                                ->select('MAX(sortn)+1 last_sortn')
                                ->from($this)
                                ->exec()
                                ->getOneField('last_sortn', 1);
        }
        
        if ($save_flag == self::UPDATE_FLAG) {
            if ($this['parent'] == $this['id']) {
                $this->addError(t('Не верно указана родительская категория'));
                return false;
            }
        }
        
        if ($this['alias'] === '') {
            $this['alias'] = null;
        }
        return true;
    }
    
    /**
    * Возвращает статьи привязанные к категории статей 
    * 
    */
    function getArticles()
    {
        return \RS\Orm\Request::make()->select('*')
            ->from(new Article())
            ->where( array('parent' => $this['id']) )
            ->objects();
    }
    
    /**
    * Возвращает alias, а если он не задан, то id
    * 
    * @return mixed
    */
    function getUrlId()
    {
        return $this['alias'] ?: $this['id'];
    }
    
    /**
    * Возвращает путь к списку статей данной категории на сайте
    * 
    * @param bool $absolute - Если true, то будет возвращен абсолютный путь
    * @return string
    */
    function getUrl($absolute = false)
    {
        return \RS\Router\Manager::obj()->getUrl('article-front-previewlist', array('category' => $this->getUrlId()), $absolute);        
    }
    
    /**
    * Возвращает родительскую категорию
    * 
    * @return self
    */
    function getParent()
    {
        return new self($this['parent']);
    }
    
    function delete()
    {
        //При удалении категории удаляем все статьи, котое находятся в ней
        \RS\Orm\Request::make()
            ->delete()
            ->from(new \Article\Model\Orm\Article())
            ->where(array('parent' => $this['id']))
            ->exec();
        parent::delete();
    }
}

