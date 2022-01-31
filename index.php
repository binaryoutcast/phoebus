<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Enable Error Reporting
error_reporting(E_ALL);
ini_set("display_errors", "on");
ini_set('html_errors', false);

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Debug flag
define('DEBUG_MODE', $_GET['debug'] ?? null);

// Define basic constants for the software
const SOFTWARE_NAME       = 'Phoebus';
const SOFTWARE_VERSION    = '2.2.0';
const DATASTORE_RELPATH   = '/datastore/';
const OBJ_RELPATH         = '/.obj/';
const BASE_RELPATH        = '/base/';
const COMPONENTS_RELPATH  = '/components/';
const DATABASES_RELPATH   = '/databases/';
const MODULES_RELPATH     = '/modules/';
const LIB_RELPATH         = '/libraries/';
const SPECIAL_SKIN_PATH   = ROOT_PATH . '/base/skin/default/';

// Define components
const COMPONENTS = array(
  'aus'             => ROOT_PATH . COMPONENTS_RELPATH . 'api/addonUpdateService.php',
  'discover'        => ROOT_PATH . COMPONENTS_RELPATH . 'discover/discoverPane.php',
  'download'        => ROOT_PATH . BASE_RELPATH . 'addonDownload.php',
  'integration'     => ROOT_PATH . COMPONENTS_RELPATH . 'api/amIntegration.php',
  'panel'           => ROOT_PATH . COMPONENTS_RELPATH . 'panel/phoebusPanel.php',
  'site'            => ROOT_PATH . BASE_RELPATH . 'addonSite.php',
  'special'         => ROOT_PATH . BASE_RELPATH . 'specialComponent.php'
);

// Define modules
const MODULES = array(
  'account'         => ROOT_PATH . MODULES_RELPATH . 'classAccount.php',
  'database'        => ROOT_PATH . MODULES_RELPATH . 'classDatabase.php',
  'generateContent' => ROOT_PATH . MODULES_RELPATH . 'classGenerateContent.php',
  'log'             => ROOT_PATH . MODULES_RELPATH . 'classLog.php',
  'mozillaRDF'      => ROOT_PATH . MODULES_RELPATH . 'classMozillaRDF.php',
  'persona'         => ROOT_PATH . MODULES_RELPATH . 'classPersona.php',
  'readManifest'    => ROOT_PATH . MODULES_RELPATH . 'classReadManifest.php',
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
  'smarty'          => ROOT_PATH . LIB_RELPATH . 'smarty/libs/Smarty.class.php',
  'safeMySQL'       => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php',
  'rdfParser'       => ROOT_PATH . LIB_RELPATH . 'librdf/rdf_parser.php',
);

// Define the target applications that the site will accommodate with
// the enabled site features
const TARGET_APPLICATION_SITE = array(
  'palemoon' => array(
    'enabled'       => true,
    'oldVersion'    => '28.0.0',
    'name'          => 'Pale Moon - Legacy (29 and older) Add-ons',
    'domain'        => array('live' => 'addons-legacy.palemoon.org', 'dev' => 'addons-dev.palemoon.org'),
    'features'      => array('https', 'extensions', 'extensions-cat', 'themes',
                             'personas', 'language-packs', 'search-plugins')
  ),
  'basilisk' => array(
    'enabled'       => true,
    'oldVersion'    => '20210427',
    'name'          => 'Basilisk: add-ons',
    'domain'        => array('live' => 'addons.basilisk-browser.org', 'dev' => null),
    'features'      => array('https', 'extensions', 'themes', 'personas', 'search-plugins')
  ),
  'ambassador' => array(
    'enabled'       => false,
    'oldVersion'    => '0.1',
    'name'          => 'Add-ons - Ambassador',
    'domain'        => array('live' => 'ab-addons.thereisonlyxul.org', 'dev' => null),
    'features'      => array('extensions', 'themes', 'disable-xpinstall')
  ),
  'borealis' => array(
    'enabled'       => false,
    'oldVersion'    => '0.1',
    'name'          => 'Borealis Add-ons - Binary Outcast',
    'domain'        => array('live' => 'borealis-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('https', 'extensions', 'themes', 'search-plugins')
  ),
  'interlink' => array(
    'enabled'       => true,
    'oldVersion'    => '0.1',
    'name'          => 'Interlink Add-ons - Binary Outcast',
    'domain'        => array('live' => 'interlink-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('https', 'extensions', 'themes', 'search-plugins', 'disable-xpinstall')
  ),
);

/* Define Application IDs
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
const TARGET_APPLICATION_ID = array(
  'toolkit'         => 'toolkit@mozilla.org',
  'palemoon'        => '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}',
  'basilisk'        => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
  'ambassador'      => '{4523665a-317f-4a66-9376-3763d1ad1978}',
  'borealis'        => '{a3210b97-8e8a-4737-9aa0-aa0e607640b9}',
  'interlink'       => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
);

const EXTENSION_CATEGORY_SLUGS = array(
  'alerts-and-updates'        => 'Alerts &amp; Updates',
  'appearance'                => 'Appearance',
  'bookmarks-and-tabs'        => 'Bookmarks &amp; Tabs',
  'download-management'       => 'Download Management',
  'feeds-news-and-blogging'   => 'Feeds, News, &amp; Blogging',
  'privacy-and-security'      => 'Privacy &amp; Security',
  'search-tools'              => 'Search Tools',
  'social-and-communication'  => 'Social &amp; Communication',
  'tools-and-utilities'       => 'Tools &amp; Utilities',
  'web-development'           => 'Web Development',
  'other'                     => 'Other'
);

const OTHER_CATEGORY_SLUGS = array(
  'themes'                    => 'Themes',
  'personas'                  => 'Personas',
  'search-plugins'            => 'Search Plugins',
  'language-packs'            => 'Language Packs',
);

// Load fundamental constants and global functions
require_once('./fundamentals.php');

// nsIVersionComparator is now needed software wide so include it.
gfImportModules('vc');

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Basic Content Generation using the Special Component's Template
***********************************************************************************************************************/
function gfGenContent($aTitle, $aContent, $aTextBox = null, $aList = null, $aError = null) {
  $templateHead = @file_get_contents(SPECIAL_SKIN_PATH . 'template-header.xhtml');
  $templateFooter = @file_get_contents(SPECIAL_SKIN_PATH . 'template-footer.xhtml');

  // Make sure the template isn't busted, if it is send a text only error as an array
  if (!$templateHead || !$templateFooter) {
    gfError([__FUNCTION__ . ': Special Template is busted...', $aTitle, $aContent], -1);
  }

  // Can't use both the textbox and list arguments
  if ($aTextBox && $aList) {
    gfError(__FUNCTION__ . ': You cannot use both textbox and list');
  }

  // Anonymous function to determin if aContent is a string-ish or not
  $notString = function() use ($aContent) {
    return (!is_string($aContent) && !is_int($aContent)); 
  };

  // If not a string var_export it and enable the textbox
  if ($notString()) {
    $aContent = var_export($aContent, true);
    $aTextBox = true;
    $aList = false;
  }

  // Use either a textbox or an unordered list
  if ($aTextBox) {
    // We are using the textbox so put aContent in there
    $aContent = '<textarea style="width: 1195px; resize: none;" name="content" rows="36" readonly>' .
                $aContent .
                '</textarea>';
  }
  elseif ($aList) {
    // We are using an unordered list so put aContent in there
    $aContent = '<ul><li>' . $aContent . '</li><ul>';
  }

  // Set page title
  $templateHead = str_replace('<title></title>',
                  '<title>' . $aTitle . ' - ' . SOFTWARE_NAME . ' ' . SOFTWARE_VERSION . '</title>',
                  $templateHead);

  // If we are generating an error from gfError we want to clean the output buffer
  if ($aError) {
    ob_get_clean();
  }

  // Send an html header
  header('Content-Type: text/html', false);

  // write out the everything
  print($templateHead . '<h2>' . $aTitle . '</h2>' . $aContent . $templateFooter);

  // We're done here
  exit();
}

/**********************************************************************************************************************
* Checks for enabled features
*
* @param $aFeature    feature
* @param $aReturn     if true we will return a value else 404
***********************************************************************************************************************/
function gfEnabledFeature($aFeature, $aReturn = null) {
  global $gaRuntime;

  $currentApplication = $gaRuntime['currentApplication'];
  if (!in_array($aFeature, TARGET_APPLICATION_SITE[$currentApplication]['features'])) {
    if(!$aReturn) {
      gfHeader(404);
    }

    return null;
  }

  return true;
}

/**********************************************************************************************************************
* Checks for old versions
*
* @param $aFeature    feature
* @param $aReturn     if true we will return a value else 404
***********************************************************************************************************************/
function gfValidClientVersion($aCheckVersion = null, $aVersion = null) {
  global $gaRuntime;

  $currentApplication = $gaRuntime['currentApplication'];

  // No user agent is a blatantly bullshit state
  if (!$gaRuntime['userAgent']) {
    gfError('Reference Code - ID-10-T');
  }

  // Non-web clients get a pass because they aren't web clients... for NOW.
  if (gfEnabledFeature('disable-xpinstall', true)) {
    return true;
  }

  // Knock the UA to lowercase so it is easier to deal with
  $userAgent = strtolower($gaRuntime['userAgent']);


  // ------------------------------------------------------------------------------------------------------------------

  // This is our basic client ua check.
  if (!$aCheckVersion) {
    // Check for invalid clients
    foreach (['curl/', 'wget/', 'git/'] as $_value) {
      if (str_contains($userAgent, $_value)) {
        gfError('Reference Code - ID-10-T');
      }
    }

    $oldAndInsecureHackJobs = array(
      'nt 5',
      'nt 6.0',
      'macintosh',
      'intel',
      'ppc',
      'mac os',
      'iphone',
      'ipad',
      'ipod',
      'android',
      'goanna/3.5',
      'goanna/4.0',
      'rv:3.5',
      'rv:52.9',
      'basilisk/52.9.0',
      '55.0',
      'mypal/',
      'centaury/',
      'bnavigator/',
    );

    // Check for old and insecure Windows versions and enemy hackjobs
    foreach ($oldAndInsecureHackJobs as $_value) {
      if (str_contains($userAgent, $_value)) {
        return false;
      }
    }

    // Check if the application slice matches the current site.
    if (!str_contains($userAgent, $currentApplication)) {
      return false;
    }

    return true;
  }

  // ------------------------------------------------------------------------------------------------------------------

  // This is the main meat of this function. To detect old and insecure application versions
  // Try to find the position of the application slice in the UA
  $uaVersion = strpos($userAgent, $currentApplication . SLASH);

  // Make sure we have a position for the application slice
  // If we don't then it ain't gonna match the current add-ons site
  if ($uaVersion === false) {
    return false;
  }

  // Extract the application slice by slicing off everything before it
  // UXP Applications ALWAYS have the application slice at the end of the UA
  $uaVersion = substr($userAgent, $uaVersion, $uaVersion);

  // Extract the application version
  $uaVersion = str_replace($currentApplication . SLASH, EMPTY_STRING, $uaVersion);

  // Make sure we actually have a string
  if (!gfSuperVar('var', $uaVersion)) {
    return false;
  }

  // Set currentVersion to the supplied version else the extracted version from the ua
  $currentVersion = $aVersion ?? $uaVersion;

  // ------------------------------------------------------------------------------------------------------------------

  // Set the old version to compare against 
  $oldVersion = TARGET_APPLICATION_SITE[$currentApplication]['oldVersion'];

  // Basilisk needs special handling because .. It is Basilisk.
  // Convert any use the full mozilla-style version so it matches the UA slice when
  // general.useragent.appVersionIsBuildID is true which is default.
  // Just need to knock off the 52.9. and the dots
  if ($currentApplication == 'basilisk') {
    $lockedVersionPrefix = '52.9';
    if (str_starts_with($uaVersion, $lockedVersionPrefix . DOT . '20')) {
      $uaVersion = str_replace($lockedVersionPrefix, EMPTY_STRING, $uaVersion);
      $uaVersion = str_replace(DOT, EMPTY_STRING, $uaVersion);
    }

    if (str_starts_with($currentVersion, $lockedVersionPrefix . DOT . '20')) {
      $currentVersion = str_replace($lockedVersionPrefix, EMPTY_STRING, $currentVersion);
      $currentVersion = str_replace(DOT, EMPTY_STRING, $currentVersion);
    }
  }

  // If we are supplying the version number to check make sure it actually matches the UA.
  if ($aVersion && ($currentVersion != $uaVersion)) {
    return false;
  }

  // NOW we can compare it against the old version.. Finally.
  if (ToolkitVersionComparator::compare($currentVersion, $oldVersion) <= 0) {
    return false;
  }

  // Welp, seems it is newer than the currently stated old version so pass
  return true;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$gaRuntime = array(
  'authentication'      => null,
  'currentApplication'  => null,
  'orginalApplication'  => null,
  'currentName'         => null,
  'currentScheme'       => gfSuperVar('server', 'SCHEME') ?? (gfSuperVar('server', 'HTTPS') ? 'https' : 'http'),
  'currentDomain'       => null,
  'validClient'         => null,
  'validVersion'        => null,
  'debugMode'           => null,
  'useSmarty'           => null,
  'phpServerName'       => gfSuperVar('server', 'SERVER_NAME'),
  'phpRequestURI'       => gfSuperVar('server', 'REQUEST_URI'),
  'remoteAddr'          => gfSuperVar('server', 'HTTP_X_FORWARDED_FOR') ??
                           gfSuperVar('server', 'REMOTE_ADDR'),
  'userAgent'           => gfSuperVar('server', 'HTTP_USER_AGENT'),
  'qComponent'          => gfSuperVar('get', 'component'),
  'qPath'               => gfSuperVar('get', 'path'),
  'qApplication'        => gfSuperVar('get', 'appOverride'),
  'qDebugOff'           => gfSuperVar('get', 'debugOff'),
  'qSearchTerms'        => gfSuperVar('get', 'terms'),
);

// --------------------------------------------------------------------------------------------------------------------

// If the entire site is offline but nothing above is busted.. We want to serve proper but empty responses
if (file_exists(ROOT_PATH . '/.offline') && !gfSuperVar('cookie', 'overrideOffline')) {
  $strOfflineMessage = 'Phoebus, and by extension this Add-ons Site, is currently unavailable. Please try again later.';
  // Root (/) won't set a component or path
  if (!$gaRuntime['qComponent'] && !$gaRuntime['qPath']) {
    $gaRuntime['qComponent'] = 'site';
    $gaRuntime['qPath'] = '/';
  }

  switch ($gaRuntime['qComponent']) {
    case 'aus':
      gfHeader('xml');
      print('<?xml version="1.0" encoding="UTF-8"?><RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />');
      exit();
      break;
    case 'integration':
      $gaRuntime['requestAPIScope'] = gfSuperVar('get', 'type');
      $gaRuntime['requestAPIFunction'] = gfSuperVar('get', 'request');
      if ($gaRuntime['requestAPIScope'] != 'internal') {
        gfHeader(404);
      }
      switch ($gaRuntime['requestAPIFunction']) {
        case 'search':
          gfHeader('xml');
          print('<?xml version="1.0" encoding="utf-8" ?><searchresults total_results="0" />');
          exit();
          break;      
        case 'get':
        case 'recommended':
          gfHeader('xml');
          print('<?xml version="1.0" encoding="utf-8" ?><addons />');
          exit();
          break;
        default:
          gfHeader(404);
      }
      break;
    case 'discover':
      gfHeader(404);
    default:
      gfError($strOfflineMessage);
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Decide which application by domain that the software will be serving
// and if debug is enabled
foreach (TARGET_APPLICATION_SITE as $_key => $_value) {
  switch ($gaRuntime['phpServerName']) {
    case $_value['domain']['live']:
      $gaRuntime['currentApplication'] = $_key;
      $gaRuntime['currentDomain'] = $_value['domain']['live'];
      break;
    case $_value['domain']['dev']:
      $gaRuntime['currentApplication'] = $_key;
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
  if ($gaRuntime['qDebugOff']) {
    $gaRuntime['debugMode'] = null;
  }

  // Override currentApplication by query
  // If qApplication is set and it exists in the array constant set the currentApplication to that
  if ($gaRuntime['qApplication']) {
    if (array_key_exists($gaRuntime['qApplication'], TARGET_APPLICATION_SITE)) {
      $gaRuntime['orginalApplication'] = $gaRuntime['currentApplication'];
      $gaRuntime['currentApplication'] = $gaRuntime['qApplication'];
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

// We cannot continue without a valid currentApplication
if (!$gaRuntime['currentDomain']) {
  gfError('Invalid domain');
}

// We cannot continue without a valid currentApplication
if (!$gaRuntime['currentApplication']) {
  gfError('Invalid application');
}

// We cannot contine if the application is not enabled
if (!TARGET_APPLICATION_SITE[$gaRuntime['currentApplication']]['enabled']) {
  gfError('This ' . ucfirst($gaRuntime['currentApplication']) . ' Add-ons Site has been disabled. ' .
            'Please contact the Phoebus Administrator');
}

// --------------------------------------------------------------------------------------------------------------------

// Set valid client
$gaRuntime['validClient'] = gfValidClientVersion();
$gaRuntime['validVersion'] = gfValidClientVersion(true);

// --------------------------------------------------------------------------------------------------------------------

// Root (/) won't set a component or path
if (!$gaRuntime['qComponent'] && !$gaRuntime['qPath']) {
  $gaRuntime['qComponent'] = 'site';
  $gaRuntime['qPath'] = '/';
}
// The PANEL component overrides the SITE component
elseif (str_starts_with($gaRuntime['phpRequestURI'], '/panel/')) {
  $gaRuntime['qComponent'] = 'panel';
}
// The SPECIAL component overrides the SITE component
elseif (str_starts_with($gaRuntime['phpRequestURI'], '/special/')) {
  $gaRuntime['qComponent'] = 'special';
}

// --------------------------------------------------------------------------------------------------------------------

// Load component based on qComponent
if ($gaRuntime['qComponent'] && array_key_exists($gaRuntime['qComponent'], COMPONENTS)) {
  require_once(COMPONENTS[$gaRuntime['qComponent']]);
}
else {
  if (!$gaRuntime['debugMode']) {
    gfHeader(404);
  }
  gfError('Invalid component');
}

// ====================================================================================================================

?>