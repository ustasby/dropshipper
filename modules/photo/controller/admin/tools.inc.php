<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Photo\Controller\Admin;

/**
* Содержит действия по обслуживанию фотографий
*/
class Tools extends \RS\Controller\Admin\Front
{
    function actionAjaxDelUnlinkPhotos()
    {
        $api = new \Photo\Model\PhotoApi();
        $count = $api->deleteUnlinkedPhotos();
        return $this->result
            ->setSuccess(true)
            ->addMessage(t('Удалено %0 не связанных фото', array($count)));
    }
    
    function actionAjaxDelPreviewPhotos()
    {
        $api = new \Photo\Model\PhotoApi();
        $api->deletePreviewPhotos();
        return $this->result
            ->setSuccess(true)
            ->addMessage(t('Миниатюры фотографий удалены'));        
    }
}