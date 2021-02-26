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
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
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
 if ((time() - gg('cycle_keeneticRun')) < 10 ) {
	$out['CYCLERUN'] = 1;
 } else {
	$out['CYCLERUN'] = 0;
 }
 $this->getConfig();
 $out['LOG_DEBMES']=$this->config['LOG_DEBMES'];
 $out['CYCLE_TIME']=$this->config['CYCLE_TIME'];
 if (!$out['CYCLE_TIME']) {
  $out['CYCLE_TIME']=5;
 }
 if ($this->view_mode=='update_settings') {
   global $log_debmes;
   global $cycle_time;
   $this->config['LOG_DEBMES']=$log_debmes;
   $this->config['CYCLE_TIME']=$cycle_time;
   $this->saveConfig();
   setGlobal('cycle_keeneticControl','restart');
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='keenetic_routers' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_keenetic_routers') {
   $this->search_keenetic_routers($out);
  }
  if ($this->view_mode=='edit_keenetic_routers') {
   $this->edit_keenetic_routers($out, $this->id);
  }
  if ($this->view_mode=='delete_keenetic_routers') {
   $this->delete_keenetic_routers($this->id);
   $this->redirect("?data_source=keenetic_routers");
  }
   if ($this->view_mode=='info_keenetic_devices') {
   $this->info_keenetic_devices($out, $this->id);
 }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='keenetic_devices') {
	 print 'keenetic_devices';
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
	if($rec['MAC'] == "0.0.0.0.0.0"){
		$interface = $this->getdata($router['ADDRESS'], $router['LOGIN'], $router['PASSWORD'], $router['COOKIES'], 'show/interface');
		$isp = $interface[$interface['ISP']['usedby']['0']];
		$uptime = $isp['uptime'];
		$rec['NAME'] = $isp['description'];
		$rec['ADDRESS'] = $isp['address'];
	}
	else{
		$data = $this->getdata($router['ADDRESS'], $router['LOGIN'], $router['PASSWORD'], $router['COOKIES'], '', '{"show": {"mws": {"member": {}}, "ip": {"hotspot": {"mac": "'.$rec['MAC'].'"}}}}');
		$host = $data['show']['ip']['hotspot']['host'][0];
		$interfaces = $data['show']['mws']['member'];
		foreach($interfaces as $value){;
			$interface[$value['cid']] = $value['known-host'];
		}
		$uptime = $host['uptime'];
		$rec['HOSTNAME'] = $host['hostname'];
		$rec['RXBYTES'] = round($host['rxbytes']/1024/1024, 1);
		$rec['TXBYTES'] = round($host['txbytes']/1024/1024, 1);
		if(isset($host['mws'])){
			$rec['ROUTER'] = $interface[$host['mws']['cid']];
			$rec['WIFI_MODE'] = $host['mws']['mode'];
			if(isset($host['mws']['_11'])){
				foreach($host['mws']['_11'] as $mode){
					$rec['WIFI_MODE'] = $rec['WIFI_MODE'].'/'.$mode;
				}
			}
			$rec['WIFI_MODE'] = $rec['WIFI_MODE'].' '.($host['mws']['txss']==1?'1x1':'2x2').' '.$host['mws']['ht'].'МГц '.$host['mws']['txrate'].'Мбит';
			$rec['RSSI'] = $host['mws']['rssi'];
		} else if(isset($host['ap'])){
			$rec['ROUTER'] = $router['MODEL'];
			$rec['WIFI_MODE'] = $host['mode'];
			if(isset($host['_11'])){
				foreach($host['_11'] as $mode){
					$rec['WIFI_MODE'] = $rec['WIFI_MODE'].'/'.$mode;
				}
			}
			$rec['WIFI_MODE'] = $rec['WIFI_MODE'].' '.($host['txss']==1?'1x1':'2x2').' '.$host['ht'].'МГц '.$host['txrate'].'Мбит';
			$rec['RSSI'] = $host['rssi'];
		}
	//print_r($rec);
	}
	$uptime = $this->seconds2times($uptime);
	$times_values = array('',':',':','д.','лет');
	for ($i = count($uptime)-1; $i >= 0; $i--) $rec['UPTIME'] = $rec['UPTIME'] . $uptime[$i] . $times_values[$i];
	
	if (is_array($rec)) {
		foreach($rec as $k=>$v) {
			if (!is_array($v)) {
				$rec[$k]=htmlspecialchars($v);
			}
		}
	}
	outHash($rec, $out);
	$out['LOG']=nl2br($rec['LOG']); 
//	print_r($out);
}
/**
* keenetic_routers delete record
*
* @access public
*/
 function delete_keenetic_routers($id) {
  $rec=SQLSelectOne("SELECT * FROM keenetic_routers WHERE ID='$id'");
  // some action for related tables
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
 function processCycle() {
 //$this->getConfig();

	$routers = SQLSelect("SELECT * FROM keenetic_routers");
 	foreach($routers as $val){
		$update = 0;
		$getdata = $this->getdata($val['ADDRESS'], $val['LOGIN'], $val['PASSWORD'], $val['COOKIES'], '', '{"show": {"version": {}, "identification": {}, "ip":{"hotspot":{}}, "internet":{"status":{}}}}');																		
		if(!$getdata){
			if($val['STATUS'] == 1){
				$val['STATUS'] = 0;
				$update = 1;
			}
		} 
		else {
			if($val['STATUS'] == 0) {
				$val['STATUS'] = 1;
				$update = 1;
			}
			if($val['FIRMWARE'] != $getdata['show']['version']['release']){
				$val['FIRMWARE'] != $getdata['show']['version']['release'];
				$update = 1;
			}
			if($getdata['show']['internet']['status']['internet'] != $val['INET_STATUS']){
				if($getdata['show']['internet']['status']['internet']){
					$val['INET_STATUS'] = 1;
					ClearTimeOut('KeeneticReboot');
				} else {
					if($val['AUTO_REBOOT'] !=0){
						print "TIMER START";
						setTimeOut('KeeneticReboot','include_once(DIR_MODULES . "keenetic/keenetic.class.php");$keenetic_module = new keenetic();$keenetic_module->reboot('.$val['ID'].');',$val['AUTO_REBOOT']);
					}
					$val['INET_STATUS'] = 0;
				}
				$array = SQLSelectOne('SELECT * FROM keenetic_devices WHERE IP="0.0.0.0" AND ROUTER_ID="'.$val['ID'].'"');
				$array['LOG'] = date('Y-m-d H:i:s')." Устройство ".$array['TITLE'].$text."\n".$array['LOG'];
						if(substr_count($array['LOG'], "\n") > 30){ //очищаем самые давние события, если их более 30
							$array['LOG'] = substr($array['LOG'], 0, strrpos(trim($array['LOG']), "\n"));
						}
				$array['STATUS'] = $val['INET_STATUS'];
				$array['UPDATED'] = date('Y-m-d H:i:s');
				SQLUpdate('keenetic_devices', $array); //обновляем статус в таблице устройств
				$this->setProperty($array, $array['STATUS']);//обновляем свойство
				$update = 1;
			}

			//Предобработка списка устройств
			$devices = $getdata['show']['ip']['hotspot']['host'];
			foreach ($devices as $valuedev){
				if($valuedev['name'] == "") $valuedev['name'] = $valuedev['hostname'];
				$devmac[$valuedev['mac']] = $valuedev;
            }
			//Проверка изменений
			$devicesindb = SQLSelect("SELECT * FROM keenetic_devices WHERE ROUTER_ID='".$val['ID']."'");
			foreach ($devicesindb as $value){ //Если устройство из БД есть в устройствах, отданных роутером
				if(isset($devmac[$value['MAC']])){ //
					$text = "";
					if($value['STATUS'] != $devmac[$value['MAC']]['active']){
						$value['STATUS'] = (int)$devmac[$value['MAC']]['active'];
						$this->setProperty($value, $value['STATUS']);
						if($value['STATUS']) $text = " в сети";
						else $text = " не в сети";
						if($value['STATUS'] == 1){ //проверяем и изменяем тип подключения
							if(isset($devmac[$value['MAC']]['ap']) or isset($devmac[$value['MAC']]['mws'])){
								if($value['TYPE_CONNECT'] == 0) $value['TYPE_CONNECT'] = 1;
							} else {
								if($value['TYPE_CONNECT'] == 1) $value['TYPE_CONNECT'] = 0;
							}
						}
						$code = $value['SCRIPT'];
						if($code and $code !=""){
							$success = eval($code);
							if ($success === false) {
							$this->WriteLog("Ошибка в коде: ".$code);
							registerError('keenetic', "Error in code: " . $code);
						}
                }
					}
					if($value['IP'] != $devmac[$value['MAC']]['ip']){
						If($devmac[$value['MAC']]['ip'] != "0.0.0.0"){
							$value['IP'] = $devmac[$value['MAC']]['ip'];
							$text = ": IP изменен на ". $value['IP'].".";
						}
					}
					if($value['TITLE'] != $devmac[$value['MAC']]['name']){
						$value['TITLE'] = $devmac[$value['MAC']]['name'];
						$text = ": имя изменено на ". $value['TITLE'].".";
					}
					if($text != ""){
						$value['LOG'] = date('Y-m-d H:i:s')." Устройство ".$value['TITLE'].$text."\n".$value['LOG'];
						if(substr_count($value['LOG'], "\n") > 30){ //очищаем самые давние события, если их более 30
							$value['LOG'] = substr($value['LOG'], 0, strrpos(trim($value['LOG']), "\n"));
						}
						$value['UPDATED'] = date('Y-m-d H:i:s');
						SQLUpdate('keenetic_devices', $value);
					}
					unset($devmac[$value['MAC']]);
				} else { //Если устройства из БД нет в устройствах, отданных роутером, удаляем устройство из БД
					if($value['TITLE'] == "Интернет") continue;
					removeLinkedProperty($value['LINKED_OBJECT'], $value['LINKED_PROPERTY'], $this->name); //с очисткой привязок к объектам
					SQLExec("DELETE FROM keenetic_devices WHERE ID='".$value['ID']."'");
					$this->WriteLog("Устройство ".$value['TITLE']." удалено c ".$val['TITLE'].".");
				}
            }
			//Добавляем устройства, которых нет в БД, в БД
			foreach ($devmac as $value){
				print_r($value['MAC']);
				$new['TITLE'] = $value['name'];
				$new['MAC'] = $value['mac'];
				$new['IP'] = $value['ip'];
				$new['STATUS'] = (int)$value['active'];
				if(isset($value['ap']) or isset($value['mws']))	$new['TYPE_CONNECT'] = 1;
				else $new['TYPE_CONNECT'] = 0;
				$new['ROUTER_ID'] = $val['ID'];
				$new['UPDATED'] = date('Y-m-d H:i:s');
				SQLInsert('keenetic_devices', $new);
				$this->WriteLog("Устройство ".$new['TITLE']." добавлено на ".$val['TITLE'].".");
			}
		}
		if($update){
			$val['UPDATED'] = date('Y-m-d H:i:s');
			SQLUpdate('keenetic_routers', $val);
		}
	}
 }
 
 //Запись в привязанное свойство
function setProperty($line, $value){
        if ($line['LINKED_OBJECT'] && $line['LINKED_PROPERTY']) {
			setGlobal($line['LINKED_OBJECT'] . '.' . $line['LINKED_PROPERTY'], $value);
        }
		if ($line['LINKED_OBJECT'] && $line['LINKED_METHOD']) {
			$params['VALUE'] = $line['VALUE'];
			callMethodSafe($line['LINKED_OBJECT'] . '.' . $line['LINKED_METHOD'], $params);
        }
    }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
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
  $id = SQLSelect('SELECT ID FROM keenetic_routers');
  for($i=0; $i<count($id); $i++){
	$this->delete_keenetic_routers($id[$i]['ID']);
  }
  SQLExec('DROP TABLE IF EXISTS keenetic_routers');
  SQLExec('DROP TABLE IF EXISTS keenetic_devices');
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
 keenetic_routers: SERIAL varchar(20) NOT NULL DEFAULT ''
 keenetic_routers: STATUS boolean NOT NULL DEFAULT 0
 keenetic_routers: INET_STATUS boolean NOT NULL DEFAULT 0
 keenetic_routers: AUTO_REBOOT smallint unsigned NOT NULL DEFAULT 0
 keenetic_routers: UPDATED datetime
 keenetic_devices: ID int(10) unsigned NOT NULL auto_increment
 keenetic_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 keenetic_devices: MAC varchar(20) NOT NULL DEFAULT ''
 keenetic_devices: IP varchar(20) NOT NULL DEFAULT ''
 keenetic_devices: STATUS boolean NOT NULL DEFAULT 0
 keenetic_devices: TYPE_CONNECT varchar(10) NOT NULL DEFAULT ''
 keenetic_devices: ROUTER_ID int(10) NOT NULL DEFAULT '0'
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

 function getdata($ip, $login, $password, $cookies = "", $path = "", $data = "", $save = false){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://'.$ip."/rci/".$path);
	curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	if($data != ""){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/json;charset=UTF-8'));
	}
	$html = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Получаем HTTP-код
	curl_close($ch);
	if (!$html) return false;
	if($http_code == 401){
		$cookies = $this->auth($ip, $login, $password);
		if($cookies != -1 and $cookies != false){
			$array = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ADDRESS="'.$ip.'"');
			$array['COOKIES'] = $cookies;
			$array['UPDATED'] = date('Y-m-d H:i:s');
			$array['ID'] = SQLUpdate('keenetic_routers', $array); //обновляем куки в базе
			$html = $this->getdata($ip, $login, $password, $cookies, $path, $data); //повторяем запрос
			return $html;
		} else {
		$this->WriteLog("Ошибка отправки даных. http код: " . $http_code);
		return -1;
		}
	}
	if($save){
		$resp = $this->getdata($ip, $login, $password, $cookies, 'system/configuration/save', '{}');
		if($resp['message'] == "saving configuration...") $this->WriteLog("Конфигурация сохранена");
	}
	return json_decode($html, 1);
 }
 
 function auth($ip, $login, $password){
	$ch = curl_init('http://'.$ip.'/auth');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
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
	$pass = hash('sha256', $headers["X-NDM-Challenge"].md5($login.':'.$headers["X-NDM-Realm"].':'.$password));
	$post = '{"login": "'. $login . '", "password": "' . $pass . '"}';		
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/json;charset=UTF-8'));
	curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$html = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Получаем HTTP-код
	curl_close($ch);
	if (!$html) return -1;
	if ($http_code != 200){
		$this->WriteLog("Ошибка авторизации. ".$html);
		return false;
	}
	return $cookies;
 }
 
function reboot($id){
	$router = SQLSelectOne('SELECT * FROM keenetic_routers WHERE ID="'.$id.'"');
    $this->getdata($router['ADDRESS'], $router['LOGIN'], $router['PASSWORD'], $router['COOKIES'], 'system/reboot', '{}');
}
 
 function WriteLog($msg){
      if ($this->debug) {
         DebMes($msg, $this->name);
      }
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
function seconds2times($seconds)
{
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
}


/*
*
* TW9kdWxlIGNyZWF0ZWQgRmViIDA5LCAyMDIxIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
