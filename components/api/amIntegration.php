<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
gfImportModules('database', 'readManifest', 'generateContent');

// Assign HTTP GET arguments to the software state
$gaRuntime['qAPIScope']       = gfSuperVar('get', 'type');
$gaRuntime['qAPIFunction']    = gfSuperVar('get', 'request');
$gaRuntime['qAPISearchQuery'] = gfSuperVar('get', 'q');
$gaRuntime['qAPISearchGUID']  = gfSuperVar('get', 'addonguid');
$gaRuntime['qAPIVersion']     = gfSuperVar('get', 'version');

// ====================================================================================================================

// == | Main | ========================================================================================================

// Sanity
if (!$gaRuntime['qAPIScope'] ||
    !$gaRuntime['qAPIFunction']) {
  gfError('Missing minimum arguments (type or request)');
}

// --------------------------------------------------------------------------------------------------------------------

if ($gaRuntime['qAPIScope'] == 'internal') {
  switch ($gaRuntime['qAPIFunction']) {
    case 'search':
      if (!gfValidClientVersion(true, $gaRuntime['qAPIVersion'])) {
        $gmGenerateContent->amSearch();
      }
      $searchManifest = $gmReadManifest->getAddons('api-search', $gaRuntime['qAPISearchQuery'], 1);
      $gmGenerateContent->amSearch($searchManifest);
    case 'get':
      if (!gfValidClientVersion(true, $gaRuntime['qAPIVersion'])) {
        $gmGenerateContent->amSearch();
      }

      if (!$gaRuntime['qAPISearchGUID']) {
        $gmGenerateContent->amSearch();
      }

      $gaRuntime['qAPISearchGUID'] = explode(',', $gaRuntime['qAPISearchGUID']);

      $searchManifest = $gmReadManifest->getAddons('api-get', $gaRuntime['qAPISearchGUID'], 2);
      $gmGenerateContent->amSearch($searchManifest);
    case 'recommended':
      // This is apparently not used anymore but provide an empty response
      gfHeader('xml');
      print(XML_TAG . NEW_LINE . '<addons />');
      exit();
    default:
      gfError('Unknown Internal Request');
  }
}
elseif ($gaRuntime['qAPIScope'] == 'external') {
  switch ($gaRuntime['qAPIFunction']) {
    case 'search':
      gfRedirect(
        '/search/?terms=' . $gaRuntime['qAPISearchQuery']
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
