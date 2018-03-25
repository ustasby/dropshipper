<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Model\ExportType\Avito;

use \Export\Model\Orm\ExportProfile as ExportProfile;
use \RS\Orm\Type as Type;

class Avito extends \Export\Model\ExportType\AbstractType
{
    public function _init()
    {
        return parent::_init()->append(array(
            '__help__' => new Type\Mixed(array(
                'description' => t(''),
                'visible' => true,  
                'template' => '%export%/form/exporttype/avito/help.tpl'
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
        return t('Avito');
    }
    
    /**
    * Возвращает описание типа экспорта для администратора. Возможен HTML
    * 
    * @return string
    */
    public function getDescription()
    {
        return t('Экспорт для сервиса "Avito Автозагрузка"');
    }
    
    /**
    * Возвращает идентификатор данного типа экспорта. (только англ. буквы)
    * 
    * @return string
    */
    public function getShortName()
    {
        return 'avito';
    }
    
    /**
    * Возвращает корневой тэг документа
    * 
    * @return string
    */
    protected function getRootTag()
    {
        return "Ads";
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
            new OfferType\ConsumerElectronics(),
        );
    }
    
    /**
    * Возвращает экспортированные данные (XML)
    * 
    * @param \Export\Model\Orm\ExportProfile $profile Профиль экспорта
    * @return string
    */
    public function export(ExportProfile $profile)
    {
        $writer = new \Export\Model\MyXMLWriter();
        $writer->openURI($profile->getCacheFilePath());
        $writer->setIndent(true);
        $writer->setIndentString("    ");
        $writer->startElement($this->getRootTag());
            $writer->writeAttribute('formatVersion', '3');
            $writer->writeAttribute('target', 'Avito.ru');
            
            $this->exportOffers($profile, $writer);
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
        return $profile->getCacheFilePath();
    }
}
