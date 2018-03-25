<?php
namespace EvaSmart\Model;

use Catalog\Model\Orm\Brand;
use Catalog\Model\Orm\Dir;
use Catalog\Model\Orm\Offer;
use Catalog\Model\Orm\Product;
use Catalog\Model\Orm\Property\Item;
use Catalog\Model\Orm\Property\Link;
use Evasmart\Model\CsvSchema\Catalog;
use RS\AccessControl\Rights;
use RS\Helper\Tools;
use RS\Module\AbstractModel\BaseModel;
use RS\Orm\Request;
use RS\Site\Manager;

class ImportCsvApi extends BaseModel
{

    protected $allow_ext = array('csv');

    protected $csv_name = 'evasmart_catalog.csv';

    protected $tmp_data_file = 'eva_data.tmp';

    protected $log_name = 'eva_import.log';

    protected $csv_folder = '/storage/tmp/import_eva_csv';

    protected $csv_folder_rel = '/storage/tmp/import_eva_csv';

    protected $start_time;

    protected $site_id;

    protected $costId = 0;

    protected $categoryId;

    protected $log_file_rel;

    protected $log_file;

    protected $numberExcluding;

    private $countRow;

    private $cacheBrands = [];
    private $cacheCategories = [];

    private $warehouseId;

    private $currencyId;

    private $unitId;

    private $heightId, $widthId, $lengthId;

    function __construct()
    {
        $this->csv_folder = \Setup::$PATH . $this->csv_folder_rel;

        $config = \RS\Config\Loader::byModule('evasmart');
        $this->costId = $config['cost'];
        $this->unitId = $config['unit'];

        $this->currency = \Catalog\Model\Orm\Currency::loadByWhere(array(
            'site_id'   => \RS\Site\Manager::getSiteId(),
            'title'     => 'RUB',
        ))->id;

        $this->warehouseId = \Catalog\Model\WareHouseApi::getDefaultWareHouse()->id;

        $this->categoryId = $config['category'];

        $this->numberExcluding = array_map(function($a) {
            return trim($a);
        }, explode("\r\n", $config['number_excluding']));

        $this->heightId = $config['height'];
        $this->widthId = $config['width'];
        $this->lengthId = $config['length'];

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
     * Загружает данные из Csv файла
     *
     * @param type $file файл в формате Csv
     * @return boolean
     */
    function uploadFile($file)
    {
        //\RS\File\Tools::makePath($this->csv_folder);
        //\RS\File\Tools::deleteFolder($this->csv_folder, false);

        $uploader = new \RS\File\Uploader($this->allow_ext, $this->csv_folder);
        $uploader->setRightChecker(array($this, 'checkWriteRights'));

        if (!$uploader->setUploadFilename($this->csv_name)->uploadFile($file)) {
            return $this->addError($uploader->getErrorsStr());
        }

        return true;
    }

    /**
     *
     * @return void
     */
    function checkWriteRights()
    {
        return Rights::CheckRightError($this, ACCESS_BIT_WRITE);
    }


    function process()
    {


        return [
            'offset' => 10,
        ];

    }


    function stepDeleteProduct()
    {

    }

    function stepImport()
    {
        $file = $this->csv_folder . '/' . $this->csv_name;

        $catalog = new Catalog($this);
        $catalog->import($file);
        return array('step' => 2);
    }

    function importColumnsData($row)
    {
        $this->countRow++;

        $data = [
            'vendor_code' => $row['vendor_code'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'name' => $row['name'],
            'wholesale' => (int)str_replace(' ', '', $row['wholesale']),
            'desc' => $row['desc'],
            'fasteners' => $row['fasteners'],
            'note' => $row['note'],
            'weight' => 0
        ];

        if (in_array($data['vendor_code'], $this->numberExcluding)) {
            return;
        }

        // расчет на 7 мест
        if (mb_stripos($data['name'],'7 мест') !== false) {
            $price = 0;
            if ($data['wholesale'] > 0) {
                $price = intval($data['wholesale'] / 4);
                $price2 = $price / 2;
            }
            $data['weight'] = 2.4;
            $data['variants'][] = [
                'name' => 'Комплект в салон',
                'wholesale' => $data['wholesale'],
                'weight' => 2.4,
                'xml_id' => $data['vendor_code'],
                'barcode' => $data['vendor_code'] . '-1'
            ];

            $data['variants'][1]['name'] = 'Только два передних';
            $data['variants'][1]['wholesale'] = $price * 2;
            $data['variants'][1]['weight'] = 0.8;
            $data['variants'][1]['xml_id'] = $data['vendor_code'] . '#2';
            $data['variants'][1]['barcode'] = $data['vendor_code'] . '-2';

            $data['variants'][2]['name'] = 'Только водительский';
            $data['variants'][2]['wholesale'] = $price;
            $data['variants'][2]['weight'] = 0.4;
            $data['variants'][2]['xml_id'] = $data['vendor_code'] . '#3';
            $data['variants'][2]['barcode'] = $data['vendor_code'] . '-3';

            $data['variants'][3]['name'] = 'Только передний';
            $data['variants'][3]['wholesale'] = $price;
            $data['variants'][3]['weight'] = 0.4;
            $data['variants'][3]['xml_id'] = $data['vendor_code'] . '#4';
            $data['variants'][3]['barcode'] = $data['vendor_code'] . '-4';

            $data['variants'][4]['name'] = 'Только второй ряд';
            $data['variants'][4]['wholesale'] = $price;
            $data['variants'][4]['weight'] = 0.8;
            $data['variants'][4]['xml_id'] = $data['vendor_code'] . '#5';
            $data['variants'][4]['barcode'] = $data['vendor_code'] . '-5';

            $data['variants'][5]['name'] = 'Только третий ряд';
            $data['variants'][5]['wholesale'] = $price;
            $data['variants'][5]['weight'] = 0.8;
            $data['variants'][5]['xml_id'] = $data['vendor_code'] . '#7';
            $data['variants'][5]['barcode'] = $data['vendor_code'] . '-7';

        } elseif (mb_stripos($data['note'],'Только два передних') !== false) {
            $price = 0;
            if ($data['wholesale'] > 0) {
                $price = intval($data['wholesale'] / 2);
            }
            $data['weight'] = 1;
            $data['variants'][0]['name'] = 'Только два передних';
            $data['variants'][0]['wholesale'] = $data['wholesale'];
            $data['variants'][0]['weight'] = 1;
            $data['variants'][0]['xml_id'] = $data['vendor_code'];
            $data['variants'][0]['barcode'] = $data['vendor_code'];

            $data['variants'][2]['name'] = 'Только водительский';
            $data['variants'][2]['wholesale'] = $price;
            $data['variants'][2]['weight'] = 0.5;
            $data['variants'][2]['xml_id'] = $data['vendor_code'] . '#3';
            $data['variants'][2]['barcode'] = $data['vendor_code'] . '-3';

            $data['variants'][3]['name'] = 'Только передний';
            $data['variants'][3]['wholesale'] = $price;
            $data['variants'][3]['weight'] = 0.5;
            $data['variants'][3]['xml_id'] = $data['vendor_code'] . '#4';
            $data['variants'][3]['barcode'] = $data['vendor_code'] . '-4';


        // расчет багажник
        } else if (mb_stripos($data['name'],'багажник') !== false) {
            $data['variants'][0]['name'] = 'В багажник';
            $data['variants'][0]['wholesale'] = $data['wholesale'];
            $data['variants'][0]['weight'] = 1;
            $data['variants'][0]['xml_id'] = $data['vendor_code'];
            $data['variants'][0]['barcode'] = $data['vendor_code'];
            $data['weight'] = 1;
        // общий расчет
        } else {
            $price = 0;
            if ($data['wholesale'] > 0) {
                $price = intval($data['wholesale'] / 3);
                $price2 = intval($price / 3);
            }
            $data['weight'] = 1.6;
            $data['variants'][0]['name'] = 'Комплект в салон';
            $data['variants'][0]['wholesale'] = $data['wholesale'];
            $data['variants'][0]['weight'] = 1.6;
            $data['variants'][0]['xml_id'] = $data['vendor_code'] ;
            $data['variants'][0]['barcode'] = $data['vendor_code'] . '-1';

            $data['variants'][1]['name'] = 'Только два передних';
            $data['variants'][1]['wholesale'] = $price * 2;
            $data['variants'][1]['weight'] = 0.8;
            $data['variants'][1]['xml_id'] = $data['vendor_code'] . '#2';
            $data['variants'][1]['barcode'] = $data['vendor_code'] . '-2';

            $data['variants'][2]['name'] = 'Только водительский';
            $data['variants'][2]['wholesale'] = $price;
            $data['variants'][2]['weight'] = 0.4;
            $data['variants'][2]['xml_id'] = $data['vendor_code'] . '#3';
            $data['variants'][2]['barcode'] = $data['vendor_code'] . '-3';

            $data['variants'][3]['name'] = 'Только передний';
            $data['variants'][3]['wholesale'] = $price;
            $data['variants'][3]['weight'] = 0.3;
            $data['variants'][3]['xml_id'] = $data['vendor_code'] . '#4';
            $data['variants'][3]['barcode'] = $data['vendor_code'] . '-4';

            $data['variants'][4]['name'] = 'Только второй ряд';
            $data['variants'][4]['wholesale'] = $price;
            $data['variants'][4]['weight'] = 0.8;
            $data['variants'][4]['xml_id'] = $data['vendor_code'] . '#5';
            $data['variants'][4]['barcode'] = $data['vendor_code'] . '-5';

            if (mb_stripos($data['note'],'Без перемычки') !== false) {
                $data['variants'][5]['name'] = 'Перемычка второго ряда';
                $data['variants'][5]['wholesale'] = 150;
                $data['variants'][5]['weight'] = 0.1;
                $data['variants'][5]['xml_id'] = $data['vendor_code'] . '#8';
                $data['variants'][5]['barcode'] = $data['vendor_code'] . '-8';
            }

        }

        $this->updateProduct($data);




    }

    function updateProduct($data)
    {

        $product = Product::loadByWhere(
            array(
                'xml_id' => $data['vendor_code'],
                'site_id' => Manager::getSiteId(),
            )
        );

        if (!empty($product)) {
            $product = new Product();
        }

        $product->site_id   = Manager::getSiteId();
        $product->xml_id    = $data['vendor_code'];
        $product->public    = 1;
        $product->brand_id  = $this->getBrandIdByName($data['brand']);
        $product->title     = $data['name'];
        $product->barcode   = $data['vendor_code'];
        $product->unit = $this->unitId;
        $product->desc1 = $data['desc'];
        $product->desc2 = $data['fasteners'];
        $product->desc3 = $data['note'];

        $product->weight = $data['weight'];

        $product->type_product = 'mat';
        $product->num = 10000;

        $product->maindir = $this->categoryId;
        $product->xdir = [$this->categoryId, $this->getCategoryIdByName($data['brand'])];

        $product->alias = \RS\Helper\Transliteration::str2url(Tools::unEntityString($product->title), true, 140) . "-" . $data['vendor_code'];

        $on_duplicate_update_fields = array('barcode', 'title', 'public', 'processed', 'type_product', 'maindir');
        //$on_duplicate_update_fields = array_diff($on_duplicate_update_fields, []);

        // не трогать оффер
        $product->dont_save_offers = true;

        $product->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));

        // Если во время вставки произошла ошибка, то бросаем исключение
        if ($product->hasError()){
            throw new \Exception(join(", ", $product->getErrors()));
        }

        /*
        * высота 80
        * ширина 60
        * длинна 5
        * вес 1600
        * */
        $dimensions = [
            $this->heightId => 80,
            $this->widthId => 60,
            $this->lengthId => 5
        ];

        // комплектации
        $this->updateOffers($product, $data);

        // размеры
        foreach ($dimensions as $prop_id=>$value) {
            $pdata = [];
            $pdata['id'] = $prop_id;
            $pdata['public'] = 0;
            $pdata['site_id'] = Manager::getSiteId();
            $pdata['xml_id'] = '';
            $pdata['type'] = Item::TYPE_STRING;
            $this->updateProp($product, $value, $pdata);
        }

    }

    function updateProp($product, $value, $pdata)
    {
        Request::make()
            ->delete()
            ->from(new Link())
            ->where(array(
                'site_id' => $pdata['site_id'],
                'prop_id' => $pdata['id'],
                'product_id' => $product->id
            ))
            ->exec();
        $link = new Link();
        $link->fillData($product->id, $value, $pdata);
        $link->insert();

    }


    /**
     * @param $product Product
     * @param $data
     */
    function updateOffers($product, $data)
    {
        foreach ($data['variants'] as $variant) {
            $this->updateOffer($product, $variant);
        }
    }

    /**
     * @param $product Product
     * @param $variant
     * @internal param $data
     */
    function updateOffer($product, $variant)
    {

        $product_offer = new Offer();

        $product_offer->site_id     = Manager::getSiteId();
        $product_offer->product_id  = $product->id;
        $product_offer->title       = $variant['name'];
        $product_offer->barcode     = $variant['barcode'];
        $product_offer->num         = 10000;
        $product_offer->xml_id      = $variant['xml_id']; //Уникальный идентификатор в 1С
        $product_offer->processed   = 1;
        $product_offer->weight      = $variant['weight'];

        $pricedata = array();


        $pricedata[$this->costId] = array(
            'znak'  => '=',
            'original_value' => $variant['wholesale'],
            'unit'  => $this->currencyId
        );
        $product_offer->pricedata_arr = array('price' => $pricedata);
        $product_offer->stock_num = [$this->warehouseId => $product_offer['num']];
        $product_offer->unit = $this->unitId;
        $product_offer->propsdata = '';

        $on_duplicate_update_fields = ['title', 'barcode', 'pricedata', 'propsdata', 'num', 'processed', 'sku', 'weight'];

        $product_offer->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));

        $product_offer['sortn'] = Request::make()
            ->select('sortn')
            ->from(new Offer())
            ->where("id = {$product_offer->id}")
            ->exec()->getOneField('sortn');

        // если комплектация основная, вычесляется движком по # в xml_id
        if ($product_offer['sortn'] == 0) {
            $excost_array[$this->costId] = array(
                'cost_original_val'      => $variant['wholesale'],
                'cost_original_currency' => $this->currencyId
            );

            $product = Product::loadSingle($product->id);

            $product->excost = $excost_array;
            $product->update();
        }
    }


    function getProduct($id)
    {
        return Product::loadByWhere(
            array(
                'xml_id' => $id,
                'site_id' => Manager::getSiteId(),
            )
        );
    }

    private function getCategoryIdByName($categoryName)
    {
        if (!array_key_exists($categoryName, $this->cacheCategories)) {
            $dir = Dir::loadByWhere(array(
                'site_id'   => Manager::getSiteId(),
                'name'    => $categoryName,
            ));
            if (!$dir->id) {
                $dir = new Dir();
                $dir->site_id   = Manager::getSiteId();
                $dir->parent    = $this->categoryId;
                $dir->public    = 1;
                $dir->name      = Tools::toEntityString($categoryName);
                $dir->level = 1;

                $dir->alias = \RS\Helper\Transliteration::str2url($dir->name);
                $dir->insert(false);

                if (!$dir->id) {
                    $dir->id = $this->categoryId;
                    //throw new \Exception("no id");
                }
            }
            $this->cacheCategories[$categoryName] = $dir->id;
        }
        return $this->cacheCategories[$categoryName];
    }

    private function getBrandIdByName($brandName)
    {
        if(!array_key_exists($brandName, $this->cacheBrands)){
            $brand = Brand::loadByWhere(array(
                'site_id'   => Manager::getSiteId(),
                'title'    => $brandName,
            ));
            if(!$brand->id) {
                $brand['site_id'] = Manager::getSiteId();
                $brand['public'] = 1;
                $brand['xml_id'] = '';
                $brand['title'] = $brandName;
                $brand['alias'] = \RS\Helper\Transliteration::str2url($brandName);

                $same_aliases = Request::make()
                    ->select('alias')
                    ->from(new Brand())
                    ->where('alias like "#brand_alias%"', array('brand_alias' => $brand['alias']))
                    ->exec()->fetchSelected('alias', 'alias');
                if (in_array($brand['alias'], $same_aliases)) {
                    $counter = 2;
                    while(in_array($brand['alias'].$counter, $same_aliases)) {
                        $counter++;
                    }
                    $brand['alias'] .= $counter;
                }

                $brand->insert();
            }
            $this->cacheBrands[$brandName] = $brand->id;
        }
        return $this->cacheBrands[$brandName];
    }
}