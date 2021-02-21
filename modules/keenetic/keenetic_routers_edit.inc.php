<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='keenetic_routers';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
   $rec['TITLE']=gr('title');
   $rec['ADDRESS']=gr('address');
   $rec['LOGIN']=gr('login');
   $rec['PASSWORD']=gr('password');
   $rec['AUTO_REBOOT']=gr('reboot');
   if ($rec['TITLE']=='' or $rec['ADDRESS']=='' or $rec['LOGIN']=='' or $rec['PASSWORD']=='') {
    if($rec['TITLE']=='') $out['ERR_ALERT']="Введите название устройства";
	else if ($rec['ADDRESS']=='') $out['ERR_ALERT']="Введите адрес устройства";
	else if ($rec['LOGIN']=='') $out['ERR_ALERT']="Введите имя пользователя";
	else $out['ERR_ALERT']="Введите пароль";
    $ok=0;
   }
    if($ok){
		 $rec['COOKIES'] = $this->auth($rec['ADDRESS'],$rec['LOGIN'],$rec['PASSWORD']);
		 if($rec['COOKIES']){
			$data = $this->getdata($rec['ADDRESS'],$rec['LOGIN'],$rec['PASSWORD'],$rec['COOKIES'],"show", '{"version": {}, "identification": {}, "internet":{"status":{}}}');
			if($data['version']['model'] == "Keenetic") $rec['MODEL'] = $data['version']['device'];
			else $rec['MODEL'] = $data['version']['model'];
			$rec['FIRMWARE'] = $data['version']['release'];
			$rec['SERIAL'] = $data['identification']['serial'];
			if(!gr('reboot')) $rec['AUTO_REBOOT'] = 0;
			$rec['STATUS'] = 1;
			$rec['INET_STATUS'] = $data['internet']['status']['internet'];
			$rec['UPDATED'] = date('Y-m-d H:i:s');
		 }
		 else{
			$ok=0;
			$out['ERR_ALERT']="Введены неверные данные или устройство недоступно";
		 }

	}
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
	 $inet['TITLE'] = "Интернет";
	 $inet['MAC'] = "0.0.0.0.0.0";
	 $inet['IP'] = "0.0.0.0";
	 $inet['STATUS'] = $data['internet']['status']['internet'];
	 $inet['TYPE_CONNECT'] = 0;
	 $inet['ROUTER_ID'] = $rec['ID'];
	 $inet['UPDATED'] = date('Y-m-d H:i:s');
	 SQLInsert('keenetic_devices', $inet);
    }
    $out['OK']=1;
	setGlobal('cycle_keeneticControl','restart');
   } else {
    $out['ERR']=1;
   }
  }
  // step: data
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM keenetic_devices WHERE ID='".(int)$delete_id."'");
   }
   global $sortby;
   if ($sortby) $sort = $sortby;
   else $sort = "ID";
   $out['SORTBY'] = $sortby_keenetic_lan_devices;
   $properties=SQLSelect("SELECT * FROM keenetic_devices WHERE ROUTER_ID='".$rec['ID']."' ORDER BY ".$sort);
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
	  $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      global ${'linked_method'.$properties[$i]['ID']};
      $properties[$i]['LINKED_METHOD']=trim(${'linked_method'.$properties[$i]['ID']});
      SQLUpdate('keenetic_devices', $properties[$i]);
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
   }
   $out['PROPERTIES']=$properties;   
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
