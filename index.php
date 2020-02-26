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
$arraySoftwareState = array(
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
  switch ($arraySoftwareState['phpServerName']) {
    case $_value['domain']['live']:
      $arraySoftwareState['currentApplication'] = $_key;
      $arraySoftwareState['targetApplicationID'] = $_value['id'];
      $arraySoftwareState['currentDomain'] = $_value['domain']['live'];
      break;
    case $_value['domain']['dev']:
      $arraySoftwareState['currentApplication'] = $_key;
      $arraySoftwareState['targetApplicationID'] = $_value['id'];
      $arraySoftwareState['currentDomain'] = $_value['domain']['dev'];
      $arraySoftwareState['debugMode'] = true;
      break;
  }

  if ($arraySoftwareState['currentApplication']) {
    break;
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Items that get changed depending on debug mode
if ($arraySoftwareState['debugMode']) {
  // We can disable debug mode when on a dev url otherwise if debug mode we want all errors
  if ($arraySoftwareState['requestDebugOff']) {
    $arraySoftwareState['debugMode'] = null;
  }

  // Override currentApplication by query
  // If requestApplication is set and it exists in the array constant set the currentApplication to that
  if ($arraySoftwareState['requestApplication']) {
    if (array_key_exists($arraySoftwareState['requestApplication'], TARGET_APPLICATION)) {
      $arraySoftwareState['orginalApplication'] = $arraySoftwareState['currentApplication'];
      $arraySoftwareState['currentApplication'] = $arraySoftwareState['requestApplication'];
    }
    else {
      funcError('Invalid application');
    }

    // The same application shouldn't be appOverriden
    if ($arraySoftwareState['currentApplication'] == $arraySoftwareState['orginalApplication']) {
      funcError('It makes no sense to appOverride the same application');
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
if (!$arraySoftwareState['currentDomain']) {
  funcError('Invalid domain');
}

// We cannot continue without a valid currentApplication
if (!$arraySoftwareState['currentApplication']) {
  funcError('Invalid application');
}

// We cannot contine if the application is not enabled
if (!TARGET_APPLICATION[$arraySoftwareState['currentApplication']]['enabled']) {
  funcError('This ' . ucfirst($arraySoftwareState['currentApplication']) . ' Add-ons Site has been disabled. ' .
            'Please contact the Phoebus Administrator');
}

// --------------------------------------------------------------------------------------------------------------------

// Root (/) won't set a component or path
if (!$arraySoftwareState['requestComponent'] && !$arraySoftwareState['requestPath']) {
  $arraySoftwareState['requestComponent'] = 'site';
  $arraySoftwareState['requestPath'] = '/';
}
// The PANEL component overrides the SITE component
elseif (startsWith($arraySoftwareState['phpRequestURI'], '/panel/')) {
  $arraySoftwareState['requestComponent'] = 'panel';
}
// The SPECIAL component overrides the SITE component
elseif (startsWith($arraySoftwareState['phpRequestURI'], '/special/')) {
  $arraySoftwareState['requestComponent'] = 'special';
}

// --------------------------------------------------------------------------------------------------------------------

// Load component based on requestComponent
if ($arraySoftwareState['requestComponent'] && array_key_exists($arraySoftwareState['requestComponent'], COMPONENTS)) {
  require_once(COMPONENTS[$arraySoftwareState['requestComponent']]);
}
else {
  if (!$arraySoftwareState['debugMode']) {
    funcSendHeader('404');
  }
  funcError('Invalid component');
}

// ====================================================================================================================

?>