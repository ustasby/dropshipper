<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Yandex;

use \Export\Model\ExportType\AbstractType;
use \RS\Orm\Type;

class Yandex extends AbstractType 
{
    public function _init()
    {
        return parent::_init()->append(array(
            t('Основные'),
                'no_export_offers_title' => new Type\Integer(array(
                    'description' => t('Не выгружать названия комплектаций товаров'),
                    'checkboxview' => array(1,0),
                )),
                'delivery_options_cost' => new Type\Integer(array(
                    'description' => t('Максимальная стоимость доставки по вашему региону (delivery-options)'),
                    'hint' => t('Для указания бесплатной доставки, укажите 0<br>
                                элемент delivery-options попадает в выгрузку только при указании обоих его полей<br>
                                эти 2 поля являются обязательными, если вы хотите указывазать delivery-options у товаров')
                )),
                'delivery_options_days' => new Type\Varchar(array(
                    'description' => t('Срок доставки по вашему региону (delivery-options)'),
                    'hint' => t('Указывается в днях, например: 1 или 1-3<br>
                                элемент delivery-options попадает в выгрузку только при указании обоих его полей<br>
                                эти 2 поля являются обязательными, если вы хотите указывазать delivery-options у товаров')
                )),
        ));
    }
    
    /**
    * Возвращает название типа экспорта
    * 
    * @return string
    */
    public function getTitle()
    {
        return t('Яндекс.Маркет');
    }
    
    /**
    * Возвращает описание типа экспорта для администратора. Возможен HTML
    * 
    * @return string
    */
    public function getDescription()
    {
        return t('Экспорт в формате Яндекс.Маркет - YML');
    }
    
    /**
    * Возвращает идентификатор данного типа экспорта. (только англ. буквы)
    * 
    * @return string
    */
    public function getShortName()
    {
        return 'yandex';
    }
    
    /**
    * Возвращает список классов типов описания
    * 
    * @param string $export_type_name - идентификатор типа экспорта
    * @return \Export\Model\ExportType\AbstractOfferType[]
    */
    protected function getOfferTypesClasses()
    {
        return array(
            new OfferType\Simple(),
            new OfferType\VendorModel(),
            new OfferType\Book(),
            new OfferType\AudioBook(),
            new OfferType\ArtistTitle(),
        );
    }
    
    /**
    * Возвращает корневой тэг документа
    * 
    * @return string
    */
    protected function getRootTag()
    {
        return "yml_catalog";
    }
    
    /**
    * Возвращает экспортированные данные (XML)
    * 
    * @param \Export\Model\Orm\ExportProfile $profile Профиль экспорта
    * @return string
    */
    public function export(\Export\Model\Orm\ExportProfile $profile)
    {
        $writer = new \Export\Model\MyXMLWriter();  
        $writer->openURI($profile->getCacheFilePath());  
        $writer->startDocument('1.0', self::CHARSET);  
        $writer->setIndent(true);   
        $writer->setIndentString("    ");   
        $writer->startElement($this->getRootTag());  
            $writer->writeAttribute('date', date('Y-m-d H:i'));  
            $writer->startElement("shop");  
                $writer->writeElement('name', \RS\Helper\Tools::teaser(\RS\Site\Manager::getSite()->title, 20, false));  
                $writer->writeElement('company', \RS\Config\Loader::getSiteConfig()->firm_name);  
                $writer->writeElement('url', \RS\Site\Manager::getSite()->getRootUrl(true));
                $writer->writeElement('platform', 'ReadyScript'); // идентификатор движка
                $this->exportCurrencies($profile, $writer);
                $this->exportCategories($profile, $writer);

                if ($profile->data['delivery_options_days'] != '' ) {
                    $writer->startElement('delivery-options');
                        $writer->startElement('option');
                            $writer->writeAttribute('cost', isset($profile->data['delivery_options_cost']) ? $profile->data['delivery_options_cost'] : 0);
                            $writer->writeAttribute('days', $profile->data['delivery_options_days']);
                        $writer->endElement();
                    $writer->endElement();
                }

                $writer->startElement('offers');
                    $this->exportOffers($profile, $writer);
                $writer->endElement();
            $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
        return file_get_contents($profile->getCacheFilePath());
    }

    /**
    * Возвращает массив идентификаторов выбранных пользователем групп товаров
    * 
    * @param \Export\Model\Orm\ExportProfile $profile
    * @return array
    */
    private function getSelectedProductGroupIds(\Export\Model\Orm\ExportProfile $profile)
    {
        //Возвращаем основные группы товаров, без спецкатегорий.
        $selected_product_ids = $this->getSelectedProductIds($profile);
        if(empty($selected_product_ids)) return array();
        $groups_ids = \RS\Orm\Request::make()
            ->select('maindir')
            ->from(new \Catalog\Model\Orm\Product())
            ->where(array('public' => 1))
            ->whereIn('id', $selected_product_ids)
            ->exec()
            ->fetchSelected(null, 'maindir');
        return array_unique($groups_ids);
    }

    /**
    * Возвращает массив выбранных пользователем групп товаров
    * 
    * @param \Export\Model\Orm\ExportProfile $profile
    * @return array
    */
    private function getSelectedProductGroups(\Export\Model\Orm\ExportProfile $profile)
    {
        $selected_product_group_ids = $this->getSelectedProductGroupIds($profile);
        if(empty($selected_product_group_ids)) return array();
        return \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Dir())
            ->whereIn('id', $selected_product_group_ids)
            ->objects(null, 'id');
    }
    
    /**
    * Экспорт Валют
    *
    * @param \Export\Model\Orm\ExportProfile $profile
    * @param \XMLWriter $writer
    */
    private function exportCurrencies(\Export\Model\Orm\ExportProfile $profile, \XMLWriter $writer)
    {
        $writer->startElement("currencies");
            $writer->startElement("currency");
                $writer->writeAttribute('id', \Catalog\Model\CurrencyApi::getDefaultCurrency()->title);  
                $writer->writeAttribute('rate', \Catalog\Model\CurrencyApi::getDefaultCurrency()->ratio);  
            $writer->endElement();
        $writer->endElement();
        $writer->flush();
    }

    /**
    * Экспорт Категорий
    * 
    * @param \Export\Model\Orm\ExportProfile $profile
    * @param \XMLWriter $writer
    */
    private function exportCategories(\Export\Model\Orm\ExportProfile $profile, \XMLWriter $writer)
    {
        $writer->startElement("categories");
            $groups = $this->getSelectedProductGroups($profile);
            
            // дополняем массив недостающими категориями
            foreach($groups as $group){
                while ($group->parent) {
                    $groupparent = $group->getParentDir();
                    if (!isset($groups[$groupparent['id']])) {
                        $groups[$groupparent['id']] = $groupparent;
                        $group = $groupparent;
                    } else {
                        break;
                    }
                }
            }

            foreach ($groups as $key => $row) {
                $param[$key]  = $row['id'];
            }
            array_multisort($param, SORT_NUMERIC, SORT_ASC, $groups); //сортирую по id

            foreach ($groups as $group) {
                $writer->startElement("category");
                    $writer->writeAttribute('id', $group->id);
                    if ($group->parent) {
                        $writer->writeAttribute('parentId', $group->parent);
                    }
                    $writer->text( ($group['export_name']) ? $group['export_name'] : $group['name'] );
                $writer->endElement();
            }
        $writer->endElement();
        $writer->flush();
    }
}
