<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model;

/**
 * Предоставляет возможности для импорта данных из YML файлов
 *
 */
class ImportYmlApi extends \RS\Module\AbstractModel\BaseModel
{
    const YML_ID_PREFIX = "yml_",
          DELETE_LIMIT = 100;

    public
        $storage,
        $xmlIds;
    
    protected 
        $site_id,
        $cost_id = 0,
        $old_cost_id = 0,
        $yml_api,
        $timeout,
        $allow_ext = array('yml', 'xml'),
        $tmp_data_file = 'data.tmp',
        $log_name = 'yml_import.log',
        $log_file_rel,
        $log_file,
        $yml_name = 'tmp.yml',
        $yml_folder,
        $yml_folder_rel = '/storage/tmp/importyml',
        $start_time,
        $params_fields = array(
            'delivery' => 'Доставка',
            'local_deliver_cost' => 'Стоимость доставки',
            'typePrefix' => 'Тип',
            'vendorCode' => 'Код производителя',
            'model' => 'Модель',
            'manufacturer_warranty' => 'Гарантия производителя',
            'country_of_origin' => 'Страна-производитель',
            'author' => 'Автор',
            'publisher' => 'Издатель',
            'series' => 'Серия',
            'year' => 'Год',
            'ISBN' => 'ISBN',
            'volume' => 'Объем',
            'part' => 'Часть',
            'language' => 'Язык',
            'binding' => 'Переплет',
            'page_extent' => 'Количество страниц',
            'downloadable' => 'Возможность скачать',
            'performed_by' => 'Исполнитель',
            'performance_type' => 'Тип представления',
            'storage' => 'Носитель',
            'format' => 'Формат',
            'recording_length' => 'Длительность записи',
            'artist' => 'Артист',
            'media' => 'Носитель медиа',
            'starring' => 'В главных ролях',
            'country' => 'Страна',
            'director' => 'Директор',
            'originalName' => 'Оригинальное название',
            'worldRegion' => 'Регион мира',
            'region' => 'Регион',
            'days' => 'Дни',
            'title' => 'Название',
            'dataTour' => 'Дата тура',
            'hotel_stars' => 'Количество звезд',
            'room' => 'Комнаты',
            'meal' => 'Питание',
            'included' => 'Включено',
            'transport' => 'Транспорт',
            'place' => 'Место проведения',
            'hall' => 'Зал',
            'hall_part' => 'Расположение',
            'date' => 'Дата',
            'is_premiere' => 'Премьера',
            'is_kids' => 'Для детей',
            'adult' => 'Для взрослых',
            'pickup' => 'Самовывоз',
            'store' => 'Точка продаж',
            'sales_notes' => 'Ценовые предложения'
        );


       // static $cachecheck = array();
    
    function __construct()
    {
        $this->cachecheck = array();


        $this->yml_folder = \Setup::$PATH.$this->yml_folder_rel;
        $this->log_file_rel = $this->yml_folder_rel.'/'.$this->log_name;
        $this->log_file = \Setup::$PATH.$this->log_file_rel;
        $this->config = \RS\Config\Loader::byModule($this);
        $this->setTimeout($this->config->import_yml_timeout);
        $this->setSiteId(\RS\Site\Manager::getSiteId());
        $this->setCostId($this->config->import_yml_cost_id ? $this->config->import_yml_cost_id : \Catalog\Model\CostApi::getDefaultCostId());
        $this->setOldCostId($this->config->old_cost);

        $this->start_time = microtime(true);
        $this->loadLocalStorage();
    }

    /**
     * @param $cost_id
     */
    function setCostId($cost_id)
    {
        $this->cost_id = $cost_id;
    }

    /**
     * @return integer
     */
    function getCostId()
    {
        return $this->cost_id;
    }

    /**
     * Устанавливает зачеркнутую цену
     *
     * @param integer $cost_id - ID цены
     */
    function setOldCostId($cost_id)
    {
        $this->old_cost_id = $cost_id;
    }

    /**
     * Возвращает ID зачеркнутой цены
     *
     * @return integer
     */
    function getOldCostId()
    {
        return $this->old_cost_id;
    }
    
    /**
    * Устанавливает сайт, для которого будет происходить импорт данных
    * 
    * @param integer $site_id - ID сайта
    * @return void
    */
    function setSiteId($site_id)
    {
        $this->site_id = $site_id;
    }
    
    /**
    * Устанавливает время работы одного шага импорта
    * 
    * @param integer $sec - количество секунд. Если 0 - то время шага не контроллируется
    * @return void
    */
    function setTimeout($sec)
    {
        $this->timeout = $sec;
    }
    
    /**
    * Загружает временные данные текущего импорта
    * 
    * @return array
    */
    function loadLocalStorage()
    {
        if (!isset($this->storage)) {
            $filename = $this->yml_folder.'/'.$this->tmp_data_file;
            if (file_exists($filename)) {
                $this->storage = unserialize( file_get_contents($filename) );
            }
            if (!isset($this->storage)) {
                $this->storage = array();
            }
        }
        return $this->storage;
    }
    
    /**
    * Сохраняет подготовленную информацию $value под ключем $key
    * 
    * @param mixed $key
    * @param mixed $value
    * @return SiteUpdate
    */
    function saveLocalKey($key, $value = null)
    {
        if (!isset($this->storage)) $this->storage = $this->loadLocalStorage();
        
        if ($key === null) {
            $this->storage = array();
        } elseif (is_array($key)) {
            $this->storage = array_merge($this->storage, $key);
        } else {
            $this->storage[$key] = $value;
        }
        $this->flushLocalStorage();
        
        return $this;
    }    
    
    function flushLocalStorage()
    {
        file_put_contents( $this->yml_folder.'/'.$this->tmp_data_file, serialize($this->storage) );
    }
    
    /**
     * Загружает данные из YML файла в XMLReader
     * 
     * @param type $file файл в формате YML
     * @return boolean
     */
    function uploadFile($file)
    {    
        \RS\File\Tools::makePath($this->yml_folder);
        \RS\File\Tools::deleteFolder($this->yml_folder, false);        
        
        $uploader = new \RS\File\Uploader($this->allow_ext, $this->yml_folder_rel);
        $uploader->setRightChecker(array($this, 'checkWriteRights'));
        
        if (!$uploader->setUploadFilename($this->yml_name)->uploadFile($file)) {
            return $this->addError($uploader->getErrorsStr());
        }
        
        return true;
    }        
    
    /**
    * Выполняет один шаг импорта
    * 
    * @param array $step_data Массив с параметрами импорта:
    * [
    *   'upload_image' => bool, //Загружать изображения или нет
    *   'step' => integer, //номер текущего шага
    *   'offset' => integer //количество обработанных раннее элементов в шаге
    * ]
    * 
    * @return array | bool Если возвращает array, то это означает что необходимо выполнить следующий шаг импорта с данными параметрами
    * Если возвращает false, значит во время импорта произошла ошибка
    * Если возвращает true, значит импорт завершен
    */
    function process($step_data)
    { 
        $this->getXmlIds();           
        $steps = $this->getSteps($step_data);
        
        $current_step_data = $steps[ $step_data['step'] ];
        $method = $current_step_data['method'];
        
        $result = $this->$method( $step_data );
        if (is_array($result)) {
            //Шаг просит повторного выполнения
            if (isset($current_step_data['make_callback'])) {
                $result += call_user_func($current_step_data['make_callback']);
            }
            return $result + array(
                'upload_images' => $step_data['upload_images'],
                'step'         => $step_data['step']
            );
            
        } elseif ($result) {
            //Шаг успешно выполнен, переходим к следующему или завершаем
            if ($step_data['step'] == count($steps)-1) {
                return $this->finishProcess();
            } else {
                $next_step = $step_data['step'] + 1;
                return array(
                    'upload_images' => $step_data['upload_images'],
                    'step'         => $next_step,
                    'offset'       => 0
                ) + (isset($steps[$next_step]['make_callback']) 
                        ? call_user_func($steps[$next_step]['make_callback']) 
                        : array());
            }
        }
        
        //Произошла ошибка
        return false;
    }
    
    /**
    * Возвращает список шагов, которые будут выполнены во время импорта
    * 
    * @param array $step_data - параметры импорта
    * @return array
    */
    function getSteps($step_data)
    {    
        $_this = $this;
        $steps = array(
            array(
                'title' => t('Подготовка к импорту'),
                'successTitle' => t('Подготовка к импорту завершена'),
                'method' => 'stepStart'
            ),
            array(
                'title' => t('Импорт валют'),
                'successTitle' => t('Валюты импортированы'),
                'method' => 'stepCurrency'
            ),
            array(
                'title' => t('Импорт категорий'),
                'successTitle' => t('Категории импортированы'),
                'method' => 'stepCategory',
                'make_callback' => function() use ($_this) {
                    return array(
                        'total' => $_this->storage['yml_info']['category_count']
                    );
                }
            ),
            array(
                'title' => t('Импорт товаров'),
                'successTitle' => t('Товары импортированы'),
                'method' => 'stepProduct',
                'make_callback' => function() use ($_this) {
                    return array(
                        'total' => count($_this->storage['yml_info']['products_xml_ids'])
                    );
                }
            ),
            array(
                'title' => t('Импорт рекомендуемых товаров'),
                'successTitle' => t('Рекомендуемые товары импортированы'),
                'method' => 'stepRecommended',
                'make_callback' => function() use ($_this) {
                    return array(
                        'total' => count($_this->storage['yml_info']['products_xml_ids'])
                    );
                }
            ),
        );
        
        if (!empty($step_data['upload_images'])) {
            $steps = array_merge($steps, array(
                array(
                    'title' => t('Импорт изображений'),
                    'successTitle' => t('Изображения импортированы'),
                    'method' => 'stepImages',
                    'make_callback' => function() use ($_this) {
                        return array(
                            'total' => $_this->storage['yml_info']['photo_count']
                        );
                    }                    
                )
            ));                
        }

        if($this->config->catalog_element_action == \Catalog\Config\File::ACTION_REMOVE){
            $steps = array_merge($steps, array(
                array(
                    'title' => t('Удаление товаров'),
                    'successTitle' => t('Товары отсутствующие в файле удалены'),
                    'method' => 'afterimportproducts',

                )
            ));
        }
        if($this->config->catalog_element_action == \Catalog\Config\File::ACTION_DEACTIVATE){
            $steps = array_merge($steps, array(
                array(
                    'title' => t('Деактивация товаров'),
                    'successTitle' => t('Товары отсутствующие в файле деактивированы'),
                    'method' => 'afterimportproducts',


                )
            ));
        }
        if($this->config->catalog_section_action == \Catalog\Config\File::ACTION_DEACTIVATE){
        $steps = array_merge($steps, array(
            array(
                'title' => t('Деактивация категорий'),
                'successTitle' => t('Категории отсутствующие в файле отключены'),
                'method' => 'afterimportdirs',

            )
        ));
    }
        if($this->config->catalog_section_action == \Catalog\Config\File::ACTION_REMOVE){
            $steps = array_merge($steps, array(
                array(
                    'title' => t('Удаление категорий'),
                    'successTitle' => t('Категории отсутствующие в файле удалены'),
                    'method' => 'afterimportdirs',

                )
            ));
        }

        if($this->config->catalog_element_action == \Catalog\Config\File::ACTION_CLEAR_STOCKS){
            $steps = array_merge($steps, array(
                array(
                    'title' => t('Обнуление остатков'),
                    'successTitle' => t('Остатки у товаров,отсутствующих в файле, обнулены'),
                    'method' => 'afterimportproducts',

                )
            ));

        }

        return $steps;
    }

    /**
    * Подготовка к импорту
    * 
    * @param array $step_data - параметры импорта
    * @return bool
    */
    private function stepStart($step_data)
    {
        \RS\Orm\Request::make()
        ->update(new \Catalog\Model\Orm\Product())
        ->set(array('processed' => null))
        ->exec();

        //Очистка временного хранилища
        $this->saveLocalKey(null);
        $this->saveLocalKey('yml_info', $this->prepareImport());
        $this->saveLocalKey(array(
            'statistic' => array(
                'inserted_categories' => 0,
                'updated_categories' => 0,
                'inserted_offers' => 0,
                'updated_offers' => 0,            
                'inserted_photos' => 0,
                'already_exists_photos' => 0,
                'not_downloaded_photos' => 0,
                'deactivated_categories' => 0,
                'removed_categories' => 0,
                'deactivated_products' => 0,
                'removed_products' => 0,
                'cs_products' => 0
            )
        ));
        $this->writeLog('Import started...', false);
        
        return true;
    }
    
    /**
    * Заверщает процесс импорта
    * 
    * @return bool
    */
    private function finishProcess()
    {
        $this->writeLog('Import completed...');
        $this->cleanTemporaryDir();


        return true;
    }
    
    /**
    * Импортирует валюты
    * 
    * @param array $step_data - параметры импорта
    * @return array | bool
    */
    private function stepCurrency($step_data)
    {
        $reader = $this->loadReader('currencies');
        if ($reader->readOuterXML()){
            $currencies = new \SimpleXMLElement($reader->readOuterXML());

            foreach($currencies as $curr_xml){
                $currency_data = array(
                    'site_id'   => $this->site_id,
                    'title'     => (string)$curr_xml->attributes()->id,
                );
                $currency = \Catalog\Model\Orm\Currency::loadByWhere($currency_data);

                //если нет валюты, добавляем ее
                if(!$currency->id){
                    $currency_data['percent'] = $curr_xml->attributes()->plus?(string)$curr_xml->attributes()->plus:0;
                    $currency_data['ratio'] = is_numeric(str_replace(',', '.', $curr_xml->attributes()->rate))?str_replace(',', '.', $curr_xml->attributes()->rate):1;

                    $currency->getFromArray($currency_data)->insert(true);
                };
            }

            $reader->close();

            return true;
        }
        return true;
    }
    
    /**
    * Импортирует категории товаров
    * 
    * @param array $step_data - параметры импорта
    * @return array | bool
    */
    private function stepCategory($step_data)
    {
        //id категорий
        $exists_dir = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Dir())
                ->where(array('site_id' => $this->site_id))
                ->where('xml_id LIKE \''.self::YML_ID_PREFIX.'%\'')
                ->exec()->fetchSelected('xml_id', 'id');
        //config с id родительвкой категории для импорта         
        $config = $this->config;
       
        $reader = $this->loadReader('category');
        $offset = $step_data['offset'];
        
        $dirs = array();
        do{
            $category = new \SimpleXMLElement( $reader->readOuterXML() );
            $dirs[] = array(
                'parent' => (int)$category->attributes()->parentId,
                'id' => (int)$category->attributes()->id,
                'name' => (string) $category[0],
            );

        } while($reader->next('category'));
        array_multisort($dirs);

       $cache = array();
       $count = 0;
        $testcount =1;
     do{  $count = $count +1;
            foreach ($dirs as $key =>$onedir){

                if ( ($onedir['parent'] == '0') or isset($cache[$onedir['parent']-1]) )


                {
                     $cache[$onedir['id']-1] = $onedir;
                     unset($dirs[$key]);
                    $testcount = $testcount + 1;
                }

            }
       }while($count < $testcount +10 );
        unset($dirs);
        foreach ( $cache as $item){
            $dirs[] = $item;
        }
        do{
            $xml_id = self::YML_ID_PREFIX.$dirs[$offset]['id'];
            $uniq_postfix = hexdec(substr(md5($dirs[$offset]['id']), 0, 4));
            $dir = \Catalog\Model\Orm\Dir::loadByWhere(array(
                'xml_id' => $xml_id,
                'site_id' =>\RS\Site\Manager::getSiteId(),
            ));

            $dir['no_update_levels'] = true;
            //создание новой категории
            if(!isset( $dir['id'])){
                $parent_dir = $dirs[$offset]['parent']? $exists_dir[self::YML_ID_PREFIX.$dirs[$offset]['parent']] : $config['yuml_import_setting'];

                $dir['parent']      = $parent_dir;
                $dir['public']      = 1;
                $dir['level']       = 0;
                $dir['weight']      = 0;
                $dir['processed']     = 1;
                $dir['xml_id']      = $xml_id;
                $dir['site_id']     = $this->site_id;
                $dir['meta_title']  = $dir['meta_keywords'] = $dir['meta_description'] = $dir['description'] = '';
                $dir['name']        = $dirs[$offset]['name'];
                $dir['alias']       = \RS\Helper\Transliteration::str2url($dirs[$offset]['name'])."-".$uniq_postfix;
                $dir->insert(true, array('alias', 'parent'), array('name', 'xml_id', 'site_id'));
                $exists_dir[$xml_id] = $dir['id'];
                $this->storage['statistic']['inserted_categories']++;
            }
            else {
                $dir['processed'] = 1;
                $dir->update();
                $this->storage['statistic']['updated_categories']++;
            }
            
            $offset++;
        } while( !($timeout = $this->checkTimeout()) && isset($dirs[$offset]));
        $reader->close();

        $this->flushLocalStorage();
        
        if ($timeout) {
            return array(
                'offset' => $offset,
                'percent' => round($offset / $this->storage['yml_info']['category_count'] * 100)
            );
        }

        \Catalog\Model\Dirapi::updateLevels();
        return true;
    }




    function check($dirs,$id)
    {
        if (!isset($this->cachecheck[$id])) {
                $ids = array_column($dirs, 'id');
                $cachecheck[$id] = array_search($id, $ids);
      }
        return  $cachecheck[$id];
    }
    
    /**
    * Импортирует товары
    * 
    * @param array $step_data - параметры импорта
    * @return array | bool
    */
    private function stepProduct($step_data)
    {
        //Загружаем справочники
        if ($step_data['offset'] == 0) {
            $this->saveLocalKey('list', $this->loadLists());
        }
        
        $reader = $this->loadReader('offer');
        $offset = 0;
        
        do {
            if(++$offset < $step_data['offset']) continue;
            
            $product = $this->importProduct($reader);

            if ($product->getLocalParameter('duplicate_updated')) {
                $this->storage['statistic']['updated_offers']++;
            } else {
                $this->storage['statistic']['inserted_offers']++;
            }            
                        
        } while(!($timeout = $this->checkTimeout()) && $reader->next('offer'));
        $reader->close();
        
        $this->flushLocalStorage();
        
        if ($timeout) {
            return array(
                'offset' => $offset,
                'percent' => round($offset / count($this->storage['yml_info']['products_xml_ids']) * 100)
            );
        }
        
        //Обновляем счетчики у категорий после импорта всех товаров
        \Catalog\Model\Dirapi::updateCounts(); 
        
        return true;
    }
    
    /**
    * Импортирует рекомендуемые товары
    * 
    * @param array $step_data - параметры импорта
    * @return array | bool
    */
    private function stepRecommended($step_data)
    {
        if ($step_data['offset'] == 0) {
            //Создаем справочник xml_id => id
            $this->storage['list']['products'] = $this->loadProductsList();
            $this->saveLocalKey('list', $this->storage['list']);
        }
        
        $reader = $this->loadReader('offer');
        $product = new Orm\Product();
        $offset = 0;
        
        do {
            if(++$offset < $step_data['offset']) continue;
            
            $offer_xml = new \SimpleXMLElement( $reader->readOuterXML() );
            
            if((string) $offer_xml->rec){
                $xml_id = self::YML_ID_PREFIX.strval($offer_xml->attributes()->id);
                $product_id = $this->getIdByXmlId($xml_id);
                $recommended_xml = explode(',', (string)$offer_xml->rec);
                $recommended_arr = array();
                foreach($recommended_xml as $offer_id){
                    $recommended_arr['product'][] = $this->getIdByXmlId(self::YML_ID_PREFIX.$offer_id);
                }
                
                if(!empty($recommended_arr)) {
                    \RS\Orm\Request::make()
                        ->update($product)
                        ->set(array(
                            'recommended' => serialize($recommended_arr)
                        ))
                        ->where(array(
                            'id' => $product_id ,
                            'site_id' => $this->site_id
                        ))->exec();
                }
            }
            
        } while(!($timeout = $this->checkTimeout()) && $reader->next('offer'));
        $reader->close();
        
        if ($timeout) {
            return array(
                'offset' => $offset,
                'percent' => round($offset / count($this->storage['yml_info']['products_xml_ids']) * 100)
            );
        }
        return true;
    }


    function getIdByXmlId($xml_id){
        static $cacheid =  array();

        if (!isset($cacheid[$xml_id])) {


            $res = \RS\Orm\Request::make()
                ->select('id')
                ->from(new \Catalog\Model\Orm\Product)
                ->where(array('xml_id' => $xml_id))
                ->exec()->getOneField('id');

            $cacheid[$xml_id] = $res;
        }

        return $cacheid[$xml_id];
    }

    /**
    * Импортирует изображения
    * 
    * @param array $step_data - параметры импорта
    * @return array | bool
    */    
    private function stepImages($step_data)
    {        
        $reader = $this->loadReader('offer');
        $photoapi = new \Photo\Model\PhotoApi();
        $offset = 0;
        
        do {            
            $offer_xml = new \SimpleXMLElement( $reader->readOuterXML() );
            if ((bool)$offer_xml->picture) {
                $offer_id = strval($offer_xml->attributes()->id);
                $xml_id = self::YML_ID_PREFIX.$offer_id;

                foreach($offer_xml->picture as $picture) {
                    if(++$offset < $step_data['offset']) continue;

                    $product_id = $this->getIdByXmlId($xml_id);

                    //Проверяем, присутствует ли данное фото у товара
                    $image = \RS\Orm\Request::make()
                        ->from(new \Photo\Model\Orm\Image())
                        ->where(array(
                            'site_id'   => $this->site_id,
                            'extra'     => $xml_id,
                            'filename' => basename($picture),
                            'linkid' => $product_id
                        ))
                        ->object();
                    
                    if (!$image) {
                        if ($photoapi->addFromUrl($picture, 'catalog', $product_id, true, $xml_id, false)) {
                            $this->storage['statistic']['inserted_photos']++;
                            $this->writeLog("Offset:".$offset." Offer(id): $offer_id, photo(url): $picture, uploaded");
                        } else {
                            $this->storage['statistic']['not_downloaded_photos']++;
                            $this->writeLog("Offer(id): $offer_id, photo(url): $picture, not uploaded");
                            $photoapi->cleanErrors();                            
                        }
                    } else {
                        $this->storage['statistic']['already_exists_photos']++;
                    }
                }
            }
            
        } while(!($timeout = $this->checkTimeout()) && $reader->next('offer'));
        $reader->close();
        
        $this->flushLocalStorage();
        
        if ($timeout) {
            return array(
                'offset' => $offset,
                'percent' => round($offset / $this->storage['yml_info']['photo_count'] * 100)
            );
        }
        
        return true;
    }
    
    
    /**
    * Возвращает справочники данных
    * @return array
    */
    private function loadLists()
    {
        $list = array();
        $common_where = array('site_id' => $this->site_id);
        //Загружаем категории        
        $list['categories'] = \RS\Orm\Request::make()
                                ->from(new Orm\Dir())
                                ->where($common_where)
                                ->exec()
                                ->fetchSelected('xml_id', 'id');
        
        //Загружаем цены
        $list['costs'] = \RS\Orm\Request::make()
                                ->from(new Orm\Typecost())
                                ->where($common_where)
                                ->exec()
                                ->fetchSelected('title', 'id');
                                
        //Загружаем бренды
        $list['brands'] = \RS\Orm\Request::make()
                                ->from(new Orm\Brand())
                                ->where($common_where)
                                ->exec()
                                ->fetchSelected('title', 'id');
                                
        //Загружаем валюты
        $list['currencies'] = \RS\Orm\Request::make()
                                ->from(new Orm\Currency())
                                ->where($common_where)
                                ->exec()
                                ->fetchSelected('title', 'id');
        
        return $list;
    }
    
    /**
    * Возвращает справочник, содержащий связь xml_id => id
    * 
    * @return array
    */
    private function loadProductsList()
    {
        $xml_ids = array();
        foreach($this->storage['yml_info']['products_xml_ids'] as $xml_id) {
            $xml_ids[] = self::YML_ID_PREFIX.$xml_id;
        }
        
        $products = \RS\Orm\Request::make()
            ->select('xml_id, id')
            ->from(new Orm\Product())
            ->whereIn('xml_id', $xml_ids)
            ->where(array(
                'site_id' => $this->site_id
            ))
            ->exec()->fetchSelected('xml_id', 'id');
        
        return $products;
    }
    
    
    /**
     * Загружает даннные из файла в reader
     * 
     * @return boolean
     */
    private function loadReader($seek_to_element = null)
    {
        $reader = new \XMLReader();
        if (!$reader->open($this->yml_folder.'/'.$this->yml_name)) {
            throw new \RS\Exception(t('Не удается открыть YML файл')." '".$this->yml_name."'", 0);
        }
        
        if ($seek_to_element) {
            while($reader->read() && ($reader->name != $seek_to_element)){}
        }
        
        return $reader;
    }
    
    /**
     * Формирует массив id товаров и категорий
     * 
     * @return void
     */
    private function prepareImport()
    {
        $product_xml_ids = array();
        $category_count = 0;
        $photo_count = 0;
        
        $reader = $this->loadReader();
        while($reader->read()){
            if ($reader->nodeType == \XMLReader::ELEMENT) {
                if ($reader->name == 'category') {
                    $category_count++;
                }    
                
                elseif ($reader->name == 'offer') {
                    $product_xml_ids[] = (int)$reader->getAttribute('id');
                }
                
                elseif ($reader->name == 'picture') {
                    $photo_count++;
                }
            }
        }    
        $reader->close();
        
        return array(
            'products_xml_ids' => $product_xml_ids,
            'category_count' => $category_count,
            'photo_count' => $photo_count
        );
    }

    
    /**
     * Проверка времени выполнения скрипта, при превышении сохраняет состояние
     * 
     * @param type $cnt
     * @return boolean
     */
    private function checkTimeout()
    {
        return ($this->timeout && time() >= $this->start_time + $this->timeout);            
    }
    
    /**
     * 
     * @return void
     */
    function checkWriteRights()
    {
        return \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE);
    }


    /**
     * Обновляет продукты
     * 
     * @return void
     */
    private function importProduct($reader)
    {
        $offer_xml = new \SimpleXMLElement( $reader->readOuterXML() );
        $xml_id = self::YML_ID_PREFIX.strval($offer_xml->attributes()->id);
        $product = new Orm\Product();
        $product['no_update_dir_counter'] = true;
        
        $strr = array("\n", " ");
        // Импортируем цены в таблицу product_x_cost
        $excost_array = array();
        if(isset($offer_xml->price)){
            if ($offer_xml->currencyId){
                $coc = $this->storage['list']['currencies'][str_replace($strr, "", (string)$offer_xml->currencyId)];
            }else{
                static $currencyCache;
                if (empty($currencyCache)){
                    $curapi = new \Catalog\Model\CurrencyApi();
                    $DC = $curapi->getDefaultCurrency();
                    $currencyCache = $DC->id;
                    $coc = $currencyCache;       //cost_original_currency
                }else{
                    $coc = $currencyCache;
                }
            }
            $excost_array[ $this->getCostId() ] = array(
                'cost_original_val'         => (string) $offer_xml->price,
                'cost_original_currency'    => $coc
            );
            if($offer_xml->oldprice && $this->getOldCostId() ) {
                $excost_array[ $this->getOldCostId() ] = array(
                    'cost_original_val'         => (string) $offer_xml->oldprice,
                    'cost_original_currency'    => $coc
                );
            }
           $product['excost'] = $excost_array;
        }

        $dir_xml_id = self::YML_ID_PREFIX.(int)$offer_xml->categoryId;
        
        //Если товар не связан с категорией, создаем для него категорию
        if ((int)$offer_xml->categoryId == 0 && !isset($this->storage['list']['categories'][$dir_xml_id])) {
            $dir = new Orm\Dir();
            $dir['name'] = t('Без категории');
            $dir['parent'] = 0;
            $dir['xml_id'] = $dir_xml_id;
            $dir->insert();
            $this->storage['list']['categories'][$dir_xml_id] = $dir['id'];
        }        
        if (isset($this->storage['list']['categories'][$dir_xml_id])){
         $dir_id = $this->storage['list']['categories'][$dir_xml_id];
        }else{
            $dir_id =  $this->config['yuml_import_setting'];
        }
        //Добавление бренда
        if((string) $offer_xml->vendor) {
            //Создается бренд, если его не существует
            $vendor_title = (string) $offer_xml->vendor;
            if( !isset($this->storage['list']['brands'][$vendor_title]) ) {
                $vendor = new \Catalog\Model\Orm\Brand();
                $vendor['title'] = $vendor_title;
                $vendor['site_id'] = $this->site_id;
                $vendor->insert();
                
                $this->storage['list']['brands'][$vendor_title] = $vendor['id'];
            }
            $product['brand_id'] = $this->storage['list']['brands'][$vendor_title];
        }
        
        $product['xml_id']            = $xml_id;
        $product['public']            = (string) $offer_xml->attributes()->available == 'false' ? 0 : 1;
        if ((string) $offer_xml->name){
            $product['title']         = (string) $offer_xml->name;
        }elseif ((string) $offer_xml->model){
            $product['title']         = (string) $offer_xml->model;
        }
        $product['description']       = (string) $offer_xml->description;
        $product['xdir']              = array($dir_id);
        $product['maindir']           = (int) $dir_id;
        $product['barcode']           = (string) $offer_xml->attributes()->vendorCode;
        $product['site_id']           = $this->site_id;
        $product['processed']         = 1;
        $uniq_postfix = hexdec(substr(md5($xml_id), 0, 4));
        $product['alias'] = \RS\Helper\Transliteration::str2url($product['title'], true, 140)."-".$uniq_postfix;
        
        $on_duplicate_update_fields = array('xml_id', 'title', 'description', 'short_description', 'public', 'maindir', 'barcode','processed');
        $on_duplicate_update_fields = array_diff($on_duplicate_update_fields, (array)$this->config->dont_update_fields);
        $product->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));    
        $this->updateProductParams($offer_xml, $product['id']);
        
        return $product;
    }


    /**
     * Обновляет характеристики продуктов
     * 
     * @param type $xml_id
     * @param type $product_id
     * @return void
     */
    private function updateProductParams(\SimpleXMLElement $offer_xml, $product_id)
    {
        //Удаляем свойства продукта
        \RS\Orm\Request::make()
            ->delete()
            ->from(new \Catalog\Model\Orm\Property\Link)
            ->where(array(
                'site_id'       => $this->site_id,
                'product_id'    => $product_id,
            ))
            ->where('xml_id LIKE \''.self::YML_ID_PREFIX.'%\'')
            ->exec();

        foreach($offer_xml->param as $param){
            $xml_id = $this->genXmlId((string) $param->attributes()->name);
            if ($checkproperty = \Catalog\Model\Orm\Property\Item::loadByWhere(array(
             'xml_id' => $xml_id,
             'site_id' => \RS\Site\Manager::getSiteId()))){
                 if ($checkproperty->type == 'list') {
                     if ($itemvalue = \Catalog\Model\Orm\Property\Itemvalue::loadByWhere(array(
                    'prop_id' => $checkproperty->id,
                    'site_id' => $this->site_id,
                    'value' => (string)$param[0]))){
                         $this->newPLink($checkproperty['id'],'val_list_id',$itemvalue['id'],$product_id,$this->genXmlId($xml_id.(string) $param[0]));
                    }else{
                        $itemvalue = new \Catalog\Model\Orm\Property\Itemvalue();
                        $itemvalue['prop_id'] = $checkproperty->id;
                        $itemvalue['value'] = (string)$param[0];
                        $itemvalue['site_id'] = $this->site_id;
                        $itemvalue->insert();
                        $this->newPLink($checkproperty['id'],'val_list_id',$itemvalue['id'],$product_id,$this->genXmlId($xml_id.(string) $param[0]));
                    }
                 }else{
                    $product_property= $this->newPItem('string',(string) $param->attributes()->name,$xml_id);
                    $this->newPLink($product_property['id'],'val_str',(string) $param[0],$product_id,$this->genXmlId($xml_id.(string) $param[0]));
                 }
             }else{
             $product_property= $this->newPItem('string',(string) $param->attributes()->name,$xml_id);
             $this->newPLink($product_property['id'],'val_str',(string) $param[0],$product_id,$this->genXmlId($xml_id.(string) $param[0]));
             }
        }
         //Сохраняются свойства из тегов не в property
        foreach($offer_xml as $node){
            if(!($node_name = $this->getParamsField($node->getName())) || empty($node[0])) continue;
            
            $xml_id = $this->genXmlId($node_name);
            if($node->getName() == 'dataTour' && is_array($node[0])){
                $node_val = join(', ', $node[0]);
            }else{
                $node_val = ((string) $node[0] == 'true')?t('да'):((string) $node[0] == 'false')?t('нет'):(string) $node[0];
            }
            $product_property= $this->newPItem('string',$node_name,$xml_id);
            $this->newPLink($product_property['id'],'val_str',$node_val,$product_id,$this->genXmlId($xml_id.$node_val));
        }
    }
    /**
     * Создает характеристику
     *
     * @param string $type тип характеристики
     * @param string $title название характеристики
     * @param string $xml_id xml идентификатор характеристики
     * @return \Catalog\Model\Orm\Property\Item объект характеристики
     */
    function newPItem($type,$title,$xml_id){
        $product_property = new \Catalog\Model\Orm\Property\Item();
        $product_property['site_id']      = $this->site_id;
        $product_property['type']         = $type;
        $product_property['title']        = $title;
        $product_property['xml_id']       = $xml_id;
        $product_property->insert(false, array('title', 'type'), array('xml_id', 'site_id'));
        return $product_property;
    }
    /**
     * Создает связь характеристики с товаром
     *
     * @param string $pp_id id характеристики
     * @param string $val_type тип значения характеристики
     * @param string $val значение или id значения(если списковая)
     * @param string $p_id id товара
     * @param string $xml_id xml идентификатор
     * @return  \Catalog\Model\Orm\Property\Link объект связи характеристики с товаром
     */
    function newPLink($pp_id,$val_type,$val,$p_id,$xml_id){
        $product_property_link = new \Catalog\Model\Orm\Property\Link();
        $product_property_link['site_id']     = $this->site_id;
        $product_property_link['prop_id']     = $pp_id;
        $product_property_link['product_id']  = $p_id;
        $product_property_link[$val_type]     = $val;
        $product_property_link['xml_id']      = $xml_id;
        $product_property_link->insert(false, array($val_type), array('xml_id', 'site_id', 'product_id'));
        return $product_property_link;
    }


    /**
     * Возвращает список полей товара, обновление которых можно отключить из настроек модуля
     *
     * @param bool $exclude_exceptions Исключая поля, указанные как "не обновлять" в настройках модуля
     */
    static public function getUpdatableProductFields($exclude_exceptions = false)
    {
        $fields = array();
        $fields['title']             = t('Наименование');
        $fields['barcode']           = t('Артикул');
        $fields['description']       = t('Описание');
        $fields['maindir']           = t('Связь с категорией');

        if($exclude_exceptions){
            $dont_update_fields = (array)\RS\Config\Loader::byModule('catalog')->dont_update_fields;
            return array_diff_key($fields, array_flip($dont_update_fields));
        }

        return $fields;
    }



    /**
     * Генерирует xml_id
     * 
     * @param type $str
     * @return void
     */
    public function genXmlId($str)
    {
        return self::YML_ID_PREFIX.crc32((string) $str);
    }
    
    /**
     * Очищает временную директорию
     * 
     * @return void
     */
    function cleanTemporaryDir()
    {
        @unlink($this->yml_folder.'/'.$this->yml_name);
        @unlink($this->yml_folder.'/'.$this->tmp_data_file);
    }

    /**
    * Возвращает сопоставленную тегу характеристику
    * 
    * @param string $param - наименование тега
    * @return string | false
    */
    private function getParamsField($param)
    {
        return (isset($this->params_fields[$param])) ? t($this->params_fields[$param]) : false;
    }
    
    /**
    * Сохраняет строку в log файл
    * 
    * @param string $message сообщение
    * @param bool $append - Если true, то добавит строку в конец файла, иначе пересоздаст файл
    */
    private function writeLog($message, $append = true)
    {
        file_put_contents($this->log_file, date('Y-m-d H:i:s ').$message."\n", $append ? FILE_APPEND : null);
    }
    
    /**
    * Возвращает относительную ссылку на лог файл
    * @return string
    */
    function getLogLink()
    {
        return $this->log_file_rel;
    }
    
    /**
    * Возвращает массив со статистическими данными об импорте
    * @return array
    */
    function getStatistic()
    {

        return $this->storage['statistic'];

    }

    function getXmlIds() {
        $this->xmlIds = \RS\Orm\Request::make()
        ->from(new \Catalog\Model\Orm\Dir())
        ->where(array(
            'site_id' => \RS\Site\Manager::getSiteId()
        ))->exec()->fetchSelected('xml_id', 'id');        
    }


    function afterimportproducts()
    {
        $config = $this->config;
        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Ничего не делать"
        if($config->catalog_element_action == \Exchange\Config\File::ACTION_NOTHING){

            return true;
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Обнулять остаток"
        if($config->catalog_element_action == \Catalog\Config\File::ACTION_CLEAR_STOCKS){
            $countpcs = 0;
            // Получаем id товаров, не участвовавших в импорте
            $ids = \RS\Orm\Request::make()
                ->select('id')
                ->from(new \Catalog\Model\Orm\Product())
                ->where(array(
                    'site_id'   => \RS\Site\Manager::getSiteId(),
                    'processed' => null,
                ))
                ->exec()
                ->fetchSelected(null, 'id');

            // Если есть товары, не участвовавшие в импорте - удалим линки остатков, кэш остатков у комплектаций и товаров
            if (!empty($ids)) {
                \RS\Orm\Request::make()
                    ->delete()
                    ->from(new \Catalog\Model\Orm\Xstock())
                    ->whereIn('product_id', $ids)
                    ->exec();
                \RS\Orm\Request::make()
                    ->update(new \Catalog\Model\Orm\Offer())
                    ->set(array('num' => 0))
                    ->whereIn('product_id', $ids)
                    ->exec();
                \RS\Orm\Request::make()
                    ->update(new \Catalog\Model\Orm\Product())
                    ->set(array('num' => 0))
                    ->whereIn('id', $ids)
                    ->exec();
            }

            $countpcs = $countpcs + count($ids);
            $this->storage['statistic']['cs_products'] = $countpcs;
            $this->flushLocalStorage();
            return true;
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Удалять"
        if($config->catalog_element_action == \Catalog\Config\File::ACTION_REMOVE)
        {
            $countpr = 0;
            $apiCatalog = new \Catalog\Model\Api();

            while(true)
            {
                $ids = \RS\Orm\Request::make()
                    ->select('id')
                    ->from(new \Catalog\Model\Orm\Product())
                    ->where(array(
                        'site_id'   => \RS\Site\Manager::getSiteId(),
                        'processed' => null,
                    ))
                    ->limit(self::DELETE_LIMIT)
                    ->exec()
                    ->fetchSelected(null, 'id');

                // Если не осталось больше товаров для удаления
                if(empty($ids)){
                    return true;
                }
                $apiCatalog->multiDelete($ids);
                $countpr = $countpr + count($ids);
                $this->storage['statistic']['removed_products'] = $countpr;
                $this->flushLocalStorage();

            }
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Деактивировать"
        if($config->catalog_element_action == \Catalog\Config\File::ACTION_DEACTIVATE)
        {

            // Скрываем товары
            $affected = \RS\Orm\Request::make()
                ->update(new \Catalog\Model\Orm\Product())
                ->set(array('public' => 0))
                ->where(array(
                    'site_id'   => \RS\Site\Manager::getSiteId(),
                    'processed' => null,
                ))
                ->exec()->affectedRows();
            $this->storage['statistic']['deactivated_products'] = $affected;
            $this->flushLocalStorage();
            return true;
        }
    }

    function afterimportdirs()
    {





        $config = $this->config;

        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Ничего не делать"
        if($config->catalog_section_action == \Catalog\Config\File::ACTION_NOTHING){

            return true;
        }

        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Удалять"
        if($config->catalog_section_action == \Catalog\Config\File::ACTION_REMOVE)
        {
            $countdirr = 0;
            while(true)
            {

                $dir = \Catalog\Model\Orm\Dir::loadByWhere(array(
                    'site_id'   => \RS\Site\Manager::getSiteId(),
                    'is_spec_dir' => 'N',                           // Удалению подлежат только категориии, не являющиеся "спец-категориями"
                    'processed' => null,
                ));

                // Если не осталось больше объектов для удаления
                if(!$dir->id){

                    return true;
                }


                $dir->delete();
                $countdirr = $countdirr +1;
                $this->storage['statistic']['removed_categories'] = $countdirr;
                $this->flushLocalStorage();

            }
        }

        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Деактивировать"
        if($config->catalog_section_action == \Catalog\Config\File::ACTION_DEACTIVATE)
        {


            // Скрываем категории
            $affected = \RS\Orm\Request::make()
                ->update(new \Catalog\Model\Orm\Dir())
                ->set(array('public' => 0))
                ->where(array(
                    'site_id'   => \RS\Site\Manager::getSiteId(),
                    'processed' => null,
                ))
                ->exec()->affectedRows();

            $this->storage['statistic']['deactivated_categories'] = $affected;
            $this->flushLocalStorage();
            return true;
        }
    }
}