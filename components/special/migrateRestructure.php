<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database', 'oldReadManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase        = new classDatabase();
$moduleReadManifest    = new classReadManifest();

// Request arguments
$strRequestSlug = gfSuperVar('get', 'slug');
$strRequestProcess = gfSuperVar('get', 'process');

// Inital vars
$arrayRestructuredData = [];
$arrayQueryStatements = [];

// ====================================================================================================================

// == | Functions | ===================================================================================================

// Restructure Physically Existing Add-ons
function funcRestructureAddons($aAddonManifest) {
  $addonBase = [];
  $addonMetadata = [];
  $addonVersions = [];

  // JSON Decode XPInstall
  if (!is_array($aAddonManifest['xpinstall'])) {
    $aAddonManifest['xpinstall'] = json_decode($aAddonManifest['xpinstall'], true);
  }

  // Extract data to build the addonBase table
  $arrayKeys = ['id', 'slug', 'type', 'active', 'reviewed'];

  foreach ($arrayKeys as $_value) {
    $addonBase[$_value] = $aAddonManifest[$_value];
  }

  $addonBase['releaseVersion'] = $aAddonManifest['xpinstall'][$aAddonManifest['releaseXPI']]['version'];

  // Extract data to build the addonMetadata table
  $arrayKeys = ['id', 'category', 'url', 'name', 'creator', 'description', 'content', 'homepageURL', 'supportURL', 'supportEmail', 'tags'];

  foreach ($arrayKeys as $_value) {
    $addonMetadata[$_value] = $aAddonManifest[$_value];
  }

  $addonMetadata['repositoryURL'] = $aAddonManifest['repository'];

  // Extract data to build the addonVersions table
  foreach ($aAddonManifest['xpinstall'] as $_key => $_value) {   
    $_bitwise = 0;
    $targetApplication = $_value['targetApplication'];

    if (array_key_exists(TARGET_APPLICATION['basilisk']['id'], $targetApplication)) {
      unset($targetApplication[TARGET_APPLICATION['basilisk']['id']]['basilisk']);
    }

    if (array_key_exists(TOOLKIT_ID, $targetApplication)) {
      $_bitwise = $_bitwise | TOOLKIT_BIT;
    }

    foreach (TARGET_APPLICATION as $_value2) {
      if (array_key_exists($_value2['id'], $targetApplication)) {
        $_bitwise = $_bitwise | $_value2['bit'];
      }
    }

    $_xpiFile = $aAddonManifest['slug'] . '-' . $_value['version'] . '.xpi';
    if ($_key != $_xpiFile) {
      $_xpiFile = $_key;
    }
    else {
      $_xpiFile = null;
    }
   
    $addonVersions[] = array(
      'id' => $aAddonManifest['id'],
      'thisVersion' => $_value['version'],
      'application' => $_bitwise,
      'epoch' => $_value['epoch'],
      'hash' => $_value['hash'],
      'licenseCode' => $aAddonManifest['license'],
      'licenseText' => $aAddonManifest['licenseText'],
      'licenseURL' => $aAddonManifest['licenseURL'],
      'xpi' => $_xpiFile,
      'targetApplication' => json_encode($targetApplication, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
  }
  

  return ['addonBase' => $addonBase, 'addonMetadata' => $addonMetadata, 'addonVersions' => $addonVersions];
}

// ====================================================================================================================

// == | Main | ========================================================================================================

if ($strRequestSlug) {
  $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $strRequestSlug);
  if (!$addonManifest || $addonManifest['type'] == 'external') {
    funcError('Unknown slug or is an external');
  }

  funcGenerateContent('Restructured Data', funcRestructureAddons($addonManifest));
}

// --------------------------------------------------------------------------------------------------------------------

// Build the statements to remove all rows from the tables
$dbs = ['addonBase', 'addonMetadata', 'addonVersions'];
$query = "DELETE FROM ?n";

foreach ($dbs as $_value) {
  $arrayQueryStatements[] = $moduleDatabase->query('parse', $query, $_value);
}

// --------------------------------------------------------------------------------------------------------------------

// Get physical add-ons
$query = "SELECT addon.*
          FROM `addon`
          WHERE `type` IN ('extension', 'theme', 'langpack')";

$result = $moduleDatabase->query('rows', $query);

// Restructure the data
foreach ($result as $_value) {
  $arrayRestructuredData[] = funcRestructureAddons($_value);
}

// Build the statements to insert the restructured add-on data into the tables
$query = "INSERT INTO ?n SET ?u";

foreach ($arrayRestructuredData as $_value) {
  $arrayQueryStatements[] = $moduleDatabase->query('parse', $query, 'addonBase', $_value['addonBase']);
  $arrayQueryStatements[] = $moduleDatabase->query('parse', $query, 'addonMetadata', $_value['addonMetadata']);

  foreach ($_value['addonVersions'] as $_value2) {
    $arrayQueryStatements[] = $moduleDatabase->query('parse', $query, 'addonVersions', $_value2);
  }
}

// --------------------------------------------------------------------------------------------------------------------

/*

// Get external add-ons
$query = "SELECT addon.*
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE `type` = 'external'";

$result = $moduleDatabase->query('rows', $query);

*/

// --------------------------------------------------------------------------------------------------------------------

// If we want to process then do it now
if ($strRequestProcess) {
  $result = $moduleDatabase->query('multiRaw', implode('; ', $arrayQueryStatements));

  if (!$result) {
    funcError('Something when very VERY wrong when executing the massive SQL operation...');
  }

  funcGenerateContent('Restructured Data - Query Statements', $result);
}

// --------------------------------------------------------------------------------------------------------------------


// Output an array containing every statement
funcGenerateContent('Restructured Data', $arrayRestructuredData);

// ====================================================================================================================

?>