<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database', 'oldReadManifest', 'generateContent'];

if ($arraySoftwareState['tap']) {
  $arrayIncludes[] = 'tap';
}

foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent();

if ($arraySoftwareState['tap']) {
  $moduleTap = new classTap();
  $moduleTap->execute();
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Assign HTTP GET arguments to the software state
$arraySoftwareState['requestAPIScope'] = funcUnifiedVariable('get', 'type');
$arraySoftwareState['requestAPIFunction'] = funcUnifiedVariable('get', 'request');
$arraySoftwareState['requestAPISearchQuery'] = funcUnifiedVariable('get', 'q');
$arraySoftwareState['requestAPISearchGUID'] = funcUnifiedVariable('get', 'addonguid');

// --------------------------------------------------------------------------------------------------------------------

// Sanity
if (!$arraySoftwareState['requestAPIScope'] ||
    !$arraySoftwareState['requestAPIFunction']) {
  funcError('Missing minimum arguments (type or request)');
}

// --------------------------------------------------------------------------------------------------------------------

if ($arraySoftwareState['requestAPIScope'] == 'internal') {
  switch ($arraySoftwareState['requestAPIFunction']) {
    case 'search':
      $searchManifest = $moduleReadManifest->getAddons('api-search', $arraySoftwareState['requestAPISearchQuery'], 1);
      $moduleGenerateContent->amSearch($searchManifest);
    case 'get':
      if (!$arraySoftwareState['requestAPISearchGUID']) {
        $moduleGenerateContent->amSearch(null);
      }

      $arraySoftwareState['requestAPISearchGUID'] = explode(',', $arraySoftwareState['requestAPISearchGUID']);

      $searchManifest = $moduleReadManifest->getAddons('api-get', $arraySoftwareState['requestAPISearchGUID'], 2);
      $moduleGenerateContent->amSearch($searchManifest);
    case 'recommended':
      // This is apperently not used anymore but provide an empty response
      funcSendHeader('xml');
      print('<?xml version="1.0" encoding="utf-8" ?>' . NEW_LINE . '<addons />');
      exit();
    default:
      funcError('Unknown Internal Request');
  }
}
elseif ($arraySoftwareState['requestAPIScope'] == 'external') {
  switch ($arraySoftwareState['requestAPIFunction']) {
    case 'search':
      funcRedirect(
        '/search/?terms=' . $arraySoftwareState['requestAPISearchQuery']
      );
    case 'themes':
      funcRedirect('/themes/');
    case 'searchplugins':
      funcRedirect('/search-plugins/');
    case 'devtools':
      funcRedirect('/extensions/web-development/');
    case 'recommended':
    default:
      funcRedirect('/');
  }
}

// ====================================================================================================================

?>
