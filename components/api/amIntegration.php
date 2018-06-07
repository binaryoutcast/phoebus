<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | ===============================================================

// Include modules
$arrayIncludes = array('sql', 'sql-creds', 'readManifest', 'generateContent');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ============================================================================

// == | Main | ================================================================

// Assign HTTP GET arguments to the software state
$arraySoftwareState['requestAPIScope'] = funcHTTPGetValue('type');
$arraySoftwareState['requestAPIFunction'] = funcHTTPGetValue('request');
$arraySoftwareState['requestAPISearchQuery'] = funcHTTPGetValue('q');
$arraySoftwareState['requestAPISearchGUID'] = funcHTTPGetValue('addonguid');

// Instantiate modules
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent();

// ----------------------------------------------------------------------------

// Sanity
if (!$arraySoftwareState['requestAPIScope'] ||
    !$arraySoftwareState['requestAPIFunction']) {
  funcError('Missing minimum arguments (type or request)');
}

if ($arraySoftwareState['requestAPIScope'] == 'internal') {
  switch ($arraySoftwareState['requestAPIFunction']) {
    case 'search':
      $searchManifest = $moduleReadManifest->
        getSearchResults($arraySoftwareState['requestAPISearchQuery'], 1);
      $moduleGenerateContent->amSearch($searchManifest);
    case 'get':
      if (!$arraySoftwareState['requestAPISearchGUID']) {
        $moduleGenerateContent->amSearch(null);
      }

      $arraySoftwareState['requestAPISearchGUID'] = 
        explode(',', $arraySoftwareState['requestAPISearchGUID']);

      $searchManifest = $moduleReadManifest->
        getSearchResults($arraySoftwareState['requestAPISearchGUID'], 2);
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

// ============================================================================

?>