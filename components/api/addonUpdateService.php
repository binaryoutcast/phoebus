<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | INFO | ========================================================================================================

  // Automatic Update Service for Add-ons responds with a valid RDF file
  // containing update information for known add-ons or sends the request
  // off to AMO (for now) if it is unknown to us.

  // FULL GET Arguments for AUS are as follows:

  // [query]          [Description]       [Example]                       [Used]
  // ----------------------------------------------------------------------------
  // reqVersion       Request Version     '2'                             false
  // id               Add-on ID           '{GUID}' or 'user@host.tld'     true
  // version          Add-on Version      '1.2.3a1'                       amo
  // maxAppVersion                        '26.5.0'                        false
  // status           Add-on Status       'userEnabled'                   false
  // appID            Client ID           'toolkit@mozilla.org'           true
  // appVersion       Client Version      '27.2.1'                        true
  // appOS            Client OS           'WINNT'                         false
  // appABI           Client ABI          'x86-gcc3'                      false
  // locale           Client Locale       'en-US'                         false    
  // currentAppVersion                    '27.4.2'                        false
  // updateType       Update Type         '32' or '64'                    false
  // compatMode       Compatibility Mode  'normal', 'ignore', or 'strict' amo

  // See: https://developer.mozilla.org/Add-ons/Install_Manifests#updateURL

// ====================================================================================================================

// == | Setup | =======================================================================================================

// Constants
// This constant is a list of Add-on IDs that should never be checked for
const BAD_ADDON_IDS = array(
  '{972ce4c6-7e08-4474-a285-3208198ce6fd}', // Default Theme
  'modern@themes.mozilla.org',              // Mozilla Modern Theme
  '{a62ef8ec-5fdc-40c2-873c-223b8a6925cc}', // GData
  '{e2fda1a4-762b-4020-b5ad-a41df1933103}', // Lightning
);

// Include modules
gfImportModules('database', 'readManifest', 'persona', 'generateContent');

// Assign HTTP GET arguments to the software state
$gaRuntime['qPersona']          = gfSuperVar('get', 'persona');
$gaRuntime['qAddonID']          = gfSuperVar('get', 'id');
$gaRuntime['qAddonVersion']     = gfSuperVar('get', 'version');
$gaRuntime['qAppID']            = gfSuperVar('get', 'appID');
$gaRuntime['qAppVersion']       = gfSuperVar('get', 'appVersion');
$gaRuntime['qAddonCompatMode']  = gfSuperVar('get', 'compatMode');
$gaRuntime['qMozXPIUpdate']     = gfSuperVar('server', 'HTTP_MOZ_XPI_UPDATE') ?? gfSuperVar('get', 'updateOverride');

// ====================================================================================================================

// == | Main | ========================================================================================================

// Deal with Personas before anything else
if ($gaRuntime['qPersona']) {
  $personaManifest = $gmPersona->getPersonaByID($gaRuntime['qPersona']);

  gfHeader('json');

  if (!$personaManifest) {
    print('{}');
    exit();
  }

  print(json_encode($personaManifest, JSON_ENCODE_FLAGS));
  exit();
}

// --------------------------------------------------------------------------------------------------------------------

// Sanity
if (!$gaRuntime['qAddonID'] || !$gaRuntime['qAddonVersion'] ||
    !$gaRuntime['qAppID'] || !$gaRuntime['qAppVersion'] ||
    !$gaRuntime['qAddonCompatMode']) {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('Missing minimum required arguments.');
}

// Check for Moz-XPI-Update header
if (!$gaRuntime['qMozXPIUpdate']) {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('Compatibility check failed.');
}

// --------------------------------------------------------------------------------------------------------------------

if (!$gaRuntime['validClient']) {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('Client check failed.');
}

if (!gfValidClientVersion(true, $gaRuntime['qAppVersion'])) {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('Version check failed.');
}

// --------------------------------------------------------------------------------------------------------------------

// Check for "Bad" Add-on IDs
if (in_array($gaRuntime['qAddonID'], BAD_ADDON_IDS)) {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('"Bad" Add-on ID Detected');
}

// --------------------------------------------------------------------------------------------------------------------

// Check for Add-on Updates
if ($gaRuntime['qAppID'] == $gaRuntime['targetApplicationID'] ||
    ($gaRuntime['debugMode'] && $gaRuntime['orginalApplication'])) {
  $addonManifest = $gmReadManifest->getAddon('by-id', $gaRuntime['qAddonID']);

  if (!$addonManifest) {
    // Add-on is non-existant send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  
  // Add-on exists so send update.rdf
  $gmGenerateContent->addonUpdateService($addonManifest);
}
else {
  if (!$gaRuntime['debugMode']) {
    // Send blank rdf response
    $gmGenerateContent->addonUpdateService(null);
  }
  gfError('Mismatched or Invalid Application ID');
}

// ====================================================================================================================

?>
