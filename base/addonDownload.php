<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
gfImportModules('database', 'readManifest');

// ====================================================================================================================

// == | funcDownloadXPI | ===============================================

function gfDownloadXPI($aAddonManifest, $aAddonVersion, $aBinaryStream = null) {
  $versionXPI = null;
  
  if ($aAddonVersion == 'latest') {
    $versionXPI = $aAddonManifest['release'];
    $addonFile = $aAddonManifest['basePath'] . $versionXPI;
  }
  else {
    $_versionMatch = false;
    foreach ($aAddonManifest['xpinstall'] as $_key => $_value) {
      if (in_array($aAddonVersion, $_value)) {
        $_versionMatch = true;
        $versionXPI = $_key;
        break;
      }
    }
    
    if ($_versionMatch == true) { 
      $addonFile = $aAddonManifest['basePath'] . $versionXPI;
    }
    else {
      gfError('Unknown XPI version');
    }
  }

   
  if (file_exists($addonFile)) {
    // Non-web browsers should send as an arbitrary binary stream
    if ($aBinaryStream) {
      header('Content-Type: application/octet-stream');
    }
    else {
      header('Content-Type: application/x-xpinstall');
    }

    header('Content-Disposition: inline; filename="' . $versionXPI . '"');
    header('Content-Length: ' . filesize($addonFile));
    header('Cache-Control: no-cache');
    header('X-Accel-Redirect: ' . ltrim($addonFile, '.'));
  }
  else {
    gfError('XPI file not found');
  }

  // We are done here
  exit();
}

// ============================================================================

// == | funcDownloadSearchPlugin | ============================================

function gfDownloadSearchPlugin($aSearchPluginName, $aBinaryStream = null) {
  $searchPluginFile = './datastore/searchplugins/' . $aSearchPluginName;
  
  if (file_exists($searchPluginFile)) {
    // Non-web browsers should send as an arbitrary binary stream
    if ($aBinaryStream) {
      header('Content-Type: application/octet-stream');
    }
    else {
      header('Content-Type: text/xml');
    }

    header('Content-Disposition: inline; filename="' . $aSearchPluginName .'"');
    header('Cache-Control: no-cache');
    
    readfile($searchPluginFile);
  }
  else {
    gfError('Search Plugin XML file not found');
  }
  
  // We are done here
  exit();
}

// ============================================================================

// == | Main | ================================================================

$gaRuntime['qAddonID'] = gfSuperVar('get', 'id');
$gaRuntime['qVersion'] = gfSuperVar('get', 'version') ?? 'latest';
$gaRuntime['qPanel']   = gfSuperVar('get', 'panel');
$gaRuntime['qBinary']  = in_array('disable-xpinstall',
                                  TARGET_APPLICATION_SITE[$gaRuntime['currentApplication']]['features']);

// Sanity
if ($gaRuntime['qAddonID'] == null) {
  gfError('Missing minimum required arguments.');
}

if (!$gaRuntime['validClient']){
  if (!$gaRuntime['debugMode']) {
    gfHeader(404);
  }
  gfError('Client check failed.');
}

if (!$gaRuntime['validVersion']) {
  if (!$gaRuntime['debugMode']) {
    gfHeader(404);
  }
  gfError('Version check failed.');
  
}

// Search for add-ons in our databases
if ($gaRuntime['qPanel']) {
  $addonManifest = $gmReadManifest->getAddon('panel-by-id', $gaRuntime['qAddonID']);
  $gaRuntime['qBinary'] = true;
}
else {
  $addonManifest = $gmReadManifest->getAddon('by-id', $gaRuntime['qAddonID']);
}

if ($addonManifest != null) {
  $addonManifest['release'] = $addonManifest['releaseXPI'];
  gfDownloadXPI($addonManifest, $gaRuntime['qVersion'], $gaRuntime['qBinary']);
}
else {  
  // Search Plugins
  require_once(DATABASES['searchPlugins']);
  if (array_key_exists($gaRuntime['qAddonID'], $searchPluginsDB)) {
    gfDownloadSearchPlugin($searchPluginsDB[$gaRuntime['qAddonID']], $gaRuntime['qBinary']);
  }
  else {
    gfError('Add-on could not be found in our database');
  }
}




// ============================================================================
?>