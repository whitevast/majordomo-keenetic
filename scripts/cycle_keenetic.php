<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'keenetic/keenetic.class.php');
$keenetic_module = new keenetic();
$keenetic_module->getConfig();
$tmp = SQLSelectOne("SELECT ID FROM keenetic_routers LIMIT 1");
if (!$tmp['ID'])
   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check=0;
if($keenetic_module->config['CYCLE_TIME'] != "") $checkEvery = $keenetic_module->config['CYCLE_TIME'];
else $checkEvery = 5;
$timeUpdate = 0;
//Добавляем отсутствующие столбцы в таблицу
$query = mysqli_fetch_all(SQLExec("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'keenetic_devices'"), MYSQLI_NUM);
$track = 1;
foreach($query as $name) {
	if($name[0] == 'TRACK') $track = 0;
}
if($track) SQLExec("ALTER TABLE keenetic_devices ADD COLUMN TRACK boolean NOT NULL DEFAULT 0 AFTER REGISTERED");
//
while (1)
{
   if(time() - $timeUpdate > 20){
     setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
	 $timeUpdate = time();
   }
   if ((time()-$latest_check)>=$checkEvery) {
    $latest_check=time();
    echo date('Y-m-d H:i:s').' Polling devices...';
    $keenetic_module->processCycle();
   }
   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      $db->Disconnect();
      exit;
   }
   sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
