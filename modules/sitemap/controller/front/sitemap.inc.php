<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Sitemap\Controller\Front;

class Sitemap extends \RS\Controller\Front
{
    function actionIndex()
    {
        $this->wrapOutput(false);
        $site_id = $this->url->request('site_id', TYPE_INTEGER);
        $map_type = $this->url->request('type', TYPE_STRING);
        $api = new \Sitemap\Model\Api($site_id, $map_type);
        $api->sitemapToOutput();
        return;
    }
}
?>
