<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database', 'readManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();

// ====================================================================================================================

// == | funcDownloadXPI | ===============================================

function funcDownloadXPI($aAddonManifest, $aAddonVersion, $aBinaryStream = null) {
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
      funcError('Unknown XPI version');
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
    funcError('XPI file not found');
  }

  // We are done here
  exit();
}

// ============================================================================

// == | funcDownloadSearchPlugin | ============================================

function funcDownloadSearchPlugin($aSearchPluginName, $aBinaryStream = null) {
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
    funcError('Search Plugin XML file not found');
  }
  
  // We are done here
  exit();
}

// ============================================================================

// == | Main | ================================================================

$strRequestAddonID = funcUnifiedVariable('get', 'id');
$strRequestAddonVersion = funcUnifiedVariable('get', 'version') ?? 'latest';
$boolRequestPanel = funcUnifiedVariable('get', 'panel');
$boolRequestBinaryStream = in_array('disable-xpinstall', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
                    

// Sanity
if ($strRequestAddonID == null) {
  funcError('Missing minimum required arguments.');
}

// Search for add-ons in our databases
if ($boolRequestPanel) {
  $addonManifest = $moduleReadManifest->getAddon('panel-by-id', $strRequestAddonID);
  $boolRequestBinaryStream = true;
}
else {
  $addonManifest = $moduleReadManifest->getAddon('by-id', $strRequestAddonID);
}

if ($addonManifest != null) {
  $addonManifest['release'] = $addonManifest['releaseXPI'];
  funcDownloadXPI($addonManifest, $strRequestAddonVersion, $boolRequestBinaryStream);
}
else {  
  // Search Plugins
  require_once(DATABASES['searchPlugins']);
  if (array_key_exists($strRequestAddonID, $searchPluginsDB)) {
    funcDownloadSearchPlugin($searchPluginsDB[$strRequestAddonID], $boolRequestBinaryStream);
  }
  else {
    funcError('Add-on could not be found in our database');
  }
}




// ============================================================================
?>