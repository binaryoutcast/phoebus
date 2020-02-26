<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database', 'oldReadManifest', 'generateContent'];

if ($gaRuntime['tap']) {
  $arrayIncludes[] = 'tap';
}

foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent();

if ($gaRuntime['tap']) {
  $moduleTap = new classTap();
  $moduleTap->execute();
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Assign HTTP GET arguments to the software state
$gaRuntime['requestAPIScope'] = gfSuperVar('get', 'type');
$gaRuntime['requestAPIFunction'] = gfSuperVar('get', 'request');
$gaRuntime['requestAPISearchQuery'] = gfSuperVar('get', 'q');
$gaRuntime['requestAPISearchGUID'] = gfSuperVar('get', 'addonguid');

// --------------------------------------------------------------------------------------------------------------------

// Sanity
if (!$gaRuntime['requestAPIScope'] ||
    !$gaRuntime['requestAPIFunction']) {
  gfError('Missing minimum arguments (type or request)');
}

// --------------------------------------------------------------------------------------------------------------------

if ($gaRuntime['requestAPIScope'] == 'internal') {
  switch ($gaRuntime['requestAPIFunction']) {
    case 'search':
      $searchManifest = $moduleReadManifest->getAddons('api-search', $gaRuntime['requestAPISearchQuery'], 1);
      $moduleGenerateContent->amSearch($searchManifest);
    case 'get':
      if (!$gaRuntime['requestAPISearchGUID']) {
        $moduleGenerateContent->amSearch(null);
      }

      $gaRuntime['requestAPISearchGUID'] = explode(',', $gaRuntime['requestAPISearchGUID']);

      $searchManifest = $moduleReadManifest->getAddons('api-get', $gaRuntime['requestAPISearchGUID'], 2);
      $moduleGenerateContent->amSearch($searchManifest);
    case 'recommended':
      // This is apperently not used anymore but provide an empty response
      gfHeader('xml');
      print('<?xml version="1.0" encoding="utf-8" ?>' . NEW_LINE . '<addons />');
      exit();
    default:
      gfError('Unknown Internal Request');
  }
}
elseif ($gaRuntime['requestAPIScope'] == 'external') {
  switch ($gaRuntime['requestAPIFunction']) {
    case 'search':
      gfRedirect(
        '/search/?terms=' . $gaRuntime['requestAPISearchQuery']
      );
    case 'themes':
      gfRedirect('/themes/');
    case 'searchplugins':
      gfRedirect('/search-plugins/');
    case 'devtools':
      gfRedirect('/extensions/web-development/');
    case 'recommended':
    default:
      gfRedirect('/');
  }
}

// ====================================================================================================================

?>
