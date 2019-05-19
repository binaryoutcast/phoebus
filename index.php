<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

error_reporting(E_ALL);
ini_set("display_errors", "on");

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Define basic constants for the software
const SOFTWARE_NAME       = 'Phoebus';
const SOFTWARE_VERSION    = '2.1.0a1';
const DATASTORE_RELPATH   = '/datastore/';
const OBJ_RELPATH         = '/.obj/';
const COMPONENTS_RELPATH  = '/components/';
const DATABASES_RELPATH   = '/databases/';
const MODULES_RELPATH     = '/modules/';
const LIB_RELPATH         = '/libraries/';
const NEW_LINE            = "\n";

// Define components
const COMPONENTS = array(
  'aus'             => ROOT_PATH . COMPONENTS_RELPATH . 'aus/addonUpdateService.php',
  'discover'        => ROOT_PATH . COMPONENTS_RELPATH . 'discover/discoverPane.php',
  'download'        => ROOT_PATH . COMPONENTS_RELPATH . 'download/addonDownload.php',
  'integration'     => ROOT_PATH . COMPONENTS_RELPATH . 'api/amIntegration.php',
  'panel'           => ROOT_PATH . COMPONENTS_RELPATH . 'panel/phoebusPanel.php',
  'site'            => ROOT_PATH . COMPONENTS_RELPATH . 'site/addonSite.php',
  'special'         => ROOT_PATH . COMPONENTS_RELPATH . 'special/specialComponent.php'
);

// Define modules
const MODULES = array(
  'account'         => ROOT_PATH . MODULES_RELPATH . 'classAccount.php',
  'database'        => ROOT_PATH . MODULES_RELPATH . 'classDatabase.php',
  'generateContent' => ROOT_PATH . MODULES_RELPATH . 'classGenerateContent.php',
  'mozillaRDF'      => ROOT_PATH . MODULES_RELPATH . 'classMozillaRDF.php',
  'persona'         => ROOT_PATH . MODULES_RELPATH . 'classPersona.php',
  'readManifest'    => ROOT_PATH . MODULES_RELPATH . 'classReadManifest.php',
  'tap'             => ROOT_PATH . MODULES_RELPATH . 'classTap.php',
  'writeManifest'   => ROOT_PATH . MODULES_RELPATH . 'classWriteManifest.php',
  'vc'              => ROOT_PATH . MODULES_RELPATH . 'nsIVersionComparator.php',
);

// Define databases
const DATABASES = array(
  'emailBlacklist'  => ROOT_PATH . DATABASES_RELPATH . 'emailBlacklist.php',
  'searchPlugins'   => ROOT_PATH . DATABASES_RELPATH . 'searchPlugins.php',
);

// Define libraries
const LIBRARIES = array(
  'smarty'          => ROOT_PATH . LIB_RELPATH . 'smarty/Smarty.class.php',
  'safeMySQL'       => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php',
  'rdfParser'       => ROOT_PATH . LIB_RELPATH . 'rdf/rdf_parser.php',
);

/* Known Application IDs
 * Application IDs are normally in the form of a {GUID} or user@host ID.
 *
 * Firefox:          {ec8030f7-c20a-464f-9b0e-13a3a9e97384}
 * Thunderbird:      {3550f703-e582-4d05-9a08-453d09bdfdc6}
 * SeaMonkey:        {92650c4d-4b8e-4d2a-b7eb-24ecf4f6b63a}
 * Fennec (Android): {aa3c5121-dab2-40e2-81ca-7ea25febc110}
 * Fennec (XUL):     {a23983c0-fd0e-11dc-95ff-0800200c9a66}
 * Sunbird:          {718e30fb-e89b-41dd-9da7-e25a45638b28}
 * Instantbird:      {33cb9019-c295-46dd-be21-8c4936574bee}
 * Adblock Browser:  {55aba3ac-94d3-41a8-9e25-5c21fe874539} */
const TOOLKIT_ID          = 'toolkit@mozilla.org';
const TOOLKIT_BIT         = 1;

// Define application metadata
const TARGET_APPLICATION = array(
  'palemoon' => array(
    'enabled'       => true,
    'id'            => '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}',
    'bit'           => 2,
    'name'          => 'Pale Moon',
    'siteTitle'     => 'Pale Moon - Add-ons',
    'domain'        => array('live' => 'addons.palemoon.org', 'dev' => 'addons-dev.palemoon.org'),
    'features'      => array('https', 'extensions', 'extensions-cat', 'themes',
                             'personas', 'language-packs', 'search-plugins')
  ),
  'basilisk' => array(
    'enabled'       => true,
    'id'            => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
    'bit'           => 4,
    'name'          => 'Basilisk',
    'siteTitle'     => 'Basilisk: add-ons',
    'domain'        => array('live' => 'addons.basilisk-browser.org', 'dev' => null),
    'features'      => array('https', 'extensions', 'themes', 'personas', 'search-plugins')
  ),
  'ambassador' => array(
    'enabled'       => true,
    'id'            => '{4523665a-317f-4a66-9376-3763d1ad1978}',
    'bit'           => 8,
    'name'          => 'Ambassador',
    'siteTitle'     => 'Add-ons - Ambassador',
    'domain'        => array('live' => 'ab-addons.thereisonlyxul.org', 'dev' => null),
    'features'      => array('extensions', 'themes', 'disable-xpinstall')
  ),
  'borealis' => array(
    'enabled'       => false,
    'id'            => '{a3210b97-8e8a-4737-9aa0-aa0e607640b9}',
    'bit'           => 16,
    'name'          => 'Borealis',
    'siteTitle'     => 'Borealis Add-ons - Binary Outcast',
    'domain'        => array('live' => 'borealis-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('extensions', 'search-plugins')
  ),
  'interlink' => array(
    'enabled'       => true,
    'id'            => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
    'bit'           => 32,
    'name'          => 'Interlink',
    'siteTitle'     => 'Interlink Add-ons - Binary Outcast',
    'domain'        => array('live' => 'interlink-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('extensions', 'themes', 'search-plugins', 'disable-xpinstall')
  ),
);

// ====================================================================================================================

// == | Functions | ===================================================================================================

require_once(ROOT_PATH . MODULES_RELPATH . 'basicFunctions.php');

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$arraySoftwareState = array(
  'authentication'      => null,
  'currentApplication'  => null,
  'orginalApplication'  => null,
  'currentSiteTitle'    => null,
  'currentScheme'       => funcUnifiedVariable('server', 'SCHEME'),
  'currentDomain'       => null,
  'debugMode'           => null,
  'tap'                 => funcUnifiedVariable('var', file_exists(ROOT_PATH . '/.tap')),
  'phpServerName'       => funcUnifiedVariable('server', 'SERVER_NAME'),
  'phpRequestURI'       => funcUnifiedVariable('server', 'REQUEST_URI'),
  'remoteAddr'          => funcUnifiedVariable('server', 'HTTP_X_FORWARDED_FOR') ??
                           funcUnifiedVariable('server', 'REMOTE_ADDR'),
  'requestComponent'    => funcUnifiedVariable('get', 'component'),
  'requestPath'         => funcUnifiedVariable('get', 'path'),
  'requestApplication'  => funcUnifiedVariable('get', 'appOverride') ??
                           funcUnifiedVariable('cookie', 'appOverride'),
  'requestDebugOff'     => funcUnifiedVariable('get', 'debugOff'),
  'requestSearchTerms'  => funcUnifiedVariable('get', 'terms')
);

// --------------------------------------------------------------------------------------------------------------------
// If the entire site is offline but nothing above is busted.. We want to serve proper but empty responses
if (file_exists(ROOT_PATH . '/.offline')) {
  $strOfflineMessage = 'Phoebus, and by extension this Add-ons Site, is currently unavailable. Please try again later.';
  // Root (/) won't set a component or path
  if (!$arraySoftwareState['requestComponent'] && !$arraySoftwareState['requestPath']) {
    $arraySoftwareState['requestComponent'] = 'site';
    $arraySoftwareState['requestPath'] = '/';
  }

  switch ($arraySoftwareState['requestComponent']) {
    case 'aus':
      funcSendHeader('xml');
      print('<?xml version="1.0" encoding="UTF-8"?><RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />');
      exit();
      break;
    case 'integration':
      $arraySoftwareState['requestAPIScope'] = funcUnifiedVariable('get', 'type');
      $arraySoftwareState['requestAPIFunction'] = funcUnifiedVariable('get', 'request');
      if ($arraySoftwareState['requestAPIScope'] != 'internal') {
        funcSendHeader('404');
      }
      switch ($arraySoftwareState['requestAPIFunction']) {
        case 'search':
          funcSendHeader('xml');
          print('<?xml version="1.0" encoding="utf-8" ?><searchresults total_results="0" />');
          exit();
          break;      
        case 'get':
        case 'recommended':
          funcSendHeader('xml');
          print('<?xml version="1.0" encoding="utf-8" ?><addons />');
          exit();
          break;
        default:
          funcSendHeader('404');
      }
      break;
    case 'discover': funcSend404();
    default: funcError($strOfflineMessage);
  }
}

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