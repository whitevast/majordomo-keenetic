<?php
/**
* Keenetic 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 04:02:11 [Feb 09, 2021])
*/
//
//
class keenetic extends module {
/**
* keenetic
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="keenetic";
  $this->title="Keenetic";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
  $this->getConfig();
  $this->debug = $this->config['LOG_DEBMES'] == 1 ? true : false;
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (isset($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (isset($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
  if (!isset($this->config['LOG_DEBMES'])){
	$this->config['LOG_DEBMES']=0;
	$this->saveConfig();
 }
 $out['LOG_DEBMES']=$this->config['LOG_DEBMES'];
 if ($this->view_mode=='update_settings') {
   $this->config['LOG_DEBMES']=gr('log_debmes');
   $this->saveConfig();
   setGlobal('cycle_keeneticControl','restart');
   $this->redirect("?");
 }
 if (isset($this->data_source) && !isset($_GET['data_source']) && !isset($_POST['data_source'])) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='keenetic_routers' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_keenetic_routers') {
   $this->search_keenetic_routers($out);
  }
  if ($this->view_mode=='edit_keenetic_routers') {
   $this->edit_keenetic_routers($out, $this->id ?? '');
  }
  if ($this->view_mode=='delete_keenetic_routers') {
   $this->delete_keenetic_routers($this->id);
   $this->redirect("?data_source=keenetic_routers");
  }
   if ($this->view_mode=='info_keenetic_devices') {
   $this->info_keenetic_devices($out, $this->id);
 }
 }
 if ($this->data_source=='keenetic_devices') {
  if ($this->view_mode=='' || $this->view_mode=='search_keenetic_devices') {
   $this->search_keenetic_devices($out);
  }
  if ($this->view_mode=='edit_keenetic_devices') {
   $this->edit_keenetic_devices($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}


function api($params) {
    $id = $params['id'];
    if ($this->isIP($id)) {
        $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ADDRESS="'.$id.'"');
    } else {
        $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.(int)$id.'"');
    }
    if (!isset($router['ID'])) return false;
    $data = $this->getdata($router, $params['path'], $params['data']);
    return $data;
}

/**
* keenetic_routers search
*
* @access public
*/
 function search_keenetic_routers(&$out) {
  require(dirname(__FILE__).'/keenetic_routers_search.inc.php');
 }
/**
* keenetic_routers edit/add
*
* @access public
*/
 function edit_keenetic_routers(&$out, $id) {
  require(dirname(__FILE__).'/keenetic_routers_edit.inc.php');
 }
 
 function info_keenetic_devices(&$out, $id) {
	$rec = SQLSelectOne('SELECT * FROM keenetic_devices WHERE ID="'.$id.'"');
	$router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.$rec['ROUTER_ID'].'"');
	$track_device = gr('track_device');
	if($track_device){
		if($track_device==2) $track_device=0;
		$ok = 1;
		if($track_device==1){
			if(!getObject($rec['TITLE'])) addClassObject($router['TITLE'], $rec['TITLE']);
			else {
				$ok=0;
				$rec['ERR']=1;
				$rec['ERR_ALERT'] = "Объект с именем \"" . $rec['TITLE'] . "\" уже существует в системе! Переименуйте объект или устройство на интернет-центре.";
				$track_device = 0;
			}
		}
		else $this->delete_object($rec['TITLE']);
		if($ok){
			$rec['TRACK']=(int)$track_device;
			SQLUpdate('keenetic_devices', $rec);
		}
	}
	//print_r($rec);
	if ($this->mode=='update') {
		$code = gr('code');
		$old_code=$rec['SCRIPT'];
		$rec['SCRIPT'] = $code;
		
		if ($rec['SCRIPT'] != '') {
			$errors = php_syntax_error($rec['SCRIPT']);
			if ($errors) {
				$out['ERR_LINE'] = preg_replace('/[^0-9]/', '', substr(stristr($errors, 'php on line '), 0, 18));
				$out['ERR_CODE'] = 1;
				$errorStr = explode('Parse error: ', htmlspecialchars(strip_tags(nl2br($errors))));
				$errorStr = explode('Errors parsing', $errorStr[1]);
				$errorStr = explode(' in ', $errorStr[0]);
				$out['ERRORS'] = $errorStr[0];
				$out['ERR_FULL'] = $errorStr[0].' '.$errorStr[1];
				$out['ERR_OLD_CODE'] = $old_code;
			}
		}
		$rec['SCRIPT']=$code;
		SQLUpdate('keenetic_devices', $rec);

	}
	if($rec['MAC'] == "0.0.0.0.0.0"){
		$interface = $this->getdata($router, 'show/interface');
		$isp = $this->backupWAN($interface);
		$uptime = $isp['UPTIME'];
		$rec['NAME'] = $isp['NAME'];
		$rec['ADDRESS'] = $isp['IP'];
	}
	else{
		if($router['MWS']) $mws =  '"mws": {"member": {}}, ';
		else $mws = "";
		$data = $this->getdata($router, '', '{"show": {'.$mws.'"ip": {"hotspot": {"mac": "'.$rec['MAC'].'"}}, "interface": {}}}');
		$host = $data['show']['ip']['hotspot']['host'][0];
		$interfaces = $router['MWS'] ? $data['show']['mws']['member'] : "";
		$wifies = $data['show']['interface'];
		$info = $this->parse_data($router, $host, $interfaces, $wifies);
		$rec = array_merge($rec, $info);
		$uptime = $rec['UPTIME'];
		unset($rec['UPTIME']);
		$rec['RXBYTES'] = round($rec['RXBYTES']/1024/1024, 1);
		$rec['TXBYTES'] = round($rec['TXBYTES']/1024/1024, 1);
		if($rec['ROUTER']) $rec['ROUTER'] = $rec['ROUTER'].' '.$rec['FREQ'].' ('.$rec['NET'].')';
	}
	
	$uptime = $this->seconds2times($uptime);
	$times_values = array('',':',':','д.','год');
	$rec['UPTIME'] = "";
	for ($i = count($uptime)-1; $i >= 0; $i--){
		if(strlen($uptime[$i]) == 1) $uptime[$i] = "0".$uptime[$i];
		$rec['UPTIME'] = $rec['UPTIME'] . $uptime[$i] . $times_values[$i];
	}
	
	if (is_array($rec)) {
		foreach($rec as $k=>$v) {
			if (!is_array($v)) {
				$rec[$k]=htmlspecialchars($v);
			}
		}
	}
	//	print_r($rec);
	outHash($rec, $out);
	$out['LOG']=nl2br($rec['LOG']);
	//print_r($out);
}
/**
* keenetic_routers delete record
*
* @access public
*/
 function delete_keenetic_routers($id) {
  $rec=SQLSelectOne("SELECT * FROM keenetic_routers WHERE ID='$id'");
  $this->delete_class($rec['TITLE']); //удаляем класс
  SQLExec("DELETE FROM keenetic_routers WHERE ID='".$rec['ID']."'");
  $properties=SQLSelect("SELECT * FROM keenetic_devices WHERE ROUTER_ID='".$rec['ID']."' AND LINKED_OBJECT != '' AND LINKED_PROPERTY != ''");
    foreach($properties as $prop) {
		removeLinkedProperty($prop['LINKED_OBJECT'], $prop['LINKED_PROPERTY'], $this->name);
	}
  SQLExec("DELETE FROM keenetic_devices WHERE ROUTER_ID='".$rec['ID']."'");
 }
/**
* keenetic_devices search
*
* @access public
*/
 function search_keenetic_devices(&$out) {
  require(dirname(__FILE__).'/keenetic_devices_search.inc.php');
 }
/**
* keenetic_devices edit/add
*
* @access public
*/
 function edit_keenetic_devices(&$out, $id) {
  require(dirname(__FILE__).'/keenetic_devices_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
   $table='keenetic_devices';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
    }
   }
 }
 function processSubscription($event, $details=''){
	 $this->getConfig();
	 if($event == 'HOURLY'){
		 $routers = SQLSelect("SELECT * FROM keenetic_routers");
		 foreach($routers as $router){
			 if($router['HREF_FW'] == 1) continue;
			 if($router['STATUS'] and $router['INET_STATUS']){
				 $firmware = $this->getdata($router, 'components/list', "{}");
				 //print_r($firmware['firmware']['version']);
				 if(!isset($firmware['firmware']['version'])){
					if(!timeOutExists('KeeneticWaitUpdate'))
						setTimeOut('KeeneticWaitUpdate',
						'include_once(DIR_MODULES . "keenetic/keenetic.class.php");
						$keenetic_module = new keenetic();
						$keenetic_module->processSubscription("HOURLY");'
						,10);
					return;
				 }
				 if($firmware['firmware']['version'] != $router['NEW_FIRMWARE']){
					$router['NEW_FIRMWARE'] = $firmware['firmware']['version'];
					//Запрвшиваем ссылку на изменения в прошивке
					$timeout = time() + 5; //Таймаут 5с
					$link = $this->getdata($router,"webhelp/release-notes",'{"version": "'.$router['NEW_FIRMWARE'].'", "locale": "ru"}');
					while(!isset($link['webhelp']['ru'][0]['href']) and $timeout > time()){
						usleep(300);
						$link = $this->getdata($router, "webhelp/release-notes");
					}
					$router['HREF_FW'] = $link['webhelp']['ru'][0]['href'] ?? "";
					SQLUpdate('keenetic_routers', $router);
					$this->WriteLog('Новая версия прошивки: '.$router['NEW_FIRMWARE']);
					if(method_exists($this, 'sendnotification')) {
						$this->sendnotification('Новая версия прошивки для '.$router['TITLE'].': '.$router['NEW_FIRMWARE'], 'danger');
					}
				 }
			 }
		 }
	 }
 }
 function processCycle() {
 //$this->getConfig();
	$routers = SQLSelect("SELECT * FROM keenetic_routers");
 	foreach($routers as $router){
		//print_r($router);
		if($router['REQ_PERIOD'] != 0 and time() - $router['REQ_UPDATE'] >= $router['REQ_PERIOD']){
			$update_router = 0;
			if($router['MWS']) $mws =  ', "mws": {"member": {}}';
			else $mws = "";
			$getdata = $this->getdata($router, '', '{"show": {"system": {}, "version": {}, "identification": {}, "ip":{"hotspot":{}}, "internet":{"status":{}}, "interface": {}'.$mws.'}}');
			if(!$getdata){
				if($router['STATUS'] == 1){
					$router['STATUS'] = 0;
					$update_router = 1;
				}
			} 
			else {
				if($router['STATUS'] == 0) {
					$router['STATUS'] = 1;
					$update_router = 1;
				}
				$components = explode(",", $getdata['show']['version']['ndw']['components']);
				$mws = 0;
				foreach($components as $name) {
					if($name == 'mws') $mws = 1;
				}
				if($router['MWS'] != $mws){
					$router['MWS'] = $mws;
					$update_router = 1;
				}
				if($router['FIRMWARE'] != $getdata['show']['version']['release']){
					$router['FIRMWARE'] = $getdata['show']['version']['release'];
					$this->WriteLog('Прошивка на '.$router['TITLE'].' обновлена на версию '.$router['FIRMWARE']);
					if(method_exists($this, 'sendnotification')) {
						$this->sendnotification('Прошивка на '.$router['TITLE'].' обновлена на версию '.$router['FIRMWARE'].'.', 'info');
					}
					$router['HREF_FW'] = '';
					$update_router = 1;
				}
				$log = "";
				$update = 0;
				if($getdata['show']['system']['uptime'] > 180){ //если после загрузки роутера прошло более трех минут (для того, чтобы успел поднять подключение к интернету)
					$inet = SQLSelectOne('SELECT * FROM keenetic_devices WHERE MAC="0.0.0.0.0.0" AND ROUTER_ID="'.$router['ID'].'"');
					if($getdata['show']['internet']['status']['internet'] != $router['INET_STATUS']){
						if($getdata['show']['internet']['status']['internet']){
							$router['INET_STATUS'] = 1;
							$log = "восстановлено.";
							ClearTimeOut('KeeneticReboot');
						} else {
							$log = "";
							if($router['AUTO_REBOOT'] != 0){
								$log = 'потеряно. Таймер перезагрузки на '.$router['AUTO_REBOOT'].' секунд активирован.';
								setTimeOut('KeeneticReboot','include_once(DIR_MODULES . "keenetic/keenetic.class.php");$keenetic_module = new keenetic();$keenetic_module->reboot('.$router['ID'].');',$router['AUTO_REBOOT']);
							} 
							else $log = 'потеряно.';
							$router['INET_STATUS'] = 0;
						}
						$inet['STATUS'] = $router['INET_STATUS'];
						$update = 1;
						$update_router = 1;
					}
					if($router['INET_STATUS']){ //если соединение с интернетом есть, проверяем через какой канал подключены
						$state = $this->backupWAN($getdata['show']['interface']);//получаем активный интерфейс и IP-адрес
						if($state['STATE']){
							if($inet['IP'] != $state['IP']){
								$inet['IP'] = $state['IP'];
								if($log != "") $log .=" IP адрес: ".$inet['IP'].".";
								else $log = "изменено. Новый IP: ".$inet['IP'].".";
								$update = 1;
							}
							if($inet['STATUS'] != $state['WAN']+1){
								$inet['STATUS'] = $state['WAN']+1;
								if($state['WAN']){
									if($log != "") $log .=" Включен резервный канал ".$state['WAN'].".";
									else $log = "переключено на резервный канал ".($state['WAN']+1).".";
								}
								else {
									if($log != "") $log .=" Включен основной канал.";
									else $log = "переключено на основной канал.";
								}
								$update = 1;
							}
						}
					}
				}
				if($update){
					$inet['LOG'] = date('Y-m-d H:i:s')." Соединение с интернетом ".$log."\n".$inet['LOG'];
						if(substr_count($inet['LOG'], "\n") > 30){ //очищаем самые давние события, если их более 30
							$inet['LOG'] = substr($inet['LOG'], 0, strrpos(trim($inet['LOG']), "\n"));
						}
					$this->WriteLog("Соединение с интернетом ".$log);
					$inet['UPDATED'] = date('Y-m-d H:i:s');
					SQLUpdate('keenetic_devices', $inet); //обновляем статус в таблице устройств
					$this->setProperty($inet, $inet['STATUS']);//обновляем свойство
					$status = (int)$inet['STATUS'];
					$code = SQLSelectOne("SELECT SCRIPT FROM keenetic_devices WHERE ROUTER_ID='".$router['ID']."' and TITLE='Интернет'" )['SCRIPT'];
						$errors = php_syntax_error($code);
						if ($errors){
							$line = preg_replace('/[^0-9]/', '', substr(stristr($errors, 'php on line '), 0, 18));
							$errorStr = explode('Parse error: ', htmlspecialchars(strip_tags(nl2br($errors))));
							$errorStr = explode('Errors parsing', $errorStr[1]);
							$errorStr = explode(' in ', $errorStr[0]);
							$errors = $errorStr[0].' on line '.$line;
							$this->WriteLog("Ошибка в коде: ".$code);
							registerError('Keenetic', "Error in code: " . $code. PHP_EOL . PHP_EOL . $errors . PHP_EOL);
						}
						else eval($code);
				}
				//Предобработка списка устройств
				$devices = $getdata['show']['ip']['hotspot']['host'];
				if(!is_array($devices)) {
					$this->WriteLog($devices);
				}
				foreach ($devices as $valuedev){
					if($valuedev['name'] == "") $valuedev['name'] = $valuedev['hostname'];
					if(!isset($valuedev['link'])) $valuedev['link'] = 0;
					else if($valuedev['link'] == "up") $valuedev['link'] = 1;
					else if($valuedev['link'] == "down") $valuedev['link'] = 0;
					if($valuedev['ip'] == "0.0.0.0") $valuedev['link'] = 0;
					$devmac[$valuedev['mac']] = $valuedev;
				}
				//Проверка изменений
				$devicesindb = SQLSelect("SELECT ID, ROUTER_ID, TITLE, MAC, IP, STATUS, TYPE_CONNECT, REGISTERED, TRACK, SCRIPT, LINKED_OBJECT, LINKED_PROPERTY, LINKED_METHOD, UPDATED FROM keenetic_devices WHERE ROUTER_ID='".$router['ID']."'");
				foreach ($devicesindb as $value){ //Если устройство из БД есть в устройствах, отданных роутером
					if(isset($devmac[$value['MAC']])){ //
						if($value['TRACK']){
							$host = $devmac[$value['MAC']];
							$interfaces = $router['MWS'] ? $getdata['show']['mws']['member'] : "";
							$wifies = $getdata['show']['interface'];
							$info = $this->parse_data($router, $host, $interfaces, $wifies);
							$object = 'Keenetic.'.$router['TITLE'].'.'.$value['TITLE'];
							callMethod($value['TITLE'].'.track', $info);
						}
						$log = "";
						if($value['IP'] != $devmac[$value['MAC']]['ip']){
							If($devmac[$value['MAC']]['ip'] != "0.0.0.0"){
								$value['IP'] = $devmac[$value['MAC']]['ip'];
								$log = ": IP изменен на ". $value['IP'].".";
							}
						}
						if($value['TITLE'] != $devmac[$value['MAC']]['name']){
							$value['TITLE'] = $devmac[$value['MAC']]['name'];
							$log = ": имя изменено на ". $value['TITLE'].".";
						}
						if($value['REGISTERED'] != $devmac[$value['MAC']]['registered']){
							$value['REGISTERED'] = (int)$devmac[$value['MAC']]['registered'];
							if($value['REGISTERED'])$log = " зарегистрировано на роутере.";
							else $log = ": регистрация с роутера удалена.";
						}
						if($value['STATUS'] != $devmac[$value['MAC']]['link']){
							if($devmac[$value['MAC']]['link'] == 1){ // если устройство подключилось, проверяем и изменяем тип подключения
								if(isset($devmac[$value['MAC']]['ap']) or isset($devmac[$value['MAC']]['mws'])){
									if($value['TYPE_CONNECT'] == 0) $value['TYPE_CONNECT'] = 1;
								} else {
									if($value['TYPE_CONNECT'] == 1) $value['TYPE_CONNECT'] = 0;
								}
							}
							else{ //дополнительно запрашиваем статус устройства, если устройство подключено проводом (с ВайФай ложных срабатываний не наблюдалось)
								if($value['TYPE_CONNECT'] == 0){
									$device = $this->getdata($router, '', '{"show":{"ip":{"hotspot":{"mac":"'.$value['MAC'].'"}}}}');
									if(!$device) continue;
									$device = $device['show']['ip']['hotspot']['host']['0'];
									//print_r($device);
									if(isset($device['link']) and $device['link'] == "up"){
										unset($devmac[$value['MAC']]); //удаляем устройства из массива, иначе оно будет считаться не числящимся в БД
										continue;
									}
								}
							}
							$device = $devmac[$value['MAC']];
							$status = (int)$devmac[$value['MAC']]['link'];
							$value['STATUS'] = (int)$devmac[$value['MAC']]['link'];
							$this->setProperty($value, $value['STATUS'], $device);
							if($value['STATUS']) $log = " в сети";
							else $log = " не в сети";
							$code = $value['SCRIPT'];
							$errors = php_syntax_error($code);
							if ($errors){
								$line = preg_replace('/[^0-9]/', '', substr(stristr($errors, 'php on line '), 0, 18));
								$errorStr = explode('Parse error: ', htmlspecialchars(strip_tags(nl2br($errors))));
								$errorStr = explode('Errors parsing', $errorStr[1]);
								$errorStr = explode(' in ', $errorStr[0]);
								$errors = $errorStr[0].' on line '.$line;
								$this->WriteLog("Ошибка в коде: ".$code);
								registerError('Keenetic', "Error in code: " . $code. PHP_EOL . PHP_EOL . $errors . PHP_EOL);
							}
							else eval($code);
							
						}
						if($log != ""){
							$value['LOG'] = date('Y-m-d H:i:s')." Устройство ".$value['TITLE'].$log."\n".SQLSelectOne('SELECT LOG FROM keenetic_devices WHERE MAC="'.$value['MAC'].'"')['LOG'];
							if(substr_count($value['LOG'], "\n") > 30){ //очищаем самые давние события, если их более 30
								$value['LOG'] = substr($value['LOG'], 0, strrpos(trim($value['LOG']), "\n"));
							}
							$value['UPDATED'] = date('Y-m-d H:i:s');
							SQLUpdate('keenetic_devices', $value);
						}
						unset($devmac[$value['MAC']]); //удаляем устройства из массива, иначе оно будет считаться не числящимся в БД
					} else { //Если устройства из БД нет в устройствах, отданных роутером, удаляем устройство из БД
						if($value['TITLE'] == "Интернет") continue;
						if($value['LINKED_OBJECT']) continue; //если есть привязанный объект, не удаляем
						SQLExec("DELETE FROM keenetic_devices WHERE ID='".$value['ID']."'");
						$this->WriteLog("Устройство ".$value['TITLE'].", MAC: ".$value['MAC']." удалено c ".$router['TITLE'].".");
					}
				}
				//Добавляем устройства, которых нет в БД, в БД
				foreach ($devmac as $value){
					$new['TITLE'] = $value['name'];
					$new['MAC'] = $value['mac'];
					$new['IP'] = $value['ip'];
					$new['STATUS'] = (int)$value['active'];
					$new['REGISTERED'] = (int)$value['registered'];
					if(isset($value['ap']) or isset($value['mws']))	$new['TYPE_CONNECT'] = 1;
					else $new['TYPE_CONNECT'] = 0;
					$new['ROUTER_ID'] = $router['ID'];
					$new['SCRIPT'] ='if($status){ //если устройство появилось в сети;
	
}
else{ //если устройство отключилось от сети;
	
}';
					$new['UPDATED'] = date('Y-m-d H:i:s');
					SQLInsert('keenetic_devices', $new);
					$this->WriteLog("Устройство ".$new['TITLE'].", MAC: ".$new['MAC']." добавлено на ".$router['TITLE'].".");
				}
				unset($devmac);
			}
			if($update_router) $router['UPDATED'] = date('Y-m-d H:i:s');
			unset($router['COOKIES']);
			$router['REQ_UPDATE'] = time();
			SQLUpdate('keenetic_routers', $router);
		}
	}
 }
 
 //Запись в привязанное свойство/метод
 function setProperty($device, $value, $params = []){
    if (isset($device['LINKED_OBJECT']) && isset($device['LINKED_PROPERTY'])) {
		setGlobal($device['LINKED_OBJECT'] . '.' . $device['LINKED_PROPERTY'], $value, array($this->name=>1), $this->name);
    }
	if (isset($device['LINKED_OBJECT']) && isset($device['LINKED_METHOD'])) {
		$params['VALUE'] = $value;
		callMethodSafe($device['LINKED_OBJECT'] . '.' . $device['LINKED_METHOD'], $params);
    }
}

// Глобальный поиск по модулю
 function findData($data) {
    $res = array();
	//Keenetic routers
    $routers = SQLSelect("SELECT ID, TITLE, MODEL FROM keenetic_routers where `TITLE` like '%" . DBSafe($data) . "%' OR `MODEL` like '%" . DBSafe($data) . "%' OR `ADDRESS` like '%" . DBSafe($data) . "%'  order by TITLE");
	foreach($routers as $router){
         $res[]= '&nbsp;<span class="label label-info">routers</span>&nbsp;<a href="/panel/keenetic.html?md=keenetic&inst=adm&view_mode=edit_keenetic_routers&id=' . $router['ID'] . '.html">' . $router['TITLE'].($router['MODEL'] ? '<small style="color: gray;padding-left: 5px;"><i class="glyphicon glyphicon-arrow-right" style="font-size: .8rem;vertical-align: text-top;color: lightgray;"></i> ' . $router['MODEL'] . '</small>' : ''). '</a>';
    }
      //Keenetic devices
    $devices = SQLSelect("SELECT ID, TITLE, IP, ROUTER_ID FROM keenetic_devices where `TITLE` like '%" . DBSafe($data) . "%' OR `MAC` like '%" . DBSafe($data) . "%' OR `IP` like '%" . DBSafe($data) . "%'  order by TITLE");
    foreach($devices as $device){
		$routr = SQLSelectOne('SELECT TITLE FROM keenetic_routers WHERE ID="'.$device['ROUTER_ID'].'"');
		$res[]= '&nbsp;<span class="label label-info">'.$routr['TITLE'].'</span>&nbsp;<span class="label label-primary">devices</span>&nbsp;<a href="/panel/keenetic.html?md=keenetic&inst=adm&view_mode=info_keenetic_devices&id=' . $device['ID'] . '.html">' . $device['TITLE']. ($device['IP'] ? '<small style="color: gray;padding-left: 5px;"><i class="glyphicon glyphicon-arrow-right" style="font-size: .8rem;vertical-align: text-top;color: lightgray;"></i> ' . $device['IP'] . '</small>' : '').'</a>';
    }
    return $res;
 }

/**
* Install\
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  subscribeToEvent($this->name, 'HOURLY');
  addClass('Keenetic');
  $code = '$this->setProperty("uptime", $params["UPTIME"]);
$this->setProperty("rxbytes", $params["RXBYTES"]);
$this->setProperty("txbytes", $params["TXBYTES"]);
$this->setProperty("router", $params["ROUTER"]);
$this->setProperty("net", $params["NET"]);
$this->setProperty("frequency", $params["FREQ"]);
$this->setProperty("mode", $params["WIFI_MODE"]);
$this->setProperty("rssi", $params["RSSI"]);';
  addClassMethod('Keenetic', 'track', $code);
  addClassProperty('Keenetic', 'uptime');
  addClassProperty('Keenetic', 'rxbytes');
  addClassProperty('Keenetic', 'txbytes');
  addClassProperty('Keenetic', 'router');
  addClassProperty('Keenetic', 'net');
  addClassProperty('Keenetic', 'frequency');
  addClassProperty('Keenetic', 'mode');
  addClassProperty('Keenetic', 'rssi');
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  unsubscribeFromEvent($this->name, 'HOURLY');
  $id = SQLSelect('SELECT ID FROM keenetic_routers');
  for($i=0; $i<count($id); $i++){
	$this->delete_keenetic_routers($id[$i]['ID']);
  }
  SQLExec('DROP TABLE IF EXISTS keenetic_routers');
  SQLExec('DROP TABLE IF EXISTS keenetic_devices');
  $this->delete_class("Keenetic");
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
keenetic_routers - 
keenetic_devices - 
*/
  $data = <<<EOD
 keenetic_routers: ID int(10) unsigned NOT NULL auto_increment
 keenetic_routers: TITLE varchar(100) NOT NULL DEFAULT ''
 keenetic_routers: ADDRESS varchar(50) NOT NULL DEFAULT ''
 keenetic_routers: MODEL varchar(20) NOT NULL DEFAULT ''
 keenetic_routers: LOGIN varchar(100) NOT NULL DEFAULT ''
 keenetic_routers: PASSWORD varchar(100) NOT NULL DEFAULT ''
 keenetic_routers: COOKIES varchar(100) NULL DEFAULT ''
 keenetic_routers: FIRMWARE varchar(20) NOT NULL DEFAULT ''
 keenetic_routers: NEW_FIRMWARE varchar(20) NOT NULL DEFAULT ''
 keenetic_routers: HREF_FW text NOT NULL DEFAULT ''
 keenetic_routers: SERIAL varchar(20) NOT NULL DEFAULT ''
 keenetic_routers: MWS boolean NOT NULL DEFAULT 0
 keenetic_routers: STATUS boolean NOT NULL DEFAULT 0
 keenetic_routers: INET_STATUS boolean NOT NULL DEFAULT 0
 keenetic_routers: AUTO_REBOOT smallint unsigned NOT NULL DEFAULT 0
 keenetic_routers: UPDATED datetime
 keenetic_routers: REQ_PERIOD smallint unsigned NOT NULL DEFAULT 5
 keenetic_routers: REQ_UPDATE int(10) unsigned NOT NULL DEFAULT 0
 keenetic_devices: ID int(10) unsigned NOT NULL auto_increment
 keenetic_devices: ROUTER_ID int(10) NOT NULL DEFAULT '0'
 keenetic_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 keenetic_devices: MAC varchar(20) NOT NULL DEFAULT ''
 keenetic_devices: IP varchar(20) NOT NULL DEFAULT ''
 keenetic_devices: STATUS boolean NOT NULL DEFAULT 0
 keenetic_devices: TYPE_CONNECT varchar(10) NOT NULL DEFAULT ''
 keenetic_devices: REGISTERED boolean NOT NULL DEFAULT 0
 keenetic_devices: TRACK boolean NOT NULL DEFAULT 0
 keenetic_devices: LOG text
 keenetic_devices: SCRIPT text
 keenetic_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 keenetic_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 keenetic_devices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 keenetic_devices: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------

/////////////////////////My_functions//////////////////////////////////

 function getdata($router, $path = "", $data = "", $save = false){
	$ip = $router['ADDRESS'];
	$login = $router['LOGIN'];
	$password = $router['PASSWORD'];
	$cookies = $router['COOKIES'];
	$prefix = "http://";
	if(!$this->isIP($ip))$prefix = "https://";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $prefix.$ip."/rci/".$path);
	curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	if($data != ""){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/json;charset=UTF-8'));
	}
	$html = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Получаем HTTP-код
	curl_close($ch);
	if (!$html) return false;
	if($http_code == 401 or $http_code == 403){
		$cookies = $this->auth($ip, $login, $password);
		if($cookies != -1 and $cookies != false){
			$array = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ADDRESS="'.$ip.'"');
			$array['COOKIES'] = $cookies;
			$array['UPDATED'] = date('Y-m-d H:i:s');
			$result = SQLUpdate('keenetic_routers', $array); //обновляем куки в базе
			$html = $this->getdata($array, $path, $data); //повторяем запрос
			return $html;
		} else {
		$this->WriteLog("Ошибка отправки даных. http код: " . $http_code);
		return false;
		}
	}
	if($http_code != 200) return false;
	if($save){
		$resp = $this->getdata($router, 'system/configuration/save', '{}');
		if($resp['status']['0']['message'] == "saving configuration...") $this->WriteLog("Конфигурация сохранена");
	}
	return json_decode($html, 1);
 }
 
function auth($ip, $login, $password){
	$prefix = "http://";
	$cookies = "";
	$password = $this->dsCrypt($password, true);
	if(!$this->isIP($ip))$prefix = "https://";
	$ch = curl_init($prefix.$ip.'/auth');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	//curl_setopt($ch, CURLOPT_NOBODY);
	curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	$html = curl_exec($ch);
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $html, $matches); //вытаскиваем куки
	$cookies = $matches['1']['0']; //сохраняем куки
	$headers = [];
	$data = explode("\r",$html);
	$headers['status'] = $data['0'];
	array_shift($data);
	foreach($data as $part){
		$middle=explode(":",$part);
		@$headers[trim($middle['0'])] = trim($middle['1']);
	}
	if(isset($headers['X-NDM-Challenge'])){
		$challenge = $headers['X-NDM-Challenge'];
		$realm = $headers['X-NDM-Realm'];
	}
	else{
		$challenge = $headers['x-ndm-challenge'];
		$realm = $headers['x-ndm-realm'];
	}
	$pass = hash('sha256', $challenge.md5($login.':'.$realm.':'.$password));
	$post = '{"login": "'. $login . '", "password": "' . $pass . '"}';		
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/json;charset=UTF-8'));
	curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$html = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Получаем HTTP-код
	curl_close($ch);
	if (!isset($html)) return -1;
	if ($http_code != 200){
		$this->WriteLog("Ошибка авторизации. ".$html);
		return false;
	}
	return $cookies;
}
 
function command($id, $data, $save=false){
	if ($this->isIP($id)) $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ADDRESS="'.$id.'"');
	else $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.$id.'"');
	$response = $this->getdata($router, '', $data, $save);
	return $response;
}
 
function reboot($id){
	if ($this->isIP($id)) $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ADDRESS="'.$id.'"');
	else $router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.$id.'"');
	$this->getdata($router, 'system/reboot', '{}');
}

function wol($mac){
	$oktets = explode(':', $mac);
	if(count($oktets) != 6) $devices = SQLSelect('SELECT * FROM keenetic_devices WHERE TITLE="'.$mac.'"'); //если это не mac-адрес
	else $devices = SQLSelect('SELECT * FROM keenetic_devices WHERE MAC="'.$mac.'"');
	if(count($devices) == 0) return false;
	foreach($devices as $device){
		$router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.$device['ROUTER_ID'].'"');
		$this->getdata($router, 'ip/hotspot/wake', '{"mac": "'.$device['MAC'].'"}');
	}
	return true;
}
 
function WriteLog($msg){
     if ($this->debug) {
        DebMes($msg, $this->name);
     }
  }
   
function isIP($address){
	$port = strpos($address, ':');
	if($port) $address = substr($address, 0, $port);
	$oktets = explode('.', $address);
	if(count($oktets) == 4){
		$num = 1;
		for($i=0; $i<4; $i++){
			if(!is_numeric($oktets[$i])) $num = 0;
		}
		if($num) return true;
	}
	return false;
}

function parse_data($router, $host, $interfaces, $wifies){
	$rec['UPTIME'] = $host['uptime'];
	$rec['HOSTNAME'] = $host['hostname'];
	$rec['RXBYTES'] = $host['rxbytes'];
	$rec['TXBYTES'] = $host['txbytes'];
	if(isset($host['mws'])){ //если подключено к экстендеру
		foreach($interfaces as $value){
			$interface[$value['cid']] = $value['known-host'];
		}
		$cidwifi = $host['mws']['ap'];
		$rec['ROUTER'] = $interface[$host['mws']['cid']]; //название роутера
		$rec['WIFI_MODE'] = $host['mws']['mode'];
		if(isset($host['mws']['_11'])){
			foreach($host['mws']['_11'] as $mode){
				$rec['WIFI_MODE'] = $rec['WIFI_MODE'].'/'.$mode; //стандарт
			}
		}
		$rec['WIFI_MODE'] = $rec['WIFI_MODE'].' '.($host['mws']['txss']==1?'1x1':'2x2').' '.$host['mws']['ht'].'МГц '.$host['mws']['txrate'].'Мбит'; //режми (стандарт, режми, частота, скорость)
		$rec['RSSI'] = $host['mws']['rssi']; //rssi
	} else if(isset($host['ap'])){ //если подключено к контроллеру
		$cidwifi = $host['ap'];
		$rec['ROUTER'] = $router['MODEL']; //название роутера
		$rec['WIFI_MODE'] = $host['mode'];
		if(isset($host['_11'])){
			foreach($host['_11'] as $mode){
				$rec['WIFI_MODE'] = $rec['WIFI_MODE'].'/'.$mode;
			}
		}
		$rec['WIFI_MODE'] = $rec['WIFI_MODE'].' '.($host['txss']==1?'1x1':'2x2').' '.$host['ht'].'МГц '.$host['txrate'].'Мбит'; //режми (стандарт, режми, частота, скорость)
		$rec['RSSI'] = $host['rssi']; //rssi
	}
	foreach($wifies as $iface){
		if($cidwifi == $iface['id']){
			$wifiap = explode("/", $iface['id']);
			$rec['FREQ'] = (int)substr($wifiap[0], -1)==1?'5ГГц':'2.4ГГц'; //Частота
			$rec['NET'] = $iface['ssid']; //Имя точки доступа
		}
	}
	return $rec;
}

function backupWAN($interface){
	$i=0;
	foreach($interface as $iface){
		if(isset($iface['global']) and $iface['global'] == "true"){
			$iifaces[$i] = $iface;
			$priority[$i] = $iface['priority'];
			$i++;
		}
	}
	rsort($priority);
	foreach($iifaces as $iface){
		$total = count($priority);
		for($i = 0; $i<$total; $i++){
			if($priority[$i] == $iface['priority']) $siface[$i] = $iface;
		}
	}
	$total = count($siface);
	$array['STATE'] = 0;
	for($i = 0; $i<$total; $i++){
		if($siface[$i]['connected'] == 'yes'){
			if($siface[$i]['defaultgw']){
				$array['STATE'] = 1;
				$array['NAME'] = $siface[$i]['description'];
				$array['UPTIME'] = $siface[$i]['uptime'];
				$array['IP'] = isset($siface[$i]['address']) ?? "";
				$array['WAN'] = $i;
				break;
			}
		}
	}
	//print_r($siface[$i]);
	return $array;
}
   /**
 * Преобразование секунд в секунды/минуты/часы/дни/года
 * 
 * @param int $seconds - секунды для преобразования
 *
 * @return array $times:
 *		$times[0] - секунды
 *		$times[1] - минуты
 *		$times[2] - часы
 *		$times[3] - дни
 *		$times[4] - года
 *
 */
function seconds2times($seconds){
	$times = array();
	
	// считать нули в значениях
	$count_zero = false;
	
	// количество секунд в году не учитывает високосный год
	// поэтому функция считает что в году 365 дней
	// секунд в минуте|часе|сутках|году
	$periods = array(60, 3600, 86400, 31536000);
	
	for ($i = 3; $i >= 0; $i--)
	{
		$period = floor($seconds/$periods[$i]);
		if (($period > 0) || ($period == 0 && $count_zero))
		{
			$times[$i+1] = $period;
			$seconds -= $period * $periods[$i];
			
			$count_zero = true;
		}
	}
	
	$times[0] = $seconds;
	return $times;
}
/*Обратимое шифрование методом "Двойного квадрата" (Reversible crypting of "Double square" method)
* @param  String $input   Строка с исходным текстом
* @param  bool   $decrypt Флаг для дешифрования
* @return String          Строка с результатом Шифрования|Дешифрования
* @author runcore*/

function dsCrypt($input,$decrypt=false) {
    $o = $s1 = $s2 = array(); // Arrays for: Output, Square1, Square2
    // формируем базовый массив с набором символов
    $basea = array('?','(','@',';','$','#',"]","&",'*'); // base symbol set
    $basea = array_merge($basea, range('a','z'), range('A','Z'), range(0,9) );
    $basea = array_merge($basea, array('!',')','_','+','|','%','/','[','.',' ') );
    $dimension=9; // of squares
    for($i=0;$i<$dimension;$i++) { // create Squares
        for($j=0;$j<$dimension;$j++) {
            $s1[$i][$j] = $basea[$i*$dimension+$j];
            $s2[$i][$j] = str_rot13($basea[($dimension*$dimension-1) - ($i*$dimension+$j)]);
        }
    }
    unset($basea);
    $m = floor(strlen($input)/2)*2; // !strlen%2
    $symbl = $m==strlen($input) ? '':$input[strlen($input)-1]; // last symbol (unpaired)
    $al = array();
    // crypt/uncrypt pairs of symbols
    for ($ii=0; $ii<$m; $ii+=2) {
        $symb1 = $symbn1 = strval($input[$ii]);
        $symb2 = $symbn2 = strval($input[$ii+1]);
        $a1 = $a2 = array();
        for($i=0;$i<$dimension;$i++) { // search symbols in Squares
            for($j=0;$j<$dimension;$j++) {
                if ($decrypt) {
                    if ($symb1===strval($s2[$i][$j]) ) $a1=array($i,$j);
                    if ($symb2===strval($s1[$i][$j]) ) $a2=array($i,$j);
                    if (!empty($symbl) && $symbl===strval($s2[$i][$j])) $al=array($i,$j);
                }
                else {
                    if ($symb1===strval($s1[$i][$j]) ) $a1=array($i,$j);
                    if ($symb2===strval($s2[$i][$j]) ) $a2=array($i,$j);
                    if (!empty($symbl) && $symbl===strval($s1[$i][$j])) $al=array($i,$j);
                }
            }
        }
        if (sizeof($a1) && sizeof($a2)) {
            $symbn1 = $decrypt ? $s1[$a1[0]][$a2[1]] : $s2[$a1[0]][$a2[1]];
            $symbn2 = $decrypt ? $s2[$a2[0]][$a1[1]] : $s1[$a2[0]][$a1[1]];
        }
        $o[] = $symbn1.$symbn2;
    }
    if (!empty($symbl) && sizeof($al)) // last symbol
        $o[] = $decrypt ? $s1[$al[1]][$al[0]] : $s2[$al[1]][$al[0]];
    return implode('',$o);
}


function delete_object($name){
	$obj=getObject($name);
	if(isset($obj)){
		include_once(DIR_MODULES . "objects/objects.class.php");
		$objects_module = new objects();
		$objects_module->delete_objects($obj->id);
	}
}
function delete_class($name){
	$class_id = SQLSelectOne("SELECT ID FROM classes WHERE TITLE='".$name."'"); // удаляем класс
	if(isset($class_id)){
		include_once(DIR_MODULES . "classes/classes.class.php");
		$classes_module = new classes();
		$classes_module->delete_classes($class_id['ID']);
	}
}
}


/*
*
* TW9kdWxlIGNyZWF0ZWQgRmViIDA5LCAyMDIxIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
