<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

if (!defined('ROOT_PATH')) {
  define('SOFTWARE_NAME', 'Compy');
  define('SOFTWARE_VERSION', '386');
  require_once('./globalFunctions.php');
  $strTitle = 'strongbad_email.exe';
  $strMessage = 'ert+' . "\n" .
                'y76p; \'0lu8jykee;u4p;e\'/Rh' . "\n" .
                'Strong ba15456`-------++++++gf' . "\n" .
                '+++++-//==========/*8901ikg' . "\n\n" .
                'a>_';
  gfGenContent($strTitle, $strMessage, true);
}

$strOfflineMessage = 'Phoebus, and by extension this Add-ons Site, is currently unavailable. Please try again later.';

if (contains(SOFTWARE_VERSION, 'a') || contains(SOFTWARE_VERSION, 'b') ||
    contains(SOFTWARE_VERSION, 'pre') || $gaRuntime['debugMode']) {
  $strOfflineMessage = 'This in-development version of Phoebus is not for public consumption. Please try a live Add-ons Site!<br /><br /></li>';

  foreach (TARGET_APPLICATION as $_value) {
    if ($_value['enabled']) {
      $strOfflineMessage = $strOfflineMessage . '<li><a href="http://' . $_value['domain']['live'] . '">' . $_value['name'] . ' Add-ons Site</a></li>';
    }
  }

  $strOfflineMessage = substr($strOfflineMessage, 0, -3);
}

// Root (/) won't set a component or path
if (!$gaRuntime['requestComponent'] && !$gaRuntime['requestPath']) {
  $gaRuntime['requestComponent'] = 'site';
  $gaRuntime['requestPath'] = '/';
}

switch ($gaRuntime['requestComponent']) {
  case 'aus':
    gfHeader('xml');
    print('<?xml version="1.0" encoding="UTF-8"?><RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />');
    exit();
    break;
  case 'integration':
    $gaRuntime['requestAPIScope'] = gfSuperVar('get', 'type');
    $gaRuntime['requestAPIFunction'] = gfSuperVar('get', 'request');
    if ($gaRuntime['requestAPIScope'] != 'internal') {
      gfHeader('404');
    }
    switch ($gaRuntime['requestAPIFunction']) {
      case 'search':
        gfHeader('xml');
        print('<?xml version="1.0" encoding="utf-8" ?><searchresults total_results="0" />');
        exit();
        break;      
      case 'get':
      case 'recommended':
        gfHeader('xml');
        print('<?xml version="1.0" encoding="utf-8" ?><addons />');
        exit();
        break;
      default:
        gfHeader('404');
    }
    break;
  case 'discover': gfHeader(404);
  default: gfError($strOfflineMessage);
}

// ====================================================================================================================

?>