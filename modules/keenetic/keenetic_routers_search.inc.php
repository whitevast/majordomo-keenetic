<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['keenetic_routers_qry'];
  } else {
   $session->data['keenetic_routers_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_keenetic_routers="ID DESC";
  $out['SORTBY']=$sortby_keenetic_routers;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM keenetic_routers WHERE $qry ORDER BY ".$sortby_keenetic_routers);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
