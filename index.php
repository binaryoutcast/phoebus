<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

error_reporting(E_ALL);
ini_set("display_errors", "on");

require_once('./globalConstants.php');
require_once('./globalFunctions.php');

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$gaRuntime = array(
  'authentication'      => null,
  'currentApplication'  => null,
  'orginalApplication'  => null,
  'currentSiteTitle'    => null,
  'currentScheme'       => gfSuperVar('server', 'SCHEME'),
  'currentDomain'       => null,
  'debugMode'           => null,
  'tap'                 => gfSuperVar('var', file_exists(ROOT_PATH . '/.tap')),
  'phpServerName'       => gfSuperVar('server', 'SERVER_NAME'),
  'phpRequestURI'       => gfSuperVar('server', 'REQUEST_URI'),
  'remoteAddr'          => gfSuperVar('server', 'HTTP_X_FORWARDED_FOR') ??
                           gfSuperVar('server', 'REMOTE_ADDR'),
  'requestComponent'    => gfSuperVar('get', 'component'),
  'requestPath'         => gfSuperVar('get', 'path'),
  'requestApplication'  => gfSuperVar('get', 'appOverride') ??
                           gfSuperVar('cookie', 'appOverride'),
  'requestDebugOff'     => gfSuperVar('get', 'debugOff'),
  'requestSearchTerms'  => gfSuperVar('get', 'terms')
);

// --------------------------------------------------------------------------------------------------------------------

// Decide which application by domain that the software will be serving
// and if debug is enabled
foreach (TARGET_APPLICATION as $_key => $_value) {
  switch ($gaRuntime['phpServerName']) {
    case $_value['domain']['live']:
      $gaRuntime['currentApplication'] = $_key;
      $gaRuntime['targetApplicationID'] = $_value['id'];
      $gaRuntime['currentDomain'] = $_value['domain']['live'];
      break;
    case $_value['domain']['dev']:
      $gaRuntime['currentApplication'] = $_key;
      $gaRuntime['targetApplicationID'] = $_value['id'];
      $gaRuntime['currentDomain'] = $_value['domain']['dev'];
      $gaRuntime['debugMode'] = true;
      break;
  }

  if ($gaRuntime['currentApplication']) {
    break;
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Items that get changed depending on debug mode
if ($gaRuntime['debugMode']) {
  // We can disable debug mode when on a dev url otherwise if debug mode we want all errors
  if ($gaRuntime['requestDebugOff']) {
    $gaRuntime['debugMode'] = null;
  }

  // Override currentApplication by query
  // If requestApplication is set and it exists in the array constant set the currentApplication to that
  if ($gaRuntime['requestApplication']) {
    if (array_key_exists($gaRuntime['requestApplication'], TARGET_APPLICATION)) {
      $gaRuntime['orginalApplication'] = $gaRuntime['currentApplication'];
      $gaRuntime['currentApplication'] = $gaRuntime['requestApplication'];
    }
    else {
      gfError('Invalid application');
    }

    // The same application shouldn't be appOverriden
    if ($gaRuntime['currentApplication'] == $gaRuntime['orginalApplication']) {
      gfError('It makes no sense to appOverride the same application');
    }
  }
}

// --------------------------------------------------------------------------------------------------------------------

// If the entire site is offline but nothing above is busted.. We want to serve proper but empty responses
if (file_exists(ROOT_PATH . '/.offline')) {
  require_once(ROOT_PATH . '/mini.php');
}

// --------------------------------------------------------------------------------------------------------------------

// We cannot continue without a valid currentApplication
if (!$gaRuntime['currentDomain']) {
  gfError('Invalid domain');
}

// We cannot continue without a valid currentApplication
if (!$gaRuntime['currentApplication']) {
  gfError('Invalid application');
}

// We cannot contine if the application is not enabled
if (!TARGET_APPLICATION[$gaRuntime['currentApplication']]['enabled']) {
  gfError('This ' . ucfirst($gaRuntime['currentApplication']) . ' Add-ons Site has been disabled. ' .
            'Please contact the Phoebus Administrator');
}

// --------------------------------------------------------------------------------------------------------------------

// Root (/) won't set a component or path
if (!$gaRuntime['requestComponent'] && !$gaRuntime['requestPath']) {
  $gaRuntime['requestComponent'] = 'site';
  $gaRuntime['requestPath'] = '/';
}
// The PANEL component overrides the SITE component
elseif (str_starts_with($gaRuntime['phpRequestURI'], '/panel/')) {
  $gaRuntime['requestComponent'] = 'panel';
}
// The SPECIAL component overrides the SITE component
elseif (str_starts_with($gaRuntime['phpRequestURI'], '/special/')) {
  $gaRuntime['requestComponent'] = 'special';
}

// --------------------------------------------------------------------------------------------------------------------

// Load component based on requestComponent
if ($gaRuntime['requestComponent'] && array_key_exists($gaRuntime['requestComponent'], COMPONENTS)) {
  require_once(COMPONENTS[$gaRuntime['requestComponent']]);
}
else {
  if (!$gaRuntime['debugMode']) {
    gfHeader(404);
  }
  gfError('Invalid component');
}

// ====================================================================================================================

?>