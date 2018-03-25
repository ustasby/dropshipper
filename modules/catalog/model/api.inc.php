<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model;

use Catalog\Model\Orm\Property\Item;
use \Catalog\Model\Orm\Xdir,
    \Catalog\Model\Orm\Xcost;

/**
 * Класс содержит API функции для работы с товарами
 * @ingroup Catalog
 */
class Api extends \RS\Module\AbstractModel\EntityList
{
    const
        WEIGHT_UNIT_G  = 'g',
        WEIGHT_UNIT_KG = 'kg',
        WEIGHT_UNIT_T  = 't',
        WEIGHT_UNIT_LB = 'lb';
    
    protected
        $work_list,
        $work_list_ids;
    
    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\Product,
        array(
            'multisite' => true,
            'aliasField' => 'alias',
            'defaultOrder' => 'dateof DESC'
        ));
    }
    
    /**
    * Добавляет к запросу выборки условия для присоединения цен
    * 
    * @param Orm\Typecost[] $cost_types - типы цен
    * @return void
    */
    function addCostQuery(array $cost_types)
    {
        foreach($cost_types as $cost_type) {
            $alias = 'XC'.$cost_type['id'];
            $this->queryObj()
                ->select("$alias.cost_val as cost_".$cost_type['id'])
                ->leftjoin(new \Catalog\Model\Orm\Xcost(), "$alias.product_id=A.id AND $alias.cost_id='{$cost_type['id']}'", $alias);
        }
    }

    /**
     * Возвращает список товаров для таблицы в административной части
     * 
     * @param integer $page - номер страницы
     * @param integer $page_size - количество элементов на страницу
     * @param string $order - сортировка
     * @return array
     */
    public function getTableList($page = null, $page_size = null, $order = null)
    {
        $list = parent::getList($page, $page_size, $order);
        $list = $this->addProductsPhotos($list);
        return $list;
    }

    /**
     * Добавляет ко всем товарам характеристики
     * 
     * @param array $products
     * @return array
     */
    public function addProductsProperty($products)
    {
        $api = new PropertyApi();
        $proplist = $api->getProductProperty($products);

        if(isset($products) && is_array($products))   foreach ($products as $item) {
            if (isset($proplist[$item[$this->id_field]])) {
                $item['properties'] = $proplist[$item[$this->id_field]];
            } else {
                $item['properties'] = array();
            }
        }
        return $products;
    }

    /**
     * Загружает свойства cost и xcost в объекты товаров, с учетом текущей группы пользователя, для списка товаров
     * 
     * @param Orm\Product[] - список товаров
     * @return Orm\Product[]
     */
    public function addProductsCost($products)
    {
        $tmp = array();
        foreach ($products as $item) {
            $tmp[] = $item[$this->id_field];
        }
        $products_id = $tmp;

        if (empty($products_id))
            return $products;

        $res = \RS\Orm\Request::make()
                ->from(new Xcost())
                ->whereIn('product_id', $products_id)
                ->exec();

        $products_xcost = array();
        $products_excost = array();
        while ($row = $res->fetchRow()) {
            $products_xcost[$row['product_id']][$row['cost_id']] = $row['cost_val'];
            $products_excost[$row['product_id']][$row['cost_id']] = $row;
        }

        foreach ($products as $product) {
            /**
             * @var \Catalog\Model\Orm\Product $product
             */
            if (isset($products_xcost[$product[$this->id_field]])) {
                $product['xcost'] = $products_xcost[$product[$this->id_field]];
                $product['excost'] = $products_excost[$product[$this->id_field]];
            }
            $product->calculateUserCost();
        }
        return $products;
    }

    /**
     * Загружает информацию о привязанных файлах к группе товаров (в свойство files)
     * @param Orm\Product[] - список товаров
     * @return Orm\Product[]
     */
    public function addProductsFiles($products)
    {
        $tmp = array();
        foreach ($products as $item) {
            $tmp[] = $item[$this->id_field];
        }
        $products_id = $tmp;

        $product_files = \RS\Orm\Request::make()
                        ->from(new \Files\Model\Orm\Linkfiles())
                        ->where(array(
                            'type' => 'catalog'
                        ))
                        ->whereIn('linkid', $products_id)
                        ->exec()->fetchSelected('linkid', 'uniq', true);

        foreach ($products as $product) {
            if (isset($product_files[$product[$this->id_field]])) {
                $product['files'] = $product_files[$product[$this->id_field]];
            }
        }
        return $products;
    }

    /**
     * Загружает ссылки на фотографии к группе товаров (в свойство images)
     * 
     * @param Orm\Product[] - список товаров
     * @return Orm\Product[]
     */
    public function addProductsPhotos($products)
    {
        $products_id = array();
        foreach ($products as $item) {
            $products_id[] = $item[$this->id_field];
        }

        if (empty($products_id))
            return $products;

        $product_photos = \RS\Orm\Request::make()
                ->from(new \Photo\Model\Orm\Image())
                ->where(array(
                    'type' => 'catalog'
                ))
                ->whereIn('linkid', $products_id)
                ->orderby('sortn')
                ->objects(null, 'linkid', true);

        foreach ($products as $product) {
            if (isset($product_photos[$product[$this->id_field]])) {
                $product['images'] = $product_photos[$product[$this->id_field]];
            } else {
                $product['images'] = array(new \Photo\Model\Stub());
            }
        }
        return $products;
    }
    
    /**
    * Возвращает сгенерированный артикул
    *
    * @return string
    */
    function genereteBarcode(){
       $config = \RS\Config\Loader::byModule($this);
       
       $barcode_mask = $config['auto_barcode'];
       //Запросим максимальный элемент в таблице с товарами
       $q = \RS\Orm\Request::make()
            ->select('max(id) as id')
            ->from(new \Catalog\Model\Orm\Product())
            ->exec();
            
       $row    = $q->fetchRow();
       $max_id = $row['id']+1; //Наш максимальный id
       
       $barcode = preg_replace_callback('/\{(.*?)\}/si',function ($matches) use ($max_id){
          $mask      = explode("|",$matches[1]);
          $max_id = sprintf('%0'.$mask[1].'d', $max_id);
          
          return  $max_id;
           
       }, $barcode_mask);
       
       return $barcode; 
    }

    /**
    *  Устанавливает фильтр для последующей выборки элементов
    * 
    * @param string | array $key - имя поля (соответствует имени поля в БД) или массив для установки группового фильтра
    *
    * Пример применения группового фильтра:
    * array(
    *   'title' => 'Название',                     // AND title = 'Название'
    *   '|title:%like%' => 'Текст'                 // OR title LIKE '%Текст%'
    *   '&title:like%' => 'Текст'                  // AND title LIKE 'Текст%'
    *   'years:>' => 18,                           // AND years > 18
    *   'years:<' => 21,                           // AND years < 21
    *   ' years:>' => 30,                          // AND years > 30  #пробелы по краям вырезаются
    *   ' years:<' => 40,                          // AND years < 40  #пробелы по краям вырезаются
    *   'id:in' => '12,23,45,67,34',               // AND id IN (12,23,45,67,34)
    *   '|id:notin' => '44,33,23'                  // OR id NOT IN (44,33,23)
    * 
    *   array(                                     // AND (
    *       'name' => 'Артем',                     // name = 'Артем'
    *       '|name' => 'Олег'                      // OR name = 'Олег'
    *   ),                                         // )
    *   
    *   '|' => array(                              // OR (
    *       'surname' => 'Петров'                  // surname = 'Петров'
    *       '|surname' => 'Иванов'                 // OR surname = 'Иванов'
    *   )                                          // )
    * )
    * Общая маска ключей массива: 
    * [пробелы][&|]ИМЯ ПОЛЯ[:ТИП ФИЛЬТРА]
    * 
    * @param mixed $value - значение 
    * @param string $type - =,<,>, in, notin, fulltext, %like%, like%, %like тип соответствия поля значению.
    * @param string $prefix условие связки с предыдущими условиями (AND/OR/...)
    * @param array $options
    *
    * @return Api|\RS\Module\AbstractModel\EntityList
    */
    public function setFilter($key, $value = null, $type = '=', $prefix = 'AND', array $options = array())
    {
        if ($key == 'dir') {
            $q = $this->queryObj();
            if (!$q->issetTable(new Xdir())) {
                $q->join(new Xdir(), "{$this->def_table_alias}.id = X.product_id", 'X');
            }

            parent::setFilter('X.dir_id', $value, $type, 'AND');
            return $this;
        }

        return parent::setFilter($key, $value, $type, $prefix, $options);
    }

    /**
     * Возвращает верное количество товаров, 
     * которое вернет getList если выбираются товары из нескольких директорий одновременно
     */
    public function getMultiDirCount()
    {
        $q = clone $this->queryObj();
        $q->select = 'COUNT(DISTINCT A.id) cnt';
        return
                        $q->groupby(false)
                        ->orderby(false)
                        ->exec()
                        ->getOneField('cnt', 0);
    }

    /**
     * Устанавливает сортировку товаров
     */
    public function setSortOrder($field, $direction)
    {
        $q = $this->queryObj();
        $order_prefix = '';
        if (\RS\Config\Loader::byModule($this)->list_order_instok_first) {
            $order_prefix .= 'A.num > 0 desc, ';
        }
        if ($field == 'cost' || $field == 'A.cost') {
            //Подключаем таблицу цен, если таковая не подключена
            if (!$q->issetTable(new Xcost())) {
                $current_cost_type = CostApi::getUserCost(); //Текущий тип цен
                $current_cost_type = Costapi::getInstance()->getManualType($current_cost_type);
                $q->leftjoin(new Xcost(), "A.id = XC.product_id AND XC.cost_id='{$current_cost_type}'", 'XC');
            }
            $q->orderby($order_prefix . 'XC.cost_val ' . $direction);
        }  else {         
            $this->setOrder($order_prefix . $field . ' ' . $direction);
        }
    }

    /**
     * Возвращает массив текущих переданных базовых фильтров из массива $_REQUEST
     *
     * @return array
     */
    public function getBaseFilters()
    {
        return \RS\Http\Request::commonInstance()->request('bfilter', TYPE_ARRAY);
    }

    /**
     * Применяет "базовые" фильтры, т.е. фильтры, которые не зависят от таблиц характеристик товара.
     *
     * @param null|array $bfilters - массив базовых фильтров
     * @return bool
     */
    public function applyBaseFilters($bfilters = null)
    {
        if ($bfilters === null) {
            $bfilters = $this->getBaseFilters();
        }
        foreach ($bfilters as $key => $filter) {
            //Чистим неактивные фильтры
            if ($filter === '' || (is_array($filter) && isset($filter['from']) && empty($filter['from']) && empty($filter['to']))) {
                unset($bfilters[$key]);
            }
        }

        $keys = array_keys($bfilters);
        if (empty($keys))
            return false;

        foreach ($keys as $n => $key) {
            //Вызываем метод на установку фильтров
            $func = $key . 'Filter';
            if (method_exists($this, $func))
                $this->$func($bfilters[$key]);
        }
        return true;
    }

    /**
     * Устанавливает фильтр по наличию
     *
     * @param string $filter - строка с типом фильтра ("", "1", "0")
     */
    protected function isNumFilter($filter)
    {
        if ($filter != "") {
            if ($filter){ //Если надо только в наличии
               $q = $this->queryObj();
               $q->where('num>0');
            }else{ //Если надо не в наличии
               $q = $this->queryObj();
               $q->where('num<=0');
            }
        }
    }


    /**
     * Устанавливает фильтр по бренду
     *
     * @param string|array $filter - бренд или массив брендов
     */
    protected function brandFilter($filter)
    {
        $filter = (array)$filter;
        if ($filter) {
            $q = $this->queryObj();
            $q->whereIn('brand_id', $filter);
        }
    }

    /**
     * Устанавливает фильтр по цене с учетом цен текущего пользователя
     *
     * @param array $filter - массив со сведениями о переданном фильтре по цене (ключи to, from)
     */
    protected function costFilter($filter)
    {
        $q = $this->queryObj();

        $current_cost_type = CostApi::getUserCost(); //Текущий тип цен
        $current_cost_type = CostApi::getInstance()->getManualType($current_cost_type);

        $q->leftjoin(new Xcost(), "A.id = XC.product_id AND XC.cost_id='{$current_cost_type}'", 'XC')
                ->orderby('cost_val');

        //Корректируем цены для фильтра, если цена пользователя - автоматическая
        $costapi = CostApi::getInstance();
        $currencyApi = new CurrencyApi();
        if (!empty($filter['from'])) {
            $cost_from = $costapi->correctCost($filter['from']);
            $cost_from = floor($currencyApi->convertToBase($cost_from));
            $q->where("XC.cost_val>='#cost_from'", array('cost_from' => $cost_from));
        }
        if (!empty($filter['to'])) {
            $cost_to = $costapi->correctCost($filter['to']);
            $cost_to = ceil($currencyApi->convertToBase($cost_to));
            $q->where("XC.cost_val<='#cost_to'", array('cost_to' => $cost_to));
        }
    }

    /**
     * Загружает товарам свойства xdir, xspec, в которых содержится принадлежность категориям
     * 
     * @param Orm\Product[] $products - массив товаров
     * @return Orm\Product[]
     */
    public function addProductsDirs($products)
    {
        $products_id = array();
        foreach ($products as $item) {
            $products_id[] = $item[$this->id_field];
        }
        if (empty($products_id))
            return $products;

        $dirapi = DirApi::getInstance();
        $dirapi->setFilter('is_spec_dir', 'Y');
        $spec_dirs = $dirapi->getAssocList('id');

        $res = \RS\Orm\Request::make()->select('*')
                        ->from(new Xdir())
                        ->whereIn('product_id', $products_id)
                        ->exec()->fetchAll();
        if (empty($res))
            return $products;

        $xdir = array();
        $xspec = array();
        foreach ($res as $cats) {
            $dir_id = $cats['dir_id'];
            $products_id = $cats['product_id'];

            $xdir[$products_id][] = $dir_id;
            if (isset($spec_dirs[$dir_id])) {
                $xspec[$products_id][] = $dir_id;
            }
        }

        foreach ($products as $item) {
            $item['xdir'] = isset($xdir[$item['id']]) ? $xdir[$item['id']] : array();
            $item['xspec'] = isset($xspec[$item['id']]) ? $xspec[$item['id']] : array();
        }

        return $products;
    }

    /**
     * Загружает списку товаров комплектации
     * 
     * @param Orm\Product[] $products - массив товаров
     * @return Orm\Product[]
     */
    public function addProductsOffers($products)
    {
        $products_id = array();
        foreach ($products as $item) {
            $products_id[] = $item[$this->id_field];
        }

        if (empty($products_id))
            return $products;

        //Загружаем комплектации для списка товаров
        $offers = \RS\Orm\Request::make()
                        ->from(new Orm\Offer())
                        ->whereIn('product_id', $products_id)
                        ->orderby('product_id, sortn')
                        ->objects(null, 'product_id', true);

        //Раскладываем по товарам
        foreach ($products as $item) {
            $one_offers = array(
                'use' => 1,
                'items' => array()
            );
            if (isset($offers[$item['id']])) {
                $one_offers['items'] = $offers[$item['id']];
            } else {
                $one_offers['use'] = 0;
            }
            $item['offers'] = $one_offers;
        }
        return $products;
    }
    
    /**
     * Загружает списку товаров уровни многомерных комплектаций
     * 
     * @param Orm\Product[] $products массив товаров
     * @return Orm\Product[]
     */
    public function addProductsMultiOffers($products)
    {
        $products_id = array();
        foreach ($products as $item) {
            $products_id[] = $item[$this->id_field];
        }

        if (empty($products_id))
            return $products;

        $levels_res = \RS\Orm\Request::make()
                        ->select('I.title as prop_title, Level.*')
                        ->from(new Orm\MultiOfferLevel(),'Level')
                        ->join(new Orm\Property\Item(),'Level.prop_id = I.id','I')
                        ->where(array(
                            'I.site_id'        => \RS\Site\Manager::getSiteId()
                        )) 
                        ->whereIn('I.type', Orm\Property\Item::getListTypes())
                        ->whereIn('Level.product_id', $products_id)
                        ->orderby('product_id, Level.sortn')
                        ->exec();
                        
        $levels = array();
        $props_id = array();
        while($row = $levels_res->fetchRow()) {
            $levels[ $row['product_id'] ][ $row['prop_id'] ] = $row + array('values' => array());
            $props_id[ $row['prop_id'] ] = $row['prop_id'];
        }
        
        if ($props_id) {
            $values_res = \RS\Orm\Request::make()
                    ->select('V.*, V.value as val_str, L.product_id') //Для совместимости
                    ->from(new Orm\Property\ItemValue(), 'V')
                    ->join(new Orm\Property\Link(), 'L.val_list_id = V.id', 'L')
                    ->whereIn('L.product_id', $products_id)
                    ->whereIn('L.prop_id', $props_id)
                    ->where("V.value != ''")
                    ->orderby('V.sortn')
                    ->exec();
                    
            while($row = $values_res->fetchRow()) {
                if ($row['val_str'] != '') {
                    $link = new Orm\Property\ItemValue();
                    $link->getFromArray($row);
                    if (isset($levels[ $row['product_id'] ][ $row['prop_id'] ])) {
                        $levels[ $row['product_id'] ][ $row['prop_id'] ]['values'][] = $link;
                    }
                }
            }
        }
                  
        foreach ($products as $item) {
            $product_multioffers = array('use' => false);
            if (isset($levels[ $item['id'] ])) {
                $product_multioffers['use'] = true;
                $product_multioffers['levels'] = $levels[ $item['id'] ];
            }
            $item['multioffers'] = $product_multioffers;
        }
        
        return $products;
    }
    
    /**
    * Загружает списку товаров флаг, находится ли товар в избранном
    * 
    * @param Orm\Product[] $products массив товаров
    * @return Orm\Product[]
    */
    public function addProductsFavorite($products)
    {
        if ($products) {
            $data = \RS\Orm\Request::make()
                        ->from(new Orm\Favorite(), 'F')
                        ->select("product_id")
                        ->where("(F.guest_id = '#guest' OR F.user_id = '#user')",
                            array(
                                'guest' => FavoriteApi::getGuestId(),
                                'user' => FavoriteApi::getUserId(),
                            ))
                        ->exec()->fetchSelected('product_id', 'product_id');
                        
            foreach ($products as $item) {
                $item['isInFavorite'] = isset($data[$item['id']]);
            }
        }
        
        return $products;
    }
    
    /**
    * Загружает списку товаров информацию о том используются ли комплектации и многомерные 
    * комплектации без загрузки подробных сведений. Данный метод удобно использовать на странице со списком товаров
    * 
    * @param array $products список товаров
    * @return array
    */
    public function addProductsMultiOffersInfo($products)
    {
        $products_id = array();
        if (!empty($products)){
             foreach ($products as $item) {
                $products_id[] = $item[$this->id_field];
            }
        }
        if (!$products_id) {
            return $products;
        }
                    
        $use_multioffers = \RS\Orm\Request::make()
            ->select('DISTINCT product_id')
            ->from(new Orm\MultiOfferLevel())
            ->whereIn('product_id', $products_id)
            ->exec()->fetchSelected('product_id', 'product_id');
        
        $use_offers = \RS\Orm\Request::make()
            ->select('product_id, COUNT(*) as cnt')
            ->from(new Orm\Offer())
            ->whereIn('product_id', $products_id)
            ->groupby('product_id')
            ->exec()->fetchSelected('product_id', 'cnt');
        
        foreach ($products as $item) {
            $item->setFastMarkMultiOffersUse( isset($use_multioffers[ $item['id'] ]) );
            $item->setFastMarkOffersUse( isset($use_offers[ $item['id'] ]) && $use_offers[ $item['id'] ]>1 );
        }
        return $products;
    }
    

    /**
     * Возвращает группы, в которых состоят товары (включая родителей групп)
     * 
     * @param Orm\Product[] $products_id - массив товаров
     * @param bool $add_root - добавлять корневую категорию
     * @param bool $is_products - добавлять в запрос идентификаторы из переданных товаров
     * @return array|false
     */
    public function getProductsDirs($products_id, $add_root = false, $is_products = true)
    {
        if ($is_products) {
            $tmp = array();
            foreach ($products_id as $item) {
                $tmp[] = isset($item[$this->id_field]) ? $item[$this->id_field] : $item;
            }
            $products_id = $tmp;
        }

        if (empty($products_id)){
            return false;
        }

        $list = \RS\Orm\Request::make()
                ->from(new Xdir())
                ->whereIn('product_id', $products_id)
                ->exec();
        
        $dir_parents = \RS\Orm\Request::make()
            ->from(new Orm\Dir())
            ->exec()->fetchSelected('id', 'parent');

        $result = array();
        while ($row = $list->fetchRow()) {
            $dir_id = $row["dir_id"];
            while ($dir_id != 0) {
                if (!isset($result[$row["product_id"]][$dir_id])) {
                    $result[$row["product_id"]][$dir_id] = $dir_id;
                    $dir_id = $dir_parents[$dir_id];
                } else {
                    break;
                }
            }
        }
        // если нужно - добавляем корневую категорию
        if ($add_root) {
            foreach ($result as $key=>$item) {
                $result[$key][0] = '0';
            }
        }
        return $result;
    }

    /**
     * Функция быстрого группового удаления товаров по их идентификаторам
     *
     * @param array $ids - массив id товаров
     * @param integer $dir - если >0, то будет удалено только из категории $dir
     *
     * @return boolean
     */
    function multiDelete($ids, $dir = 0)
    {
        //Проверяем права на запись для модуля
        if ($this->noWriteRights()) return false;
        
        $event_result = \RS\Event\Manager::fire('orm.beforemultidelete.catalog-product', array(
            'ids' => $ids,
            'api' => $this
        ));
        
        if ($event_result->getEvent()->isStopped()){ //Если нужно оставить из стороннего модуля
            return false;
        }

        if (empty($ids)){ //Если не переданы идентификаотры
            return false;
        }

        //Удаляем связи
        $q = \RS\Orm\Request::make()
                ->delete()
                ->from(new Xdir())
                ->whereIn('product_id', $ids);

        if ($dir > 0) {
            $dir_api = new \Catalog\Model\Dirapi();
            $child_ids = $dir_api->getChildsId($dir);
            $q->whereIn('dir_id', $child_ids);
        }
        $q->exec();

        $del_ids = array_flip($ids);

        if ($dir > 0) {
            //Помечаем на удаление товар, на который нет ссылок
            $res = \RS\Orm\Request::make()
                    ->from(new Xdir())
                    ->whereIn("product_id", $ids)
                    ->exec();

            while ($row = $res->fetchRow()) {
                if (isset($del_ids[$row['product_id']])) {
                    unset($del_ids[$row['product_id']]);
                }
            }
        }

        if (!empty($del_ids)) {
            $del_ids = array_keys($del_ids);
            $del_ids_in = implode(',', \RS\Helper\Tools::arrayQuote($del_ids));
            //Определяем связанные фотографии
            $photo_api = new \Photo\Model\PhotoApi();

            $photo_ids = \RS\Orm\Request::make()
                            ->select('id')->from($photo_api->getElement())
                            ->where(array(
                                'type' => 'catalog'
                            ))
                            ->where("linkid IN ($del_ids_in)")
                            ->exec()->fetchSelected(null, 'id');

            $photo_api->multiDelete($photo_ids); //Удаляем связанные фото
            //Удаляем сами товары
            $product_table = $this->obj_instance->_getTable();
            \RS\Orm\Request::make()
                    ->delete()
                    ->from($this->obj_instance)
                    ->where("id IN($del_ids_in)")
                    ->exec();

            //Удаляем цены
            \RS\Orm\Request::make()
                    ->delete()
                    ->from(new Xcost())
                    ->where("product_id IN($del_ids_in)")
                    ->exec();

            //Удаляем товарные предложения (комплектации)
            \RS\Orm\Request::make()
                    ->delete()
                    ->from(new \Catalog\Model\Orm\Offer())
                    ->where("product_id IN($del_ids_in)")
                    ->exec();
                    
            //Удаляем уровни многомерных комплектаций
            \RS\Orm\Request::make()
                    ->delete()
                    ->from(new \Catalog\Model\Orm\MultiOfferLevel())
                    ->where("product_id IN($del_ids_in)")
                    ->exec();

            //Удаляем характеристики
            \RS\Orm\Request::make()->delete()
                ->from(new \Catalog\Model\Orm\Property\Link())
                ->where("product_id IN($del_ids_in)")
                ->exec();
                
            //Удаляем из поискового индекса
            \RS\Orm\Request::make()->delete()
                ->from(new \Search\Model\Orm\Index())
                ->where("entity_id IN($del_ids_in)")
                ->where(array(
                    'result_class' => get_class($this->obj_instance)
                ))
                ->exec();
            
            //Удаляем остатки на складах
            \RS\Orm\Request::make()->delete()
                ->from(new \Catalog\Model\Orm\Xstock())
                ->where("product_id IN($del_ids_in)")
                ->exec();
        }

        \Catalog\Model\Dirapi::updateCounts(); //Обновляем счетчики у категорий
        //Добавим событие на удалении
        \RS\Event\Manager::fire('orm.multidelete.catalog-product',array(
            'ids' => $del_ids,
            'api' => $this
        ));
        return true;
    }
    
    /**
    * Мульти обновление цен только у товаров
    * 
    * @param array $ids - массив с id товаров цены которых надо изменить
    * @param array $excost - массив сведений об изменении цены
    * @param array $all_prices - массив сведений о прошлых ценах
    * @param boolean $update_price_round - флаг округлять ли цены - !устарел
    * @param integer $update_price_round_value - количество дробных цифр в округлённой цене (округляется вверх) - !устарел
    */
    private function multiUpdateProductPrices( $ids, $excost, $all_prices, $update_price_round = false, $update_price_round_value = 0)
    {
        //Поменяем значения цен у товаров
        foreach ($ids as $id) {
            foreach($excost as $cost_id => $cdata) {
                //Если задано мульти редактирование накрутки цены
                if (isset($cdata['edit_multi'])) { 
                    
                    $product_before_cost = isset($all_prices[$cost_id][$id]) ? $all_prices[$cost_id][$id] : 
                        array(
                            'cost_original_currency' => 0,
                            'cost_original_val' => 0
                        );
                    
                    $cdata['cost_original_currency'] = $product_before_cost['cost_original_currency'];
                    if ($cdata['plus_type']) { //Если в еденицах
                        $delta = $cdata['plus_value'];
                    } else {
                        //Если задано в процентах
                        $delta = $product_before_cost['cost_original_val'] * ($cdata['plus_value'] / 100);
                    }
                    
                    $way = $cdata['way'] ? -1 : 1;
                    $cdata['cost_original_val'] = $product_before_cost['cost_original_val'] + ($way * $delta);
                    
                    $cdata['cost_original_val'] = \Catalog\Model\CostApi::roundCost($cdata['cost_original_val']);
                }

                $cost_item = new Xcost();
                $cost_item->fillData($cost_id, $id, $cdata);
                $cost_item->insert();
             }
        }
        
    }
    
    /**
    * Мульти обновление цен только у комплектаций
    * 
    * @param array $ids - массив с id товаров цены которых надо изменить
    * @param array $excost - массив сведений об изменении цены
    * @param array $all_prices - массив сведений о прошлых ценах
    * @param boolean $update_price_round - флаг округлять ли цены - !устарел
    * @param integer $update_price_round_value - количество дробных цифр в округлённой цене (округляется вверх) - !устарел
    */
    private function multiUpdateProductOffers( $ids, $excost, $all_prices, $update_price_round = false, $update_price_round_value = 0 )
    {
        //Поменяем значения цен у комплектаций, подгрузив значения pricedata у комплектации   
        $pricedata_offers = (array)\RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Offer())
            ->whereIn('product_id',$ids)
            ->where('sortn > 0')
            ->exec()
            ->fetchSelected('id');
        if (empty($pricedata_offers)){
            return;
        }
            
        //Получим валюты в системе
        $currencies = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Currency())
                ->orderby('`default` DESC')
                ->objects(null,'id');
                
        $currency_keys       = array_keys($currencies);
        $currency_default_id = $currency_keys[0]; //id валюты по умолчанию
        
        static 
            $prices; //Все сущесвующие цены в системе

        //Перебираем комплектации
        foreach ($pricedata_offers as $offer_id=>$offer_price_info){
            $product_id = $offer_price_info['product_id'];
            $pricedata  = (array)unserialize($offer_price_info['pricedata']);
            
            //Если установлен флаг "для всех типов цен" и знак установлен в равно, то мы снимем галочку, заново сформировав массив с ценами для этой комплектации
            if (isset($pricedata['oneprice']) && ($pricedata['oneprice']['znak']=="=") && $pricedata['oneprice']['original_value']>0){ 
                if ($prices === null){
                   $prices = \RS\Orm\Request::make()
                                ->from(new \Catalog\Model\Orm\Typecost())
                                ->where(array(
                                    'site_id' => \RS\Site\Manager::getSiteId(),
                                    'type' => 'manual'
                                ))
                                ->objects();
                }
                
                $pricedata['price'] = array();
                //Пересоберём массив цен
                foreach ($prices as $price){
                   $pricedata['price'][$price['id']] = array(
                      'znak' => '=',  
                      'unit' => $pricedata['oneprice']['unit'],  
                      'original_value' => $pricedata['oneprice']['original_value'],  
                      'value' => $pricedata['oneprice']['value'],  
                   );
                }
                unset($pricedata['oneprice']);
            }else if (isset($pricedata['oneprice']) && ($pricedata['oneprice']['znak']=="+")){
                //Если выбрано "Для всех типов цен" у комплектации и знак "+", то пропустим
                continue;
            }else if (isset($pricedata['oneprice']) && ($pricedata['oneprice']['znak']=="=" && $pricedata['oneprice']['original_value']=="")){
                //Если выбрано "Для всех типов цен" у комплектации и знак "=", и значения пустые, то пропустим
                continue;
            }
            
            //Если у нас не образовался массив с ценами по какой-то причине, или он вообще не создавался никогда
            if (!isset($pricedata['price'])){ 
                continue;
            }
            
            foreach($excost as $cost_id => $cdata){
                
                //Если у нас у подсчитываемой цены у комплектации стоит валюты, не как валюта, а процент
                if (isset($pricedata['price'][$cost_id]['unit']) && ($pricedata['price'][$cost_id]['unit']=="%")){
                    continue;
                }
                
                //Если задано мульти редактирование накрутки цены
                if (isset($cdata['edit_multi']) && $cdata['edit_multi']) {  
                    //Если стоит галочка
                    $from_price = $cdata['from_price'];
                    //Если цены от которой считаем не существует у комплектации, добавим запись
                    if (!isset($pricedata['price'][$from_price]['znak'])){
                        $pricedata['price'][$from_price] = array(
                            'znak' => "=",
                            'unit' => $all_prices[$cost_id][$product_id]['cost_original_currency'],
                            'cost_original_currency' => "",
                            'cost_original_val' => "",
                        );
                    }
                    
                    //Если цены для которой считаем не существует
                    if (!isset($pricedata['price'][$cost_id]['znak'])){
                        $pricedata['price'][$cost_id] = array(
                            'znak' => "=",
                            'unit' => $all_prices[$cost_id][$product_id]['cost_original_currency'],
                            'cost_original_currency' => "",
                            'cost_original_val' => "",
                        );
                    }
                    
                     //Если цена существует
                    if(isset($pricedata['price'][$from_price]['original_value'])){
                        //Если цена задана и знак "=" и не процент от суммы
                        if (($pricedata['price'][$cost_id]['znak'] == "=") && ($pricedata['price'][$from_price]['unit'] != "%") && ($pricedata['price'][$from_price]['original_value'] > 0)){
                       
                            //Если цена от которой будем считать имеет знак + и имеет значение больше 0
                            if ($pricedata['price'][$from_price]['znak']=="+"){
                                $currency_id   = $all_prices[$cost_id][$product_id]['cost_original_currency'];
                                $original_cost = $all_prices[$cost_id][$product_id]['cost_original_val']; //Цена в валюте которую установили
                                //Прибавим плюсовые значения
                                if ($pricedata['price'][$from_price]['unit']=="%"){
                                    $original_cost = $original_cost + ($original_cost*($pricedata['price'][$from_price]['original_value']/100));
                                }else{
                                    $original_cost = $original_cost + $pricedata['price'][$from_price]['original_value'];
                                }
                            }else{
                                $currency_id   = $pricedata['price'][$from_price]['unit'];
                                $original_cost = $pricedata['price'][$from_price]['original_value']; //Цена в валюте которую установили
                            } 
                        

                            //Увеличим или уменьшим цену в зависимости от того, что выбрано
                            if ($cdata['plus_type']) { //Если в еденицах
                                $delta = $cdata['plus_value'];
                            } else {
                                //Если задано в процентах
                                $delta = $original_cost * ($cdata['plus_value']/100);
                            }
                       
                            $way = $cdata['way'] ? -1 : 1;
                            $original_cost = $original_cost + ($way * $delta); //Посчитываем цену
                            
                            $cost = \Catalog\Model\CostApi::roundCost($original_cost); //Цена в валюте по умолчанию
                            //Если у нас валюта не по умолчанию, то подсчитаем значения по курсу для значения в валюте по умолчанию
                            if ($currency_id && $currency_id!=$currency_default_id){
                                $cost = $original_cost*$currencies[$currency_id]['ratio'];
                                $cost = \Catalog\Model\CostApi::roundCost($cost);
                            }
                       
                            $pricedata['price'][$cost_id] = array(
                                'znak' => '=',
                                'unit' => $currency_id,
                                'original_value' => $original_cost,
                                'value' => $cost,
                            );
                        }
                    }
                    //Если просто задано равное значение цены
                }else if (($pricedata['price'][$cost_id]['znak'] == "=") && ($pricedata['price'][$cost_id]['unit'] != "%")){
                
                    $currency_id   = $cdata['cost_original_currency'];
                    $original_cost = $cdata['cost_original_val']; //Цена в валюте которую установили
                    $cost = $original_cost;
                    if ($currency_id != $currency_default_id){
                       $cost = $original_cost * $currencies[$currency_id]['ratio'];
                       $cost = \Catalog\Model\CostApi::roundCost($cost);
                    }
                    $pricedata['price'][$cost_id] = array(
                       'znak' => '=',
                       'unit' => $currency_id,
                       'original_value' => $original_cost,
                       'value' => $cost,
                    );
                }
                
            }
            
            //Обновляем цены комплектации, уже подготовленным массивом
            \RS\Orm\Request::make()
                    ->update()
                    ->from(new \Catalog\Model\Orm\Offer())
                    ->set(array(
                       'pricedata' => serialize((array)$pricedata)
                    ))
                    ->where(array(
                        'id' => $offer_id,
                        'site_id' => \RS\Site\Manager::getSiteId()
                    ))
                    ->exec();
        } 
    }
    
    /**
    * Мульти обновление цены у товаров и комплектаций
    * 
    * @param array $ids - массив id товаров
    * @param array $excost - массив сведений об именении цены или с полями изменения цен
    */
    function multiUpdateProductsAndOffersPrices( $ids = array(), array $excost )
    {
        $all_prices = array(); //Массив с ценами, если поставлен флаг увеличения значения
        //Если необходимо мультиредактирование цен товаров и оно задано, то получим массив 
        $replace_price = array();
        foreach ($excost as $cost_id => $cdata) {
           //Получим массив с id цен, который будет заменён  
           if (trim($cdata['cost_original_val']) !== "" ) $replace_price[$cost_id] = $cost_id;
           if (isset($cdata['edit_multi']) && trim($cdata['plus_value']) !== "") {
               
             //Получим в массив старые цены для всех найденных объектов
             $all_prices[$cost_id] = \RS\Orm\Request::make()
                ->from(new Xcost())
                ->where(array('cost_id'=>$cdata['from_price']))
                ->whereIn('product_id', $ids)->exec()->fetchSelected('product_id');
             
             $replace_price[$cost_id] = $cost_id; //Добавим на замену   
           } 
           
           //Исключим из массива цены, которые менять не надо
           if (!isset($replace_price[$cost_id])){
              unset($excost[$cost_id]); 
           }
        }

        if (!empty($replace_price)){ //Какие точно удалять, чтобы заменить
            //Удалим старые цены
            \RS\Orm\Request::make()->delete()
                ->from(new Xcost())
                ->whereIn('cost_id', $replace_price)  
                ->whereIn('product_id', $ids)
                ->exec();  
        }
        
         
        //Обновляем цены товара и его комплектаций
        if (!empty($excost)) {
            //Cначала цены товара
            $this->multiUpdateProductPrices($ids, $excost, $all_prices);
            //Обновим для товара цены комплектаций
            $this->multiUpdateProductOffers($ids, $excost, $all_prices);
        }
    }
    
    /**
    * Удаляет дубликаты характеристик в БД
    * 
    */
    function deleteDuplicateProperties()
    {
        $sufix = mb_substr(sha1(time()), 0, 8);
        $temp_table = \Setup::$DB_TABLE_PREFIX.'prop_temp_'.$sufix;
        $junk_table = \Setup::$DB_TABLE_PREFIX.'junk_temp_'.$sufix;
        
        $prop_link = new \Catalog\Model\Orm\Property\Link();
        //Получим запрос на создание таблиции подменим имя
        $table_info = $prop_link->_getTableArray();
        $create_table_query = str_replace($table_info[1], $temp_table, \RS\Db\Adapter::sqlExec('SHOW CREATE TABLE '.$prop_link->_getTable())->getOneField('Create Table', ''));    
        
        \RS\Db\Adapter::sqlExec($create_table_query.' SELECT DISTINCT * FROM '.$prop_link->_getTable());
        \RS\Db\Adapter::sqlExec('ALTER TABLE '.$prop_link->_getTable().' RENAME '.$junk_table);
        \RS\Db\Adapter::sqlExec('ALTER TABLE `'.$temp_table.'` RENAME '.$prop_link->_getTable());
        \RS\Db\Adapter::sqlExec('DROP TABLE `'.$junk_table.'`');
    }

    /**
     * Функция быстрого группового редактирования товаров
     *
     * @param array $data - массив данных для обновления
     * @param array $ids - идентификаторы товаров на обновление
     * @return int
     */
    function multiUpdate(array $data, $ids = array())
    {  
        //Добавим событие перед обновлением
        $event_result = \RS\Event\Manager::fire('orm.beforemultiupdate.catalog-product',array(
            'data' => $data, 
            'ids' => $ids, 
            'api' => $this, 
        ));
        if ($event_result->getEvent()->isStopped()) return false;
        list($data, $ids) = $event_result->extract();
        
        $sql = array();
        $need_calculate_product_num = false;
        
        $spec_dirs = $this->obj_instance->getSpecDirs(true);

        $xdir       = new Xdir();
        $xdir_table = $xdir->_getTable();

        $merged_xdir  = array();
        $need_reindex = array_intersect(array('title', '_property_', 'barcode', 'brand_id',
                                              'short_description', 'meta_keywords'), array_keys($data)) != false;
        
        // Сбрасываем хэши импорта
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Product())
            ->set(array('import_hash' => null))
            ->whereIn('id', $ids)
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
            ->exec();
        
        //Загружаем фотографии к товарам
        if (isset($data['simage'])) {
            $photo_api = new \Photo\Model\PhotoApi();
            
            if (!empty($data['simage']['delete_all_photos'])) {
                $q = $photo_api->queryObj();
                $q->where(array('type' => Orm\Product::IMAGES_TYPE))
                  ->whereIn('linkid', $ids)
                  ->select = 'id';
                    
                $photo_ids = $q->exec()->fetchSelected(null, 'id');
                $photo_api->multiDelete($photo_ids);
            }
            
            if (isset($_FILES['simagefile'])) {
                $photo_api->addFromUrl($_FILES['simagefile']['tmp_name'], 'catalog', $ids);
            }
            unset($data['simage']);
        }

        //Обновляем спец. категории
        if (isset($data['xspec']) && count($spec_dirs) > 0) {
            $merged_xdir = $data['xspec'];
            
            if (!empty($data['xspec']['delbefore'])) {
                \RS\Db\Adapter::sqlExec("DELETE FROM ".$xdir_table." WHERE product_id IN (" . implode(',', $ids) . ") 
                                            AND dir_id IN (" . implode(',', array_keys($spec_dirs)) . ")");
            }
            unset($data['xspec']['delbefore']);

            if (!empty($data['xspec'])) {
                $xspec = array();
                $dirs_with_sortn = array();
                foreach ($ids as $id)
                    foreach ($data['xspec'] as $dir) {
                        $xspec[] = "('".$id."', '".$dir."')";
                    }
                $sql[] = "INSERT IGNORE INTO ".$xdir_table."(product_id, dir_id) VALUES" . implode(',', $xspec);
            }
            unset($data['xspec']);
        }

        //Обновляем категории     
        if (isset($data['xdir'])) {
            if (!empty($data['xdir'])) {
                //Если удалить связь со старыми категориями
                if (!isset($data['xdir']['notdelbefore']) || !$data['xdir']['notdelbefore']) {
                    $add_dir_where = (count($spec_dirs) > 0) ? ' AND dir_id NOT IN (' . implode(',', array_keys($spec_dirs)) . ')' : '';
                    \RS\Db\Adapter::sqlExec("DELETE FROM ".$xdir_table." WHERE product_id IN (" . implode(',', $ids) . ") " . $add_dir_where);
                } else {
                    unset($data['xdir']['notdelbefore']); 
                }
                
                $merged_xdir = array_merge($merged_xdir, $data['xdir']);

                $dirs_with_sortn = array();
                $xdirs = array();
                foreach ($ids as $id) {
                   foreach ($data['xdir'] as $dir) {
                      $xdirs[] = "('".$id."', '".$dir."')";
                   } 
                } 
                $sql[] = "INSERT IGNORE INTO ".$xdir_table."(product_id, dir_id) VALUES" . implode(',', $xdirs);
            }
            unset($data['xdir']);
        }
        
        //Обновляем характеристики
        if (isset($data['_property_'])){
            
           $save_prop_links = false; 
           //Если пользователь выбрал удалить все существующие характеристики у выбранных товаров 
           if (isset($data['_property_'][0])){
               $q = \RS\Orm\Request::make()->delete()
                        ->from(new \Catalog\Model\Orm\Property\Link())
                        ->where(array(
                            'site_id' => \RS\Site\Manager::getSiteId()
                        ))  
                        ->whereIn('product_id', $ids)
                        ->exec();  
               unset($data['_property_'][0]);
           }else{
               //Удалим предыдущие значения этих характеристик
               foreach($data['_property_'] as $k=>$property){
                    if (!isset($property['savelink'])){ //Если не нужно сохранять связи с ранее установлеными данными
                        $q = \RS\Orm\Request::make()->delete()
                            ->from(new \Catalog\Model\Orm\Property\Link())
                            ->where(array(
                                'prop_id' => $property['id'],
                                'site_id' => $property['site_id']
                            ))  
                            ->whereIn('product_id', $ids)
                            ->exec();    
                    }else{
                        $save_prop_links = true;
                    }
                    //Вычеркнем те характеристики у которых флаг удалить отмечен
                    if (isset($property['delit']) && !isset($prop_item['savelink'])){
                        unset($data['_property_'][$k]);
                    }  
               }
           } 
           
           if (!empty($data['_property_'])){
               //Заполним выбранные характеристики для товаров
               foreach ($ids as $id) {
                   foreach($data['_property_'] as $property){
                       $prop_item = new \Catalog\Model\Orm\Property\Link();
                       if (!empty($property['check'])) {
                          foreach($property['check'] as $value){ //Если список значений
                             $prop_item->fillData($id, $value, $property);
                             unset($prop_item['val_str']);
                             $prop_item->insert();
                          }
                       } else { //Если всё остальное
                          $prop_item->fillData($id, $property['value'], $property);
                          $prop_item->insert();
                       }
                   } 
               }
               //Если флаг сохранения ранее установленных значений был установлен
               //Исключим возможные дубликаты
               if ($save_prop_links){ 
                   $this->deleteDuplicateProperties();
               }
           }
           
           unset($data['_property_']);
        }
        
        
        //Обновляем комплектации и многомерные комплектации
        if ( isset($data['_offers_']) ) {
            
            // Сбрасываем хэши импорта комплектаций
            \RS\Orm\Request::make()
                ->update(new \Catalog\Model\Orm\Offer())
                ->set(array('import_hash' => null))
                ->whereIn('product_id', $ids)
                ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
                ->exec();
            
            //Удаляем комплектации, если есть флаг
            if ( isset($data['_offers_']['delete']) ) {
               $offer_api = new \Catalog\Model\OfferApi(); 
               $offer_api->deleteOffersByProductId($ids);
            }
            
            $moffer_api        = new \Catalog\Model\MultiOfferLevelApi();
            $levels_by_product = array();//Массив с уровнями многомерных комплектаций соотвественно товару
            
            
            //Записываем уровни многомерных комплектаций      
            if ( isset($data['_offers_']['levels']) ) {
                foreach ($ids as $id) {     
                   //Оставим только те уровни которые необходимы товару                   
                   $levels_by_product[$id] = $moffer_api->prepareRightMOLevelsToProduct($id, $data['_offers_']['levels']);
                   
                   if (isset($data['_offers_']['is_photo']) && ($data['_offers_']['is_photo']>0) && !empty($levels_by_product[$id]) && isset($levels_by_product[$id][$data['_offers_']['is_photo']])){ //Флаг "С фото" У многомерных комплектаций
                       $levels_by_product[$id][$data['_offers_']['is_photo']]['is_photo'] = 1;  
                   }
                   
                   //Сохранение уровней мн. комплектаций
                   $moffer_api->saveMultiOfferLevels($id, $levels_by_product[$id]); 
                } 
            }else{
                foreach ($ids as $id) {  
                    $moffer_api->clearMultiOfferLevelsByProductId($id);
                }
            }
            
            //Записываем сами комплектации из мн. уровней комплектаций
            if (isset($data['_offers_']['create_autooffers']) && isset($data['_offers_']['levels'])){
                foreach ($ids as $id) { 
                    if ($moffer_api->createOffersFromLevels($id, $levels_by_product[$id]) === false) {
                        $this->addError($moffer_api->getErrorsStr());
                        $moffer_api->cleanErrors();
                    }
                }
            }
            
            $need_calculate_product_num = true;
            unset($data['_offers_']);
        }
        
        
        //Обновляем цены
        if (isset($data['excost'])) {
            $this->multiUpdateProductsAndOffersPrices($ids, $data['excost']);
            unset($data['excost']);
        }
        
        
        
        //Обновляем рекоммендуемые товары
        if (isset($data['recommended_arr']) && isset($data['recommended_arr']['product'])){
           $recomended = serialize($data['recommended_arr']);
           \RS\Orm\Request::make()
                    ->from(new \Catalog\Model\Orm\Product())
                    ->set(array(
                       'recommended' => $recomended 
                    ))
                    ->whereIn('id', $ids)
                    ->where(array(
                        'site_id' => \RS\Site\Manager::getSiteId()
                    ))                                            
                    ->update()
                    ->exec();
        }
        unset($data['recommended_arr']);
        
        //Обновляем сопутсвующие товары
        if (isset($data['concomitant_arr']) && isset($data['concomitant_arr']['product'])){
            $concomitant = serialize($data['concomitant_arr']);
            \RS\Orm\Request::make()
                    ->from(new \Catalog\Model\Orm\Product())
                    ->set(array(
                       'concomitant' => $concomitant 
                    ))
                    ->whereIn('id', $ids)
                    ->where(array(
                        'site_id' => \RS\Site\Manager::getSiteId()
                    ))                                            
                    ->update()
                    ->exec(); 
        }
        unset($data['concomitant_arr']);
        
        //Обновляем остатки для нулевой комплектации
        if (isset($data['num'])){
            
           //Получим id нулевых комплектаций 
           $offers = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Offer())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'sortn' => 0
                ))
                ->whereIn('product_id',$ids)
                ->exec()
                ->fetchSelected('product_id','id'); 
            
           $offer_sql = array();
           $stockClass = new \Catalog\Model\Orm\Xstock();           
           foreach($data['num'] as $warehouse_id=>$num){
              if ($num !== '') {
                  $stock = (int)$num;
                  
                  //Удалим остатки нулевой комплектации, если задано значение
                  if ($offers) {
                      \RS\Orm\Request::make()
                            ->from($stockClass) 
                            ->whereIn('offer_id', $offers)
                            ->where(array(
                              'warehouse_id' => $warehouse_id
                            ))
                            ->delete()
                            ->exec();
                  }
                        
                  $num_rows = array();
                  //Добавим остатки для нулевых комплектаций для каждого склада
                  foreach($ids as $id){
                      if (!isset($offers[$id])) {
                          $offer = new \Catalog\Model\Orm\Offer();
                          $offer['product_id'] = $id;
                          $offer['sortn'] = 0;
                          $offer->insert();
                          $offers[$id] = $offer['id'];
                      }
                      
                      $num_rows[] = "('". (int)$id."','".(int)$offers[$id]."','".(int)$warehouse_id."','".(int)$stock."')"; 
                  }
                  
                  $offer_sql[] = "INSERT INTO ".$stockClass->_getTable()." (
                    product_id,
                    offer_id,
                    warehouse_id,
                    stock
                  ) VALUES ".implode(",", $num_rows); 
              } 
           } 
           
           foreach ($offer_sql as $query) {
                \RS\Db\Adapter::sqlExec($query);
           }
           
           //Пересчитываем общий остаток комплектаций
           $offer = new \Catalog\Model\Orm\Offer();
           \RS\Orm\Request::make()
                ->update()
                ->from($offer, 'O')
                ->set('O.num = (SELECT SUM(stock) FROM '.$stockClass->_getTable().' S WHERE S.offer_id = O.id)')
                ->whereIn('O.product_id', $ids)
                ->exec();
                
           $need_calculate_product_num = true;
            
           unset($data['num']); 
        }
        
        if ($need_calculate_product_num) {
           //Пересчитываем общий остаток товара
           $sub_query = \RS\Orm\Request::make()
                ->select('SUM(num)')
                ->from(new Orm\Offer(), 'O')
                ->where('O.product_id = P.id');
         
           \RS\Orm\Request::make()
                ->update()
                ->from(new \Catalog\Model\Orm\Product, 'P')
                ->set('P.num = ('.$sub_query.')')
                ->whereIn('P.id', $ids)
                ->exec();
        }
        
        foreach ($sql as $query) {
            \RS\Db\Adapter::sqlExec($query);
        }
        
        //Обновляем товары
        $ret = parent::multiUpdate($data, $ids);
         
        //Обновляем кэширующее поле public в таблице ..._x_dir
        if (!empty($merged_xdir) || isset($data['public'])) {
            Dirapi::updateCounts();
        }
        
        //Если необходимо переиндексировать товары
        if ($need_reindex) {
            $this->updateProductSearchIndex($ids);
        }
       
        //Добавим событие на обновлении
        \RS\Event\Manager::fire('orm.multiupdate.catalog-product',array(
            'ids' => $ids,
            'api' => $this
        ));
        return $ret;
    }
    
    /**
    * Обновляет поисковый индекс у товаров
    * 
    * @param array $ids - список id товаров
    * @return bool
    */
    function updateProductSearchIndex($ids)
    {
        if (empty($ids)) return false;
        
        $i = 0;
        $page_size = 100;
        
        $q = \RS\Orm\Request::make()
            ->from($this->obj_instance)
            ->whereIn('id', $ids)
            ->limit($page_size);
        
        while($rows = $q->offset($i)->objects()) {
            foreach($rows as $product) {
                /**
                 * @var \Catalog\Model\Orm\Product $product
                 */
                $product->updateSearchIndex();
            }
            $i = $i + $page_size;
        }
        return true;
    }

    /**
     * Возвращает именно товары по результатам поиска
     *
     * @param array $list_of_results - массив результатов поиска
     * @return array
     */
    function getBySearchResult($list_of_results)
    {
        $ids = array();
        foreach ($list_of_results as $result) {
            $ids[] = $result['entity_id'];
        }
        if (empty($ids)){
            return array();
        }

        $this->setFilter('id', $ids, 'in');
        $this->setOrder('FIELD(id,' . implode(',', $ids) . ')');
        $products = $this->getList();
        return $products;
    }

    /**
    * Устанавливает фильтры, которые следуют из параметров конфигурации модуля "Каталог"
    * 
    * @return \RS\Orm\Request
    */
    function filterRequest()
    {
        $config = \RS\Config\Loader::byModule($this);

        $q = \RS\Orm\Request::make()
                ->join($this->obj_instance, 'P.id = A.entity_id', 'P')
                ->where('public = 1');
        if ($config['hide_unobtainable_goods'] == 'Y') {
            $q->where('num > 0');
        }
        return $q;
    }

    /**
    * Устанавливает фильтры, от компонента html_filter
    */
    public function addFilterControl(\RS\Html\Filter\Control $filter_control)
    {
        parent::addFilterControl($filter_control);
    }

    /**
    * Возвращает список категорий в которых состоят товары, возвращаемые функцией getList
    * 
    * @return array of Orm\Dir
    */
    function getDirList()
    {
        $q = clone $this->queryObj();
        $q->select = 'DL.*, COUNT(*) as itemcount';
        $q->join(new Orm\Dir(), 'A.maindir = DL.id', 'DL')
                ->where('DL.public = 1')
                ->orderby(null)
                ->groupby('DL.id');
        return $q->objects(new Orm\Dir());
    }

    function getSameByCostProducts($product, $delta, $page_size)
    {
        $cost = $product->getCost(null, null, false);
        
        $this->costFilter(array(
            'from' => $cost - $cost * ($delta / 100),
            'to'   => $cost + $cost * ($delta / 100)
        ));
        
        $this->setFilter('dir', $product->maindir);
        $this->setFilter('id', $product->id, '!=');

        if(\RS\Config\Loader::byModule('catalog')->hide_unobtainable_goods == 'Y'){
            $this->setFilter('num', 0 , '>');
        }
        return $this->getList(1, $page_size);
    }
    
    /**
    * Получает доступные значения брендов для категорий
    *
    * @param string $cache_key  - ключ для кэша
    * @param boolean $cache     - флаг включения кэша
    * @return \Catalog\Model\Orm\Brand[]|false
    */
    function getAllowableBrandsValues($cache_key, $cache = true){
        if ($cache) {
            return \RS\Cache\Manager::obj()
                ->tags(CACHE_TAG_UPDATE_CATEGORY)
                ->request(array($this, 'getAllowableBrandsValues'), $cache_key, false);
        } else {
            $q = clone $this->queryObj();            
            $q  ->select = 'Brand.*';
            $q  ->join(new \Catalog\Model\Orm\Brand(),'A.brand_id=Brand.id','Brand')
                ->groupby('A.brand_id')
                ->where(array('A.public' => 1))
                ->orderby('Brand.title ASC');
            
            $config = \RS\Config\Loader::byModule($this);
            if ($config['hide_unobtainable_goods'] == 'Y') {
                $q->where('A.num>0'); //Не учитываем товары с нулевым остатком
            }
                
            return $q->objects('\Catalog\Model\Orm\Brand', 'id');
       }
    }
    
    /**
    * Возвращает все возможные значения свойств для текущей выборки товаров, 
    * - диапазоны значений и шаг для характеристик типа int
    * - массив возможных значений для характеристик типа list
    * - массив возможных значений для характеристик типа bool
    * 
    * @param integer $dir_id id текущей категории товаров
    * @param mixed $cache_key - id кэша
    * @param bool $cache - Если true, то будет использоваться кэширование
    * 
    * @return array
    */
    function getAllowablePropertyValues($dir_id, $cache_key, $cache = true)
    {
        if ($cache) {
            return \RS\Cache\Manager::obj()
                ->tags(CACHE_TAG_UPDATE_CATEGORY)
                ->request(array($this, 'getAllowablePropertyValues'), $dir_id, $cache_key, false);
        } else {
            //Получаем фильтрующие характеристики для текущего каталога
            $result = array();
            
            $properties = array();            
            $prop_api = new \Catalog\Model\Propertyapi();
            foreach($prop_api->getGroupProperty($dir_id, true, true) as $group) {
                $properties += $group['properties'];
            }
            $public_prop_ids = array_keys($properties);
            
            //Запрашиваем возможные значения
            if ($public_prop_ids) {
                $res = $this->getSelectAllowableValuesQuery($public_prop_ids)->exec();
                
                $min_max = array();
                $values = array();
                $list_values_id = array();
                
                while($row = $res->fetchRow()) {
                    $prop_id = $row['prop_id'];
                    if (isset($properties[$prop_id])) {
                        $value = $row[ $properties[$prop_id]->getValueLinkField() ];
                        if ($value === '' || $value === null) continue;
                        
                        switch($properties[$prop_id]['type']) {
                            case 'int': {
                                if (!isset($min_max[$prop_id])) {
                                    $min_max[$prop_id] = array(
                                        'interval_from' => $value,
                                        'interval_to' => $value,
                                        'step' => 1
                                    );
                                } else {
                                    if ($value < $min_max[$prop_id]['interval_from']) {
                                        $min_max[$prop_id]['interval_from'] = $value;
                                    }
                                    if ($value > $min_max[$prop_id]['interval_to']) {
                                        $min_max[$prop_id]['interval_to'] = $value;
                                    }
                                }
                                if ($this->getDecimal($min_max[$prop_id]['step'])< $this->getDecimal($value)) {
                                    $min_max[$prop_id]['step'] = 1 / ('1'.str_repeat('0', $this->getDecimal($value)));
                                }
                                break;
                            }
                            
                            default: {
                                if ($row['val_list_id'] > 0 && $properties[$prop_id]->isListType()) {
                                    //Собираем ID значений характеристик, чтобы заполнить их далее
                                    $list_values_id[] = $row['val_list_id']; 
                                } else {
                                    $values[$prop_id]['allowed_values'][$value] = $value;
                                }
                            }
                        }
                    }
                }
                
                $values = $this->appendListValues($values, $list_values_id);
                $result = $min_max + $values;
            }
            return $result;
        }
    }
    
    /**
    * Загружает значения для списковых характеристик
    * 
    * @param array $values - значения характеристик установленных ранее
    * @param array $list_values_id - список идентификаторов значений характеристик для добавления к существующим
    * @return array
    */
    private function appendListValues($values, $list_values_id)
    {
        if ($list_values_id) {
            $result = array();
            //Загружаем отсортированные объекты значений
            $item_values = \RS\Orm\Request::make()
                ->from(new Orm\Property\ItemValue())
                ->whereIn('id', $list_values_id)
                ->orderby('prop_id, sortn')
                ->objects();
            
            foreach($item_values as $item_value) {
                $result[$item_value['prop_id']]['allowed_values'][$item_value['id']] = $item_value['value']; //Для совместимости с предыдущими версиями
                $result[$item_value['prop_id']]['allowed_values_objects'][$item_value['id']] = $item_value;
            }
            $values = $result + $values;
        }
        return $values;
    }
    
    /**
    * Возвращает объект запроса на выборку возможных значений 
    * 
    * @return \RS\Orm\Request
    */
    private function getSelectAllowableValuesQuery($public_prop_ids)
    {
        $q = clone $this->queryObj();
        $q->orderby(false);
        $q->select = 'ALP.prop_id, ALP.val_str, ALP.val_int, ALP.val_list_id';
        $q->join(new \Catalog\Model\Orm\Property\Link(), "ALP.product_id = A.id", "ALP")
            ->groupby(null)
            ->where('A.public = 1')
            ->whereIn('ALP.prop_id', $public_prop_ids);
            
        $config = \RS\Config\Loader::byModule($this);
        
        if ($config->link_property_to_offer_amount) {
            $q->where('ALP.available = 1');
        }
            
        if ($config['hide_unobtainable_goods'] == 'Y') {
            $q->where('A.num>0'); //Не учитываем товары с нулевым остатком
        }
        return $q;
    }
    
    /**
    * Возвращает дробную часть числа
    * 
    * @param float $float - число с плавающей точкой
    * @return integer
    */
    private function getDecimal($float)
    {
        if (!$dot_pos = strpos($float, '.')) {
            return 0;
        }
        return strlen($float)-$dot_pos-1;
    }
    
    /**
    * Добавляет символьные идентификаторы товарам, у которых они не установлены
    * 
    * @return integer
    */
    function addTranslitAliases()
    {
        $count = 0;
        $this->queryObj()->where("(alias IS NULL OR alias='')");
        $res = $this->getListAsResource();
        while($row = $res->fetchRow()) {
            $count++;
            $product = new Orm\Product();
            $product->getFromArray($row);
            $i = 0;
            $ok = false;
            while(!$ok && $i<15) {
                $product[$this->alias_field] = \RS\Helper\Transliteration::str2url($product['title']).(($i>0) ? "-$i" : '');
                $ok = $product->update();
                $i++;
            }
        }
        return $count;
    }
    
    /**
    * Получает риски для цен диапозона цен
    * 
    * @return string
    */
    function getHeterogeneity($min, $max)
    {
           $max = floatval($max); 
           $min = floatval($min); 
           //Проверим возможно ли это
           if ($max==$min) return "";
           $delta = $max-$min;
           $d25 = ceil($min + (($delta/100)*25)); 
           $d50 = ceil($min + (($delta/100)*50)); 
           $d75 = ceil($min + (($delta/100)*75)); 
           return '"25/'.$d25.'","50/'.$d50.'","75/'.$d75.'"';
    }
    
    /**
    * Перемещает элемент from на место элемента to. Если flag = 'up', то до элемента to, иначе после
    * 
    * @param int $dir_id - id категории в которой происходит перемещение
    * @param int $from - id элемента, который переносится
    * @param int $to - id ближайшего элемента, возле которого должен располагаться элемент
    * @param string $flag - up или down - флаг выше или ниже элемента $to должен располагаться элемент $from
    * 
    * @return boolean
    */
    function moveProduct($dir_id, $from, $to, $flag)
    {
        if ($this->noWriteRights()) return false;
        
        //Только если указана директория
        if (!$dir_id) return false;
        
        $xdir = new \Catalog\Model\Orm\Xdir();
        
        $is_up = $flag == 'up';
        $this->sort_field = 'sortn';
        
        $from_obj = \RS\Orm\Request::make()
                        ->from($this->obj_instance, 'P')
                        ->join($xdir, 'X.product_id=P.id AND X.dir_id='.$dir_id, 'X')
                        ->where(array('id' => $from))
                        ->object();
                        
        $to_obj = \RS\Orm\Request::make()
                        ->from($this->obj_instance, 'P')
                        ->join($xdir, 'X.product_id=P.id AND X.dir_id='.$dir_id, 'X')
                        ->where(array('id' => $to))
                        ->object();
        
        $from_sort = $from_obj[$this->sort_field];
        $to_sort   = $to_obj[$this->sort_field];
        
        $r = '=';
        
        if ((!$is_up && $from_sort > $to_sort) || ($is_up && $from_sort < $to_sort)) {
            $r = ''; $is_up = !$is_up;
        }
        
        if ( $from_sort >= $to_sort) {
            $filter = $this->sort_field." >".$r." '".$to_sort."' AND ".$this->sort_field." <= '".$from_sort."'"; 
        } else {
            $filter = $this->sort_field." >= '".$from_sort."' AND ".$this->sort_field." <".$r." '".$to_sort."'";
        }     
        
        $res = \RS\Orm\Request::make()   
            ->from($xdir)
            ->where(array(
                'dir_id' => $dir_id
            ))
            ->where($filter)
            ->orderby($this->sort_field);
        $res = $res->exec();
            
        if ($res->rowCount() < 2) return true;
        
        $list = $res->fetchAll();
        
        if ($is_up) $list = $this->moveArrayUp($list); 
            else $list = $this->moveArrayDown($list); 
        
        foreach ($list as $newValues)
        {
            \RS\Orm\Request::make()
                ->update($xdir)
                ->set(array($this->sort_field => $newValues[$this->sort_field]))
                ->where(array(
                    'dir_id' => $newValues['dir_id'],
                    'product_id' => $newValues['product_id'],
                ))
                ->exec();
        }
        return true;
    }
    
    
    /**
    * Перемещает объект товара в сортировке в самый верх для заданной директории
    *  
    * @param integer $dir_id - id директории
    * @param integer $product_id - id товара
    * @param string $flag - up или down - флаг выше или ниже элемента $to должен располагаться элемент $from
    * @return boolean
    */
    function moveProductInDir($dir_id, $product_id, $flag)
    {
        //Найдем товар который находится в самом начале или в конце, и если это не тот же самый товар, то переместим его.
        $q = \RS\Orm\Request::make()
                    ->from(new \Catalog\Model\Orm\Xdir())
                    ->where(array(
                        'dir_id' => $dir_id,
                    ))
                    ->limit(1);
                    
        if ($flag == 'up'){
            $q->orderby('sortn ASC'); 
        }else{
            $q->orderby('sortn DESC');
        }
                    
        $xdir = $q->exec()->fetchRow();    
                    
        if ($xdir && ($xdir['product_id'] != $product_id)){
            return $this->moveProduct($dir_id, $product_id, $xdir['product_id'], $flag); 
        }
        return true;
    }
    
    /**
    * Возвращает информацию о минимальной и максимальной цене товаров, 
    * для текущих условий выборки
    * 
    * @return array
    */
    function getMinMaxProductsCost()
    {
        $minmax_query = clone $this->queryObj();
        
        $cost_api = \Catalog\Model\CostApi::getInstance();
        
        $current_cost_type = $cost_api->getUserCost();
        $manual_cost_type = $cost_api->getManualType($current_cost_type);
        
        $minmax_query->select = 'max(XC.cost_val) interval_to, min(XC.cost_val) interval_from';
        //Если таблица с цена ещё не добавлена
        if (!$minmax_query->issetTable(new \Catalog\Model\Orm\Xcost())){
            $minmax_query
                ->leftjoin(new \Catalog\Model\Orm\Xcost(), "A.id = XC.product_id AND XC.cost_id='{$manual_cost_type}'", 'XC');
        }
        $minmax_query->orderby(null)
                     ->limit(null);
        
        $money_array = $minmax_query->exec()->fetchRow() ?: array('interval_from' => 0, 'interval_to' => 0);
        
        $current_cost = $cost_api->getCostById($current_cost_type);
        
        if ($current_cost['type'] == 'auto') {
            $money_array['interval_from'] = $cost_api->calculateAutoCost($money_array['interval_from'], $current_cost);
            $money_array['interval_to'] = $cost_api->calculateAutoCost($money_array['interval_to'], $current_cost);
        }

        //Убираем нулевые копейки
        $money_array['interval_from'] = str_replace('.00', '', $money_array['interval_from']);
        $money_array['interval_to'] = str_replace('.00', '', $money_array['interval_to']);

        return $money_array;
    }
    
    /**
    * Возвращает номер комплектации по значениям многомерной комплектации
    * 
    * @param integer $product_id - id товара
    * @param array $multioffers_values - массив характеристик многомерной комплектации (title => value)
    * @return integer
    */
    static public function getOfferByMultiofferValues($product_id, $multioffers_values)
    {
        $offers = \RS\Orm\Request::make()
                    ->select('sortn, propsdata')
                    ->from(new \Catalog\Model\Orm\Offer())
                    ->where(array('product_id' => $product_id,))
                    ->exec()->fetchAll();
        foreach($offers as $offer) {
            $offer['propsdata_arr'] = (!empty($offer['propsdata'])) ? unserialize($offer['propsdata']) : array();
            $found = 0;
            foreach($offer['propsdata_arr'] as $key=>$value) {
                foreach($multioffers_values as $item) {
                    if($item['title'] == $key && $item['value'] == $value) {
                        $found++;
                        break;
                    }
                }
            }
            if(($found == count($offer['propsdata_arr'])) and ($found == count($multioffers_values))) {
                return $offer['sortn'];
            }
        }
        return 0;
    }
    
    /**
    * Возвращает общее количество элементов, согласно условию.
    * 
    * @return integer
    */
    function getListCount()
    {
        $q = clone $this->queryObj();

        return $q->select('COUNT(DISTINCT '.$this->defAlias().'.'.$this->getIdField().') as cnt')
            ->limit(null)
            ->orderby(null)
            ->exec()
            ->getOneField('cnt', 0);
    }

    /**
     * Возвращает установленные фильтры в структурированном виде
     *
     * @return array
     */
    function getSelectedFiltersAsString($base_filters, $brands, $prop_filters)
    {
        $parts = array_merge($this->getBaseFiltersParts($base_filters, $brands),
            $this->getPropertyFiltersParts($prop_filters));

        return $parts;
    }

    /**
     * Возвращает данные по базовым фильтрам
     *
     * @param array $base_filters структура с выбранными фильтрами
     * @param array $brands Ассоциативный массив брендов
     * @return array
     */
    protected function getBaseFiltersParts($base_filters, $brands)
    {
        $parts = array();
        $part  = array();
        //Добавим информацию по стандартным фильтрам
        foreach($base_filters as $key => $values) {
            if ($values != "") {
                switch ($key) {
                    case 'brand':
                        $brand_titles = array();
                        foreach ($values as $value) {
                            if (isset($brands[$value])) {
                                $brand_titles[] = $brands[$value]['title'];
                            }
                        }
                        $part = array(
                            'title' => t('Бренд:') . implode(', ', $brand_titles)
                        );
                        break;

                    case 'cost':
                        $part = array(
                            'title' => t('Цена:') .
                                (!empty($values['from']) ? t('от ').$values['from'] : '').
                                (!empty($values['to']) ? t(' до ').$values['to'] : '')
                        );
                        break;

                    case 'isnum':
                        if ($values != "") {
                            $part = array(
                                'title' => $values ? t('В наличии') : t('Нет в наличии')
                            );
                        }
                        break;

                    default:
                        $part = array( 'title' => '');
                }
            }

            $parts[] = $part + array(
                    'type' => $key,
                    'filter' => 'base'
                );
        }

        return $parts;
    }

    /**
     * Возвращает данные по установленным фильтрам по характеристикам
     *
     * @param array $prop_filters массив, полученный от Catalog\Model\PropertyApi->last_filtered_props
     * @return array
     */
    protected function getPropertyFiltersParts($prop_filters)
    {
        $parts = array();
        $part  = array();
        foreach($prop_filters as $prop_id => $data) {
            if ($data['property']->isListType()) {
                $part = array(
                    'title' => $data['property']['title'].': '.implode(', ', $data['values'])
                );
            } else {
                switch($data['property']['type']) {
                    case Item::TYPE_NUMERIC:
                        $part = array(
                            'title' => $data['property']['title'] .': '.
                                (!empty($data['values']['from']) ? t('от ').$data['values']['from'] : '').
                                (!empty($data['values']['to']) ? t(' до ').$data['values']['to'] : '')
                        );
                        break;
                    case Item::TYPE_STRING:
                        $part = array(
                            'title' => $data['property']['title'].': '.implode(', ', $data['values'])
                        );
                        break;
                    case Item::TYPE_BOOL:
                        $part = array(
                            'title' => $data['property']['title'].': '.($data['values'] ? t('да') : t('нет'))
                        );
                        break;
                }
            }
            $parts[] = $part + array(
                    'type' => $prop_id,
                    'filter' => 'property'
                );
        }

        return $parts;
    }


    /**
     * Добавляет к мета тегам Keywords, Description выбранные фильтры
     *
     * @param array $all_filters_data Массив с выбранными значениями фильтров
     * @return void
     */
    function applyFiltersToMeta($all_filters_data)
    {
        $items = array();
        foreach($all_filters_data as $part) {
            $items[] = $part['title'];
        }

        $app = \RS\Application\Application::getInstance();
        $append_text = implode('; ', $items);
        $app->meta->addKeywords($append_text);
        $app->meta->addDescriptions($append_text);
    }
    
    /**
    * Возвращает список доступных единиц измерения веса
    * 
    * @return array
    */
    public static function getWeightUnits()
    {
        return array(
            self::WEIGHT_UNIT_G => array(
                'ratio' => 1,
                'title' => t('грамм'),
                'short_title' => t('г', null, 'сокращение "грамм"'),
            ),
            self::WEIGHT_UNIT_KG => array(
                'ratio' => 1000,
                'title' => t('килограмм'),
                'short_title' => t('кг', null, 'сокращение "килограмм"'),
            ),
            self::WEIGHT_UNIT_T => array(
                'ratio' => 1000000,
                'title' => t('тонна'),
                'short_title' => t('т', null, 'сокращение "тонна"'),
            ),
            self::WEIGHT_UNIT_LB => array(
                'ratio' => 453.59237,
                'title' => t('английский фунт'),
                'short_title' => t('lb', null, 'сокращение "фунт" (английский)'),
            ),
        );
    }
    
    /**
    * Возвращает список наименований доступных единиц измерения веса
    * 
    * @return array
    */
    public static function getWeightUnitsTitles()
    {
        $list = array();
        foreach (self::getWeightUnits() as $key=>$unit) {
            $list[$key] = $unit['title'];
        }
        return $list;
    }
}
