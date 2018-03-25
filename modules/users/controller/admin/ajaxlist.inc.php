<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Users\Controller\Admin;

/**
* Контроллер отдает список пользователей для компонента JQuery AutoComplete
* @ingroup Users
*/
class AjaxList extends \RS\Controller\Admin\Front
{
    public
        $api;


    function init()
    {
        $this->api = new \Users\Model\Api();
    }


	function actionAjaxEmail()
	{
		$term = $this->url->request('term', TYPE_STRING);
		$api = $this->api;
		$list = $api->getLike($term, array('login', 'surname', 'name', 'company', 'company_inn', 'phone'));
		$json = array();
        $i=0;
		foreach ($list as $user)
		{
           if ($i >= 5) break;
            $i= $i+1;
			$json[] = array(
				'label' => $user['surname'].' '.$user['name'].' '.$user['midname'],
                'id' => $user['id'],
				'email' => $user['e_mail'],
				'desc' => t('Логин').':'.$user['login']. 
                    ($user['company'] ? t(" ; {$user['company']}(ИНН:{$user['company_inn']})") : '')
			);

		}
		
        
		return json_encode($json);
	}
}

