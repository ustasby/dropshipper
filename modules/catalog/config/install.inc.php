<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Config;

/**
* Класс отвечает за установку и обновление модуля
* @ingroup Catalog
*/
class Install extends \RS\Module\AbstractInstall
{
    function install()
    {
        
        $result = parent::install();
        if ($result) {
            //Вставляем в таблицы данные по-умолчанию, в рамках нового сайта, вызывая принудительно обработчик события
            \Catalog\Config\Handlers::onSiteCreate(array(
                'orm' => \RS\Site\Manager::getSite(),
                'flag' => \RS\Orm\AbstractObject::INSERT_FLAG
            ));
            
            //Добавляем виджеты на рабочий стол
            $widget_api = new \Main\Model\Widgets();
            $widget_api->setUserId(1);
            $widget_api->insertWidget('catalog-widget-watchnow', 1, 0);
            $widget_api->insertWidget('catalog-widget-oneclick', 3);
        }
        
        return $result;
    }
    
    /**
    * Функция обновления модуля, вызывается только при обновлении
    */
    function update()
    {
        $result = parent::update();
        if ($result) {
            $user = new \Users\Model\Orm\User();
            $user->dbUpdate();
        }
        return $result;
    }     
    
    /**
    * Добавляет демонстрационные данные
    * 
    * @param array $params - произвольные параметры. 
    * @return boolean|array
    */
    function insertDemoData($params = array())
    {
        return $this->importCsvFiles(array(
            array('\Catalog\Model\CsvSchema\Brand', 'brand'),
            array('\Catalog\Model\CsvSchema\Typecost', 'typecost'),
            array('\Catalog\Model\CsvSchema\Property', 'property'),
            array('\Catalog\Model\CsvSchema\Unit', 'unit'),
            array('\Catalog\Model\CsvSchema\Dir', 'dir'),
            
            array('\Catalog\Model\CsvSchema\Product', 'product'),
            array('\Catalog\Model\CsvSchema\Offer', 'offer'),
            array('\Catalog\Model\CsvSchema\DirProperty', 'dirproperty'),
         ), 'utf-8', $params);
    }
    
    /**
    * Возвращает true, если модуль может вставить демонстрационные данные
    * 
    * @return bool
    */
    function canInsertDemoData()
    {
        return true;
    }
    
    /**
    * Выполняется, после того, как были установлены все модули. 
    * Здесь можно устанавливать настройки, которые связаны с другими модулями.
    * 
    * @param array $options параметры установки
    * @return bool
    */    
    function deferredAfterInstall($options)
    {
        if ($options['set_demo_data']) {
            $site_config = \RS\Config\Loader::getSiteConfig();
            if ($site_config->getThemeName() == 'default') {
                $dir_api = new \Catalog\Model\Dirapi();
                $top_dir_id = $dir_api->getIdByAlias('top');

                //Настраиваем блок Лидеры продаж
                \Templates\Model\PageApi::setupModule('main.index', 'catalog\controller\block\topproducts', array(
                    'dirs' => $top_dir_id
                ));
                
                //Настраиваем блок товары в виде баннера
                \Templates\Model\PageApi::setupModule('main.index', 'catalog\controller\block\bannerview', array(
                    'categories' => array(
                        $dir_api->getIdByAlias('popular'),
                        $dir_api->getIdByAlias('newest')
                    )
                ));                
                
                //Настраиваем спецкатегорию, которую выводим в брендах
                $actual_dir = \Catalog\Model\Orm\Dir::loadByWhere(array(
                    'alias' => 'actual'
                ));
                if ($actual_dir['id']) {
                    $catalog_config = \RS\Config\Loader::byModule($this);
                    $catalog_config['brand_products_specdir'] = $actual_dir['id'];
                    $catalog_config->update();
                }
            }
        }
        return true;
    }    
    
}
