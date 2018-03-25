<?php
namespace StatusOrder\Controller\Front;

/**
* Фронт контроллер
*/
class Ctrl extends \RS\Controller\Front
{

	private $pochtaLogin = "",
			$pochtaPassword = "",
			$pochtaURL = "",
			$useService = 1;

	public $config;

    function actionIndex()
    {
    	//забираем настройки
    	$this->config = \RS\Config\Loader::byModule($this);
    	$this->pochtaLogin = $this->config->pochtaLogin;
    	$this->pochtaPassword = $this->config->pochtaPassword;
    	$this->pochtaURL = $this->config->pochtaURL;
    	
        $months = array( 'января' , 'февраля' , 'марта' , 'апреля' , 'мая' , 'июня' , 'июля' , 'августа' , 'сентября' , 'октября' , 'ноября' , 'декабря' );

    	$number = $this->request('order-number', TYPE_STRING);
        $site_id = \RS\Site\Manager::getSiteId();
        $user_id = 0;

        if($this->config->authFrom == 1) {
            $email = $this->request('email', TYPE_STRING);
            $user = \RS\Orm\Request::make()
                ->from(new \Users\Model\Orm\User())
                ->where(array(
                    'e_mail' => $email
                ))->object();

            $user_id = $user->id;
        }
        elseif($this->config->authFrom == 2) {
            $phone = $this->request('phone', TYPE_STRING);
            $user = \RS\Orm\Request::make()
                ->from(new \Users\Model\Orm\User())
                ->where(array(
                    'phone' => $phone
                ))->object();

            $user_id = $user->id;
        }

        $delivery = "";
    	
    	//Получаем заказ по номеру
        if($user_id > 0) {
        	$order  = \RS\Orm\Request::make()
                    ->select()
                    ->from(new \Shop\Model\Orm\Order)
                    ->where("order_num='#number' AND site_id='#site_id' AND user_id='#user_id'", 
                        array(
                            "number" => $number,
                            "site_id" => $site_id,
                            "user_id" => $user_id,
                    ))->objects();
        }
        else
        {
            $order  = \RS\Orm\Request::make()
                    ->select()
                    ->from(new \Shop\Model\Orm\Order)
                    ->where("order_num='#number' AND site_id='#site_id'", 
                        array(
                            "number" => $number,
                            "site_id" => $site_id,
                    ))->objects();   
        }

        if(!empty($order)) {
        	$status = $order[0]->getStatus();
        	
            if($order[0]->track_number != "") {
            	//Получаем данные о посылке
            	$track_number = $order[0]->track_number;
                
                if(preg_match("/".$this->config->cdekTpl."/", $track_number) and $this->config->cdekTpl != "") {
                	$delivery_id = 3;
                }

                if(preg_match("/".$this->config->boxberryTpl."/", $track_number) and $this->config->boxberryTpl != "") {
                    $delivery_id = 2;
                }

                if(preg_match("/".$this->config->pochtaTpl."/", $track_number) and $this->config->pochtaTpl != "") {
                	$delivery_id = 1;
                }

                if($delivery_id == 1) {
            	   
                    $delivery = "Почтой России";

                	if($this->pochtaLogin != "" and $this->pochtaPassword != "" and $this->pochtaURL != "" and $this->config->useServicePochta == 1) {
                	   	$wsdlurl = $this->pochtaURL;
        				$client2 = '';
        				$client2 = new \SoapClient($wsdlurl, array('trace' => 1, 'soap_version' => SOAP_1_2));

        				$params3 = array ('OperationHistoryRequest' => array ('Barcode' => $track_number, 'MessageType' => '0','Language' => 'RUS'),
        					  'AuthorizationHeader' => array ('login'=>$this->pochtaLogin,'password'=>$this->pochtaPassword));

        				$result = $client2->getOperationHistory(new \SoapParam($params3,'OperationHistoryRequest'));
                        $historyRecord = $result->OperationHistoryData->historyRecord;
                        if(count($historyRecord) > 1) {
                            unset($historyRecord[0]);
                            $out = array();
                            $i = 1;
                            foreach($historyRecord as $item) {
                                $day = date("d", strtotime($item->OperationParameters->OperDate));
                                $month = $months[date("n", strtotime($item->OperationParameters->OperDate))-1];
                                $year = date("Y", strtotime($item->OperationParameters->OperDate));

                                if(count($historyRecord) > 1) $status1 = "status-2";
                                if($item->OperationParameters->OperType->Name == "Вручение") $status1 = "status-3";
                                if(count($historyRecord) == $i) $status1 = "status-1";
                                $out[] = array(
                                    "date" => $day." ".$month." ".$year,
                                    "time" => date("H:i", strtotime($item->OperationParameters->OperDate)),
                                    "title" => $item->OperationParameters->OperAttr->Name,
                                    "address" => $item->AddressParameters->OperationAddress->Index." ".$item->AddressParameters->OperationAddress->Description,
                                    "status" => $status1,
                                    );
                                $i++;
                            }
                            $out = array_reverse($out);
                        }
        			}
        			else
        			{        	
        	        	if( $curl = curl_init() ) {
        	      			curl_setopt($curl, CURLOPT_URL, "https://gdeposylka.ru/courier/russian-post/tracking/".$track_number);
        	      			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        	      			$out = curl_exec($curl);
        	      			curl_close($curl);
        	    		}

        	    		preg_match('#<div class="checkpoints">(.+?)<\/div>#is', $out, $match);
                        $xml = simplexml_load_string($match[0]);
                        $data = $xml->ul->li;
                        $out = array();
                        foreach($data as $item) {
                            $day = date("d", strtotime($item->span[2]->time["datetime"]));
                            $month = $months[date("n", strtotime($item->span[2]->time["datetime"]))-1];
                            $year = date("Y", strtotime($item->span[2]->time["datetime"]));
                            $time = date("H:i", strtotime($item->span[2]->time["datetime"]));

                            if(count($data) > 1) $status1 = "status-2";
                            if(trim((string)$item->span[2]->strong) == "Посылка доставлена") $status1 = "status-3";
                            if(count($data) == $i) $status1 = "status-1";
                            $out[] = array(
                                "date" => $day." ".$month." ".$year,
                                "time" => $time,
                                "title" => trim((string)$item->span[2]->strong),
                                "address" => trim((string)$item->span[2]->em->a),
                                "status" => $status1,
                                );
                        }

        			}
                }
                elseif($delivery_id == 2) {

                    $delivery = "Boxberry";

                    $url = "http://api.boxberry.de/json.php?token=".$this->config->boxberryKey."&method=ListStatuses&ImId=".$track_number;
                    if( $curl = curl_init() ) {
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
                        $out = curl_exec($curl);
                        curl_close($curl);
                    }
                    $data = json_decode($out);
                    $out = array();
                    if(!isset($data[0]->err)) {
                        foreach($data as $item) {
                            $day = date("d", strtotime($item->Date));
                            $month = $months[date("n", strtotime($item->Date))-1];
                            $year = date("Y", strtotime($item->Date));
                            $time = date("H:i", strtotime($item->Date));

                            if(count($data) > 1) $status1 = "status-2";
                            if($item->Name == "Выдано") $status1 = "status-3";
                            if(count($data) == $i) $status1 = "status-1";
                            $out[] = array(
                                "date" => $day." ".$month." ".$year,
                                "time" => $time,
                                "title" => $item->Name,
                                "address" => " ",
                                "status" => $status1,
                                );
                        }
                        $out = array_reverse($out);
                    }
                }
                elseif($delivery_id == 3) {

                    $delivery = "СДЕК";

                    if($this->config->useServiceCDEK == 1 and $this->config->cdekLogin != "") {
                        //api cdek
                        $api_url = "http://gw.edostavka.ru:11443/";
                        //Подготовим XML
                        $sxml = new \XMLWriter();
                        $sxml->openMemory();
                        $sxml->startDocument('1.0','UTF-8');
                        $data_create = date('Y-m-d');
                        $sxml->startElement('StatusReport');
                        $sxml->writeAttribute('Date', $data_create);
                        $sxml->writeAttribute('Account', trim($this->config->cdekLogin)); //Id аккаунта
                        $sxml->writeAttribute('Secure', md5($data_create."&".trim($this->config->cdekPass))); //Генерируемый секретный ключ
                        $sxml->writeAttribute('ShowHistory', 1); //Показ истории заказа
                        
                        //Сведения об узнаваемом заказе
                        $sxml->startElement('Order');
                        $sxml->writeAttribute('DispatchNumber', $track_number);
                        //$sxml->writeAttribute('Date', date("Y-m-d", strtotime($order[0]["dateof"])));
                        $sxml->endElement();
                        $sxml->endElement();
                        $sxml->endDocument();
                        
                        $xml = $sxml->outputMemory(); //XML заказа
                        
                        $requst_array = array(
                            'http' => array(
                                'ignore_errors' => true, //Игнорируем ошибки(статусы ошибок) в заголовках, т.к. может быть 500 ошибка, но контент есть
                                'method'=> "POST",
                                'timeout' => 20
                            )
                        );
                        
                        $requst_array['http']['content'] = http_build_query(array("xml_request" => $xml));
                        
                        $ctx = stream_context_create($requst_array);
                        $url = $api_url."status_report_h.php";
                        
                        $response = @file_get_contents($url, null, $ctx);
                        $xml = simplexml_load_string($response);
                        $out = array();
                        foreach($xml->Order->Status->State as $item):
                        	if(in_array((int)$item["Code"], array(3,21,8,10,12,4,5)))
                        	{
                        		$status1 = "status-2";
                            	if($item["Code"] == 4) $status1 = "status-3";
                            	//if($item["Code"] == 3) $status1 = "status-1";
                        		$day = date("d", strtotime($item["Date"]));
	                            $month = $months[date("n", strtotime($item["Date"]))-1];
	                            $year = date("Y", strtotime($item["Date"]));
	                            $time = date("H:i", strtotime($item["Date"]));
                        		$out[] = array(
                        			"date" => $day." ".$month." ".$year,
	                                "time" => $time,
	                                "title" => trim((string)$item["Description"]),
	                                "address" => trim((string)$item["CityName"]),
	                                "status" => $status1,
                        			);
                        	}
                        endforeach;
        				$out = array_reverse($out);
                    }
                    else
                    {
                        if( $curl = curl_init() ) {
                            curl_setopt($curl, CURLOPT_URL, "https://gdeposylka.ru/courier/sdek/tracking/".$track_number);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
                            $out = curl_exec($curl);
                            curl_close($curl);
                        }

                        preg_match('#<div class="checkpoints">(.+?)<\/div>#is', $out, $match);
                        
                        $xml = simplexml_load_string($match[0]);
                        $data = $xml->ul->li;
                        $out = array();
                        foreach($data as $item) {
                            $day = date("d", strtotime($item->span[2]->time["datetime"]));
                            $month = $months[date("n", strtotime($item->span[2]->time["datetime"]))-1];
                            $year = date("Y", strtotime($item->span[2]->time["datetime"]));
                            $time = date("H:i", strtotime($item->span[2]->time["datetime"]));

                            if(count($data) > 1) $status1 = "status-2";
                            if(trim((string)$item->span[2]->strong) == "Посылка доставлена") $status1 = "status-3";
                            if(count($data) == $i) $status1 = "status-1";
                            $out[] = array(
                                "date" => $day." ".$month." ".$year,
                                "time" => $time,
                                "title" => trim((string)$item->span[2]->strong),
                                "address" => trim((string)$item->span[2]->em),
                                "status" => $status1,
                                );
                        }
                    }
                }
            }
        }
        else
        {
        	$status->bgcolor = "";
        	$status->title = "";
        	$out = "";
        }

        $this->view->assign(array(
        	"order_number" => $number,
        	"order_status" => $status->title,
        	"order_status_color" => $status->bgcolor,
            "delivery" => $delivery,
        	"tracking" => $out,
        	));

    	return $this->result->setTemplate('result.tpl');
    }
}
?>