<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Config;
use \Catalog\Model\Orm,
    \RS\Db\Adapter as DbAdapter;

/**
* Патчи к модулю
*/
class Patches extends \RS\Module\AbstractPatches
{
    /**
    * Возвращает список имен существующих патчей
    */
    function init()
    {
        return array(
            '20082',
            '200163',
            '200188',
            '200202',
            '200204',
            '200207',
            '200211',
            '200214',
            '200216',
            '3025',
            '3035'
        );
    }
    
    /**
    * Исправляет ошибку дублирования значений характеристик
    */
    function afterUpdate200204()
    {
        $item_value = new Orm\Property\ItemValue();
        $link = new Orm\Property\Link();

        $res = \RS\Orm\Request::make()
            ->from($item_value)
            ->exec();
            
        while($prop_value = $res->fetchRow()) {
            //Если свойство дублированное и оно не связано с товарами, то удаляем его
            if (substr($prop_value['value'], -1, 1) == "\r") {
            
                $link_count = \RS\Orm\Request::make()
                    ->from($link)
                    ->where(array(
                        'prop_id' => $prop_value['prop_id'],
                        'val_list_id' => $prop_value['id']
                    ))->count();
                
                if (!$link_count) {
                    \RS\Orm\Request::make()
                        ->delete()
                        ->from($item_value)
                        ->where(array('id' => $prop_value['id']))
                        ->exec();
                }
            }
        }
    }    
    
    /**
    * Обновляет структуру хранения характеристик в БД
    * Переносит значения списковых характеристик в val_list_id
    */
    function afterUpdate200202()
    {
        $api = new \Catalog\Model\PropertyValueApi();
        
        $item_values_count = \RS\Orm\Request::make()
            ->from(new Orm\Property\ItemValue())        
            ->count();
        
        if (!$item_values_count) {
            $property_rows = \RS\Orm\Request::make()
                ->from(new Orm\Property\Item())
                ->where(array(
                    'type' => Orm\Property\Item::TYPE_LIST
                ))->objects();
            
            $value_api = new \Catalog\Model\PropertyValueApi();
            
            foreach($property_rows as $row) {
                //Создаем значения c учетом первоначальной сортировки            
                $values = preg_split('/[;\n]/', htmlspecialchars_decode($row['values']));
                foreach($values as $value) {
                    $item_value = new Orm\Property\ItemValue();
                    $item_value['site_id'] = $row['site_id'];
                    $item_value['prop_id'] = $row['id'];
                    $item_value['value'] = trim($value);
                    $item_value->insert();
                }
                
                //Конвертируем старые значения 
                $value_api->convertPropertyType($row, Orm\Property\Item::TYPE_STRING, Orm\Property\Item::TYPE_LIST);
            }
            
            //Удаляем колонку values
            DbAdapter::sqlExec('ALTER IGNORE TABLE '.$api->getElement()->_getTable().' DROP COLUMN values');
        }
    }


    
    /**
    * Патч, который добавляет всё необходимое для работы со складами
    * 
    */
    function afterUpdate20082()
    {
        //Получаем сайты, которые есть в системе
        $sites = \RS\Site\Manager::getSiteList();
        foreach ($sites as $site){
          $warehouse = Orm\WareHouse::loadByWhere(array(
                'site_id' => $site['id']
            ));

          if (!$warehouse['id']) {
          
              $module = new \RS\Module\Item('catalog');
              $installer = $module->getInstallInstance();
              $installer->importCsv(new \Catalog\Model\CsvSchema\Warehouse(), 'warehouse', $site['id']);

              $warehouse = Orm\WareHouse::loadByWhere(array(
                  'site_id' => $site['id']
              ));
           }
           
           //Теперь создадим остатки по складам для первого основного склада
           if ($warehouse['id']) {
              $product = new Orm\Product(); 
              $offer   = new Orm\Offer(); 
              $x_stock = new Orm\Xstock(); 
              //Найдём товары которые у нас без комплектаций и создаём нулевую комплектацию, для данного товара
              $sql = "
              INSERT IGNORE INTO ".$offer->_getTable()." (site_id,product_id,num,sortn) 
                  (
                      SELECT P.site_id,P.id,P.num,0 FROM ".$product->_getTable()." AS P 
                          LEFT JOIN ".$offer->_getTable()." as O ON P.id=O.product_id 
                          WHERE O.product_id IS NULL AND P.site_id = ".$site['id']."
                  )";
              \RS\Db\Adapter::sqlExec($sql); 
              
              $sql = "
              INSERT INTO ".$x_stock->_getTable()." (`product_id`,`offer_id`,`warehouse_id`,`stock`) (
                SELECT O.product_id,O.id,".$warehouse['id'].",num FROM ".$offer->_getTable()." as O 
                    WHERE O.site_id = ".$site['id']."
              )";
              \RS\Db\Adapter::sqlExec($sql); 
           } 
        } 
    }
    
    /**
    * Патч, переносит значение поля public в checkout_public у объекта warehouse
    */
    function afterUpdate200163()
    {
        \RS\Orm\Request::make()
            ->update(new Orm\WareHouse)
            ->set('checkout_public = public')
            ->exec();
    }
    
    /**
    * Патч, переформатирует записи о покупках в один клик перенесёт данные в соответствующие поля
    */
    function afterUpdate200188()
    {
        //Получим все покупки в один клик 
        $oneclick_items = \RS\Orm\Request::make()
                ->from(new Orm\OneClickItem())
                ->orderby('dateof ASC')
                ->objects();
        if (!empty($oneclick_items)){
            foreach ($oneclick_items as $oneclick){
                $un_stext = $oneclick->tableDataUnserialized('stext');
                $oneclick['user_fio']    = isset($un_stext['name']) ? $un_stext['name'] : "";
                $oneclick['user_phone']  = isset($un_stext['phone']) ? $un_stext['phone'] : "";
                $oneclick['sext_fields'] = isset($un_stext['ext_fields']) ? serialize($un_stext['ext_fields']) : serialize(array());
                
                
                //Подготовим новый массив со сведениями о товаре
                $oneclick['stext'] = serialize(array(array(
                    'id' => isset($un_stext['product']['id'])? $un_stext['product']['id'] : "",
                    'title' => isset($un_stext['product']['title'])? $un_stext['product']['title'] : "",
                    'offer_fields' => isset($un_stext['offer_fields']) ? $un_stext['offer_fields'] : "",
                )));
                $oneclick->update();
            }
            
        }
    }
    
    /**
    * Патч, переносит значение поля hidden в no_export у объекта Property\Item
    */
    function afterUpdate200207()
    {
        \RS\Orm\Request::make()
            ->update(new Orm\Property\Item())
            ->set('no_export = hidden')
            ->exec();
    }
    
    /**
    * Переносит значение, в связи с переименованием поля
    */
    function afterUpdate200211()
    {
        try {
            \RS\Orm\Request::make()
                ->update(new Orm\Property\Link())
                ->set('available = avaible')
                ->exec();
        } catch (\RS\Exception $e) {}
    } 
    
    /**
    * Исправление патча 200202
    * Удаляет колонку values
    */
    function afterUpdate200214()
    {
        $api = new \Catalog\Model\PropertyApi();
        DbAdapter::sqlExec('ALTER IGNORE TABLE '.$api->getElement()->_getTable().' DROP COLUMN values');
    }
    
    /**
    * Патч, устанавливает значение настройки convert_price_round в связи с разделением настройки update_price_round
    */
    function afterUpdate200216()
    {
        $api = new \Catalog\Model\PropertyApi();
        DbAdapter::sqlExec('ALTER IGNORE TABLE '.$api->getElement()->_getTable().' DROP COLUMN `values`');
        
        $config = \RS\Config\Loader::byModule($this);
        $config['convert_price_round'] = $config['update_price_round'];
        $config->update();
    }
    
    /**
    * Совмещает настройки настройки округления цен в одну
    */
    function afterUpdate3025()
    {
        foreach (\RS\Site\Manager::getSiteList() as $site) {
            $config = \RS\Config\Loader::byModule('catalog', $site['id']);
            if ($config['update_price_round'] || $config['convert_price_round']) {
                $config['price_round'] = pow(10, $config['update_price_round_value']);
                $config->update();
            }
        }
    }

    /**
     * Добавляет в конфиг каталога товаров опцию для поисковой строки для сохранения в поисковый индекс бренда
     */
    function afterUpdate3035()
    {
        $config = \RS\Config\Loader::byModule($this);
        $list   = $config['search_fields'];
        $list[] = "brand"; //Опция по бренду включена
        $config['search_fields'] = $list;
        $config->update();
    }
}
