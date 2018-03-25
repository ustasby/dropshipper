<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Model\Orm;
use \RS\Orm\Type;

/**
* Класс ORM Объектов, описывающих зону для баннеров
*/
class Zone extends \RS\Orm\OrmObject
{
    protected static
        $table = 'banner_zone';
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Название')
            )),
            'alias' => new Type\Varchar(array(
                'description' => t('Симв. идентификатор'),
                'uniq' => true
            )),            
            'width' => new Type\Integer(array(
                'description' => t('Ширина области, px')
            )),
            'height' => new Type\Integer(array(
                'description' => t('Высота области, px')
            ))
        ));
    }
    
    /**
    * Возвращает все баннеры, связанные с текущей зоной
    * 
    * @return array of Banner
    */
    function getBanners()
    {
        $banners = \RS\Orm\Request::make()
            ->select('B.*')
            ->from(new Banner(), 'B')
            ->join(new Xzone(), 'X.banner_id = B.id', 'X')
            ->where("X.zone_id = '#zone_id'", array('zone_id' => $this['id']))
            ->where(array(
                'public' => 1
            ))
            ->orderby('weight desc')
            ->objects();

        //Отфильтруем по дате, если расписание включено
        if (!empty($banners)){
            foreach ($banners as $k=>$banner){
                $cur_time = time();
                if ($banner['use_schedule'] && ($cur_time < strtotime($banner['date_start']) || ($cur_time > strtotime($banner['date_end'])))){
                    unset($banners[$k]);
                }
            }
        }

        return $banners;
    }
    
    /**
    * Возвращает баннер для текущей зоны с учетом всех параметров ротации
    * 
    * @return Banner
    */
    function getOneBanner()
    {
        $banners = $this->getBanners();
        $banner_index = 0;
        $max_weight = 0;
        foreach($banners as $n => $banner) {
            $weight_value = $banner['weight'] * rand(0, 1000);
            if ($max_weight < $weight_value) {
                $max_weight = $weight_value;
                $banner_index = $n;
            }
        }
        
        return isset($banners[$banner_index]) ? $banners[$banner_index] : new Banner();
    }
}
?>
