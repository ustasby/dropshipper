<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm;
use \RS\Orm\Type;
 
/**
* Класс ORM-объектов "Бренд".
* Наследуется от объекта \RS\Orm\OrmObject, у которого объявлено свойство id
*/
class Brand extends \RS\Orm\OrmObject
{
    protected static
        $table = 'brand'; //Имя таблицы в БД
         
    /**
    * Инициализирует свойства ORM объекта
    *
    * @return void
    */
    function _init()
    {        
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),  
            'title' => new Type\Varchar(array(
                'maxLength'   => '250',
                'description' => t('Название бренда'),
                'checker' => array('chkEmpty', t('Укажите название бренда')),
                'attr' => array(array(
                    'data-autotranslit' => 'alias'
                ))
            )),     
            'alias' => new Type\Varchar(array(
                'description' => t('URL имя'),
                'checker' => array('chkEmpty', t('Укажите идентификатор бренда')),
                'meVisible' => false
            )),
            'public' => new Type\Integer(array(
                'maxLength' => 1,
                'index' => true,
                'default' => 1,
                'description' => t('Публичный'),
                'checkboxView' => array(1,0)
            )),
            'image' => new Type\Image(array( //Будет отображаться форма загрузки файла
                'max_file_size'    => 10000000, //Максимальный размер - 10 Мб
                'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'), //Допустимы форматы jpg, png, gif
                'description'      => t('Картинка'),
            )),
            'description' => new Type\Richtext(array(
                'description' => t('Описание')
            )),
            'xml_id' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Идентификатор в системе 1C'),
                'meVisible' => false
            )),
            'sortn' => new Type\Integer(array(
                'maxLength' => 11,
                'index' => true,
                'description' => t('Сортировочный номер'),
                'visible' => false
            )),
            t('Мета-теги'),
               'meta_title' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('Заголовок'),
               )),
               'meta_keywords' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('Ключевые слова'),
               )),
               'meta_description' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'viewAsTextarea' => true,
                    'description' => t('Описание'),
               )), 
        ));
        $this->addIndex(array('site_id', 'alias'), self::INDEX_UNIQUE);
    }
    
    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return RS\Debug\Action[]
    */
    function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'catalog-brandctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'catalog-brandctrl'))
        );
    }
    
    /**
    * Функция срабатывает перед записью в базу
    * 
    * @param mixed $flag
    * @return void
    */
    function beforeWrite($flag)
    {
        //При вставке
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as max')
                ->from($this)
                ->exec()->getOneField('max', 0) + 1;
        }
    }
    
    
    
    /**
    * Возвращает ссылку на бренд
    * @return string
    */
    function getUrl()
    {
       $router = \RS\Router\Manager::obj();
       return $router->getUrl('catalog-front-brand',array(
           'id' => $this['alias']
       )); 
    }
    
    /**
    * Возвращает объект фото-заглушку
    * @return \Photo\Model\Stub
    */
    function getImageStub()
    {
        return new \Photo\Model\Stub();
    }
    
    /**
    * Возвращает главную фотографию (первая в списке фотографий)
    * @return \Photo\Model\Orm\Image
    */
    function getMainImage($width = null, $height = null, $type = 'xy')
    {
        $img = $this['image'] ? $this->__image : $this->getImageStub();
        
        return ($width === null) ? $img : $img->getUrl($width, $height, $type);
    }
    
    /**
    * Удаление
    * 
    */
    function delete()
    {
        if ($result = parent::delete()) {
            if ($this['id']) {
                \RS\Orm\Request::make()
                    ->update(new Product())
                    ->set(array(
                        'brand_id' => 0
                    ))->where(array(
                        'brand_id' => $this['id']
                    ))->exec();
            }
        }
        return $result;
    }
    
    /**
    * Возвращает клонированный объект бренда
    * @return Brand
    */
    function cloneSelf()
    {
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
        unset($clone['alias']);
        return $clone;
    }
}