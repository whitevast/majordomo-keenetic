<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='keenetic_routers';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if($rec){
	if ($rec['STATUS'] and $rec['FIRMWARE'] != $rec['NEW_FIRMWARE']){
		$link = $this->getdata($rec,"webhelp/release-notes",'{"version": "'.$rec['NEW_FIRMWARE'].'", "locale": "ru"}');
		while(!isset($link['webhelp']['ru'][0]['href'])){
			usleep(300);
			$link = $this->getdata($rec, "webhelp/release-notes");
		}
		$rec['HREF'] = $link['webhelp']['ru'][0]['href'];
	}
  }
if ($this->tab=='') {
  if ($this->mode=='update') {
   $ok=1;
  // step: default
   $rec['TITLE']=gr('title');
   $rec['ADDRESS']=gr('address');
   $length = strlen($rec['ADDRESS']);
   while(strrpos($rec['ADDRESS'], "/") == $length-1){ //убираем символы "/" в конце
	$rec['ADDRESS']=substr($rec['ADDRESS'], 0, -1);
	$length = strlen($rec['ADDRESS']);
   } 
   while(strpos($rec['ADDRESS'], "/") !== false){ //убираем все до и символы "/" в начале
	$rec['ADDRESS']=substr($rec['ADDRESS'], strpos($rec['ADDRESS'], "/")+1);
   }  
   $rec['LOGIN']=gr('login');
   $rec['PASSWORD']=gr('password');
   if ($rec['TITLE']=='' or $rec['ADDRESS']=='' or $rec['LOGIN']=='' or $rec['PASSWORD']=='') {
    if($rec['TITLE']=='') $out['ERR_ALERT']="Введите название устройства";
	else if ($rec['ADDRESS']=='') $out['ERR_ALERT']="Введите адрес устройства";
	else if ($rec['LOGIN']=='') $out['ERR_ALERT']="Введите имя пользователя";
	else $out['ERR_ALERT']="Введите пароль";
    $ok=0;
   }
   if(!isset($rec['ID']) and isset(SQLSelectOne("SELECT * FROM $table_name WHERE TITLE='".$rec['TITLE']."'")['ID'])){
	   $out['ERR_ALERT']="Роутер с именем \"".$rec['TITLE']."\" уже существует в системе. Выберите другое имя.";
	   $ok=0;
   }
    if($ok){
		 $rec['COOKIES'] = $this->auth($rec['ADDRESS'],$rec['LOGIN'],$rec['PASSWORD']);
		 if($rec['COOKIES']){
			$data = $this->getdata($rec,"show",'{"version": {}, "identification": {}, "internet":{"status":{}}}');
			if($data['version']['model'] == "Keenetic") $rec['MODEL'] = $data['version']['device'];
			else $rec['MODEL'] = $data['version']['model'];
			$rec['FIRMWARE'] = $data['version']['release'];
			$rec['NEW_FIRMWARE'] = $rec['FIRMWARE'];
			$rec['SERIAL'] = $data['identification']['serial'];
			$rec['AUTO_REBOOT'] = gr('reboot') ? gr('reboot') : 0;
			$rec['REQ_PERIOD'] = gr('period') ? gr('period') : 5;
			$rec['STATUS'] = 1;
			$rec['INET_STATUS'] = $data['internet']['status']['internet'];
			$rec['UPDATED'] = date('Y-m-d H:i:s');
			$components = explode(",", $data['version']['ndw']['components']);
			foreach($components as $name) {
				if($name == 'mws') $rec['MWS'] = 1;
			}
			addClass($rec['TITLE'], "Keenetic");
		 }
		 else{
			$ok=0;
			$out['ERR_ALERT']="Введены неверные данные или устройство недоступно";
		 }
	}
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
	 if(isset($rec['HREF'])) unset($rec['HREF']);
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
	 $inet['TITLE'] = "Интернет";
	 $inet['MAC'] = "0.0.0.0.0.0";
	 $inet['IP'] = "0.0.0.0";
	 $inet['STATUS'] = $data['internet']['status']['internet'];
	 $inet['TYPE_CONNECT'] = 0;
	 $inet['REGISTERED'] = 1;
	 $inet['ROUTER_ID'] = $rec['ID'];
	 $inet['SCRIPT'] ='if(!$status){ //если интернет исчез;
	
}
else if($status == 1){ //если интернет есть и активно основное подключение;

}
else if($status > 1){ //если интернет есть и активно резервное подключение;

}';
	 $inet['UPDATED'] = date('Y-m-d H:i:s');
	 SQLInsert('keenetic_devices', $inet);
    }
    $out['OK']=1;
	//setGlobal('cycle_keeneticControl','restart');
   } else {
    $out['ERR']=1;
   }
  }
}
  // Вкладка устройств
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
   $wol_id = gr('wol_id');
   if ($wol_id) {
    $device = SQLSelectOne('SELECT * FROM keenetic_devices WHERE ID="'.$wol_id.'"');
    $this->wol($device['MAC']);
   }
   $register_id = gr('register_id');
   if ($register_id) {
    $device = SQLSelectOne('SELECT * FROM keenetic_devices WHERE ID="'.$register_id.'"');
	$this->getdata($rec, 'known/host', '{"mac": "'.$device['MAC'].'", "name": "'.$device['TITLE'].'"}', 1);
   }
   $delete_id = gr('delete_id');
   if ($delete_id) {
	$device = SQLSelectOne('SELECT * FROM keenetic_devices WHERE ID="'.$delete_id.'"');
	$this->getdata($rec, 'known/host', '{"mac": "'.$device['MAC'].'", "no": "true"}', 1);
    SQLExec("DELETE FROM keenetic_devices WHERE ID='".(int)$delete_id."'");
   }
   $sortby = gr('sortby');
   if ($sortby) $sort = $sortby;
   else $sort = "ID";
   //$out['SORTBY'] = $sortby_keenetic_lan_devices;
   $properties=SQLSelect("SELECT * FROM keenetic_devices WHERE ROUTER_ID='".$rec['ID']."' ORDER BY ".$sort);
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
	  $old_title=$properties[$i]['TITLE'];
	  $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
	  global ${'title'.$properties[$i]['ID']};
	  if($properties[$i]['TITLE'] != 'Интернет') $properties[$i]['TITLE']=trim(${'title'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      global ${'linked_method'.$properties[$i]['ID']};
      $properties[$i]['LINKED_METHOD']=trim(${'linked_method'.$properties[$i]['ID']});
	  // Если юзер удалил привязанные свойство и метод, но забыл про объект, то очищаем его.
      if ($properties[$i]['LINKED_OBJECT'] != '' && ($properties[$i]['LINKED_PROPERTY'] == '' && $properties[$i]['LINKED_METHOD'] == '')) {
          $properties[$i]['LINKED_OBJECT'] = '';
      }
      SQLUpdate('keenetic_devices', $properties[$i]);
	  if ($old_title != $properties[$i]['TITLE']){
		  $this->getdata($rec, 'known/host', '{"mac": "'.$properties[$i]['MAC'].'", "name": "'.$properties[$i]['TITLE'].'"}', 1);
	  }
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] || $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
   }
   $out['PROPERTIES']=$properties;  
  }
  //Настройка DNS
if ($this->tab=='dns') {
	$delete_domain = gr('delete_domain');
	if ($delete_domain) {
		$this->getdata($rec, 'ip/host', '{"domain": "'.$delete_domain.'", "no": "true"}', 1);
		$this->WriteLog('Из DNS удален домен: '.$delete_domain);
	}
	if ($this->mode=='update') {
		$ok = 1;
		$domain = gr('title_new');
		$ip = gr('ip_new');
		$resp = $this->getdata($rec, 'ip/host', '{"domain": "'.$domain.'", "address": "'.$ip.'"}', 1);
		//print_r($resp);
		if($resp['status']['0']['status'] == "error"){
			$ok = 0;
			$out['ERR']=1;
			$out['ERR_ALERT']="Неправильный IP адрес";
		}
		$this->WriteLog('В DNS добавлен домен: '.$domain . " " . $ip);
		if($ok) $out['OK']=1;
	}
	$data = $this->getdata($rec, '', '{"show": {"running-config": {}}}');
	$i = 0;
	foreach($data['show']['running-config']['message'] as $value){
		if(strpos($value, "ip host") !== false){
			$var = str_replace('ip host ', '', $value);
			if($var){
				$var = explode(' ', $var);
				$dns[$i]['TITLE'] = $var[0];
				$dns[$i]['IP'] = $var[1];
				$i++;
			}
		}
	}
   $out['DNS']=$dns;
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
 // print_r($out);
