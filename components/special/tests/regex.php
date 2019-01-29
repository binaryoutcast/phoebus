<?php

$regex = '/[^a-z0-9_\-]/';

$arrayUsernames = array(
  'mattatobin',
  'cabbage-12',
  'banana_24',
  'BEER',
  'vodka@(*#',
  '_vulcan',
  '-gamera'
);

$arrayFinalUsernames = [];

foreach ($arrayUsernames as $_value) {
  $arrayFinalUsernames[] = preg_replace($regex, '', $_value);
}

funcError([$arrayUsernames, $arrayFinalUsernames], 99);
?>