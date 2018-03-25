<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Marketplace\Controller\Front;

use Marketplace\Model\InstallApi;
use Marketplace\Model\SignApi;
use RS\Config\Loader;

class RemInstall extends \RS\Controller\Front
{
	function actionIndex()
	{
        $signApi = new SignApi();
        $installApi = new InstallApi();
        $config = Loader::byModule($this);

        $signature_result = $signApi->checkUploadRequest($_FILES, $_POST);
        if(!$signature_result){
            $this->displayErrorResponse($signApi->getErrors());
        }

        if(!$config['allow_remote_install']){
            $this->displayErrorResponse(array(t('Удаленная установка запрешена настройками безопасности')));
        }

        $installed = $installApi->installAddon($_FILES['file']['tmp_name']);

        if($installed)
            $this->displaySuccessResponse(t('Дополнение успешно установлено'));
        else
            $this->displayErrorResponse($installApi->getErrors());
	}

    private function displayErrorResponse(array $errors)
    {
        echo json_encode(array(
            'success'=> false,
            'errors' => $errors
        ));
        exit;
    }

    private function displaySuccessResponse($message)
    {
        echo json_encode(array(
            'success' => true,
            'message' => $message,
        ));
        exit;
    }

}

