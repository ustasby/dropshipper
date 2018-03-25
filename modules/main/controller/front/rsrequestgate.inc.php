<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Controller\Front;
use Main\Model\Orm\License;

/**
 * Контроллер обеспечивает ответы на входящие запросы от ReadyScript
 * Используется для работы сервисной инфраструктуры ReadyScript
 */
class RsRequestGate extends \RS\Controller\Front
{
    function init()
    {
        $action = strtolower($this->getAction());

        //Сверяем подпись домена, к которому производится запрос
        $domain_sign = base64_decode($this->url->request('domain_sign', TYPE_STRING));
        $domain = preg_replace('/^www\./', '', strtolower($this->url->server('HTTP_HOST')));

        $pubkeyfile = \Setup::$PATH.\Setup::$MODULE_FOLDER.'/main/model/keys/request_public.key';
        $pubkeyid = openssl_pkey_get_public("file://".$pubkeyfile);
        $ok = openssl_verify($domain.$action, $domain_sign, $pubkeyid);

        if (!$ok) {
            $this->e404(t('Неверная подпись'));
        }

        $this->wrapOutput(false);
    }

    /**
     * Возвращает мультисайты, которые присутствуют в системе
     */
    function actionGetMultisites()
    {
        $sites = \RS\Site\Manager::getSiteList();
        $result = array();
        foreach($sites as $site) {
            $result[] = array(
                'title' => $site['title'],
                'full_title' => $site['full_title'],
                'folder' => $site['folder'],
                'language' => $site['language'],
                'main_domain' => $site->getMainDomain(),
                'uniq_hash' => $site->getSiteHash(),
                'absolute_root_url' => $site->getMainDomain().$site->getRootUrl(),
                'use_ssl' => $site['redirect_to_https']
            );
        }

        return json_encode(array(
            'response' => $result
        ));
    }

    /**
     * Возвращает SHA1 от основной лицензии
     */
    function actionGetMainLicenseHash()
    {
        if (defined('CLOUD_UNIQ')) {
            $response = array(
                'type' => 'cloud',
                'main_license_hash' => CLOUD_UNIQ
            );
        } else {
            $main_license = false;
            __GET_LICENSE_LIST($main_license);

            $response = array(
                'type' => 'license',
                'main_license_hash' => $main_license ? sha1(str_replace('-', '', $main_license['license_key'])) : false
            );
        }

        return json_encode(array(
            'response' => $response
        ));
    }

    /**
     * Обновляет сведения по лицензии
     */
    function actionLicenseRefresh()
    {
        if (!defined('CLOUD_UNIQ')) {
            $license_hash = $this->url->request('license_hash', TYPE_STRING);

            $license = \RS\Orm\Request::make()
                ->from(new License())
                ->where("SHA1(license) = '#hash'", array(
                    'hash' => $license_hash
                ))
                ->object();

            if ($license['license']) {
                if ($license->refresh()) {
                    return json_encode(array(
                        'response' => array(
                            'success' => true
                        )
                    ));
                } else {
                    $error = array(
                        'message' => $license->getErrorsStr()
                    );
                }
            } else {
                $error = array(
                    'message' => t('Лицензия не найдена')
                );
            }
        } else {
            $error = array(
                'message' => t('Функция недоступна в облаке')
            );
        }

        return json_encode(array(
            'response' => array(
                'success' => false,
                'error' => $error
            )
        ));
    }
}