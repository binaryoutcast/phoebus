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
const SOFTWARE_VERSION    = '2.0.0b2';
const DATASTORE_RELPATH   = '/datastore/';
const OBJ_RELPATH         = '/.obj/';
const COMPONENTS_RELPATH  = '/components/';
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
  'writeManifest'   => ROOT_PATH . MODULES_RELPATH . 'classWriteManifest.php',
  'vc'              => ROOT_PATH . MODULES_RELPATH . 'nsIVersionComparator.php',
);

// Define libraries
const LIBRARIES = array(
  'smarty'          => ROOT_PATH . LIB_RELPATH . 'smarty/Smarty.class.php',
  'safeMySQL'       => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php',
  'rdfParser'       => ROOT_PATH . LIB_RELPATH . 'rdf/rdf_parser.php',
);

// Define the target applications that the site will accomidate with
// the enabled site features
const TARGET_APPLICATION_SITE = array(
  'palemoon' => array(
    'enabled'       => true,
    'name'          => 'Pale Moon - Add-ons',
    'domain'        => array('live' => 'addons.palemoon.org', 'dev' => 'addons-dev.palemoon.org'),
    'features'      => array('https', 'extensions', 'extensions-cat', 'themes',
                             'personas', 'language-packs', 'search-plugins')
  ),
  'basilisk' => array(
    'enabled'       => true,
    'name'          => 'Basilisk: add-ons',
    'domain'        => array('live' => 'addons.basilisk-browser.org', 'dev' => null),
    'features'      => array('https', 'extensions', 'themes', 'personas', 'search-plugins')
  ),
  'ambassador' => array(
    'enabled'       => true,
    'name'          => 'Add-ons - Ambassador',
    'domain'        => array('live' => 'ab-addons.thereisonlyxul.org', 'dev' => null),
    'features'      => array('extensions', 'themes', 'disable-xpinstall')
  ),
  'borealis' => array(
    'enabled'       => false,
    'name'          => 'Borealis Add-ons - Binary Outcast',
    'domain'        => array('live' => 'borealis-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('extensions', 'search-plugins')
  ),
  'interlink' => array(
    'enabled'       => true,
    'name'          => 'Interlink Add-ons - Binary Outcast',
    'domain'        => array('live' => 'interlink-addons.binaryoutcast.com', 'dev' => null),
    'features'      => array('extensions', 'themes', 'disable-xpinstall')
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


// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Basic Content Generation using the Special Component's Template
***********************************************************************************************************************/
function funcGenerateContent($aTitle, $aContent, $aTextBox = null, $aList = null, $aError = null) {
  $templateHead = @file_get_contents('./components/special/skin/default/template-header.xhtml');
  $templateFooter = @file_get_contents('./components/special/skin/default/template-footer.xhtml');

  // Make sure the template isn't busted, if it is send a text only error as an array
  if (!$templateHead || !$templateFooter) {
    funcError([__FUNCTION__ . ': Special Template is busted...', $aTitle, $aContent], -1);
  }

  // Can't use both the textbox and list arguments
  if ($aTextBox && $aList) {
    funcError(__FUNCTION__ . ': You cannot use both textbox and list');
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

  // If we are generating an error from funcError we want to clean the output buffer
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
* Error function that will display data (Error Message)
**********************************************************************************************************************/
function funcError($aValue, $aMode = 0) {
  $varExport  = var_export($aValue, true);
  $jsonEncode = json_encode($aValue, 448); // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
  
  $pageHeader = array(
    'default' => 'Unable to Comply',
    'fatal'   => 'Fatal Error',
    'php'     => 'PHP Error',
    'output'  => 'Output'
  );

  switch($aMode) {
    case -1:
      // Text only
      header('Content-Type: text/plain', false);
      if (is_string($aValue) || is_int($aValue)) {
        print($aValue);
      }
      else {
        print($varExport);
      }
      break;
    case 1:
      funcGenerateContent($pageHeader['php'], $aValue, null, true, true);
      break;
    case 98:
      // Depercated, use funcGenerateContent
      funcGenerateContent($pageHeader['output'], $jsonEncode, true);
      break;
    case 99:
      // Depercated, use funcGenerateContent
      funcGenerateContent($pageHeader['output'], $varExport, true);
      break;
    default:
      funcGenerateContent($pageHeader['default'], $aValue, null, true, true);
  }

  exit();
}

/**********************************************************************************************************************
* PHP Error Handler
**********************************************************************************************************************/

function funcPHPErrorHandler($errno, $errstr, $errfile, $errline) {
  $errorCodes = array(
    E_ERROR => 'Fatal Error',
    E_WARNING => 'Warning',
    E_PARSE => 'Parse',
    E_NOTICE => 'Notice',
    E_CORE_ERROR => 'Fatal Error (Core)',
    E_CORE_WARNING => 'Warning (Core)',
    E_COMPILE_ERROR => 'Fatal Error (Compile)',
    E_COMPILE_WARNING => 'Warning (Compile)',
    E_USER_ERROR => 'Fatal Error (User Generated)',
    E_USER_WARNING => 'Warning (User Generated)',
    E_USER_NOTICE => 'Notice (User Generated)',
    E_STRICT => 'Strict',
    E_RECOVERABLE_ERROR => 'Fatal Error (Recoverable)',
    E_DEPRECATED => 'Depercated',
    E_USER_DEPRECATED => 'Depercated (User Generated)',
    E_ALL => 'All',
  );

  $errorType = $errorCodes[$errno] ?? $errno;
  $errorMessage = $errorType . ': ' . $errstr . ' in ' .
                  str_replace(ROOT_PATH, '', $errfile) . ' on line ' . $errline;

  if (error_reporting() !== 0) {
    funcError($errorMessage, 1);
  }
}

set_error_handler("funcPHPErrorHandler");

/**********************************************************************************************************************
* Unified Var Checking
*
* @param $_type           Type of var to check
* @param $_value          GET/PUT/SERVER/FILES/EXISTING Normal Var
* @param $_allowFalsy     Optional - Allow falsey returns (really only works with case var)
* @returns                Value or null
**********************************************************************************************************************/
function funcUnifiedVariable($_type, $_value, $_allowFalsy = null) {
  $finalValue = null;

  switch ($_type) {
    case 'get':
      $finalValue = $_GET[$_value] ?? null;

      if ($finalValue) {
        $_finalValue = preg_replace('/[^-a-zA-Z0-9_\-\/\{\}\@\.\%\s\,]/', '', $_GET[$_value]);
      }

      break;
    case 'post':
      $finalValue = $_POST[$_value] ?? null;
      break;
    case 'server':
      $finalValue = $_SERVER[$_value] ?? null;
      break;
    case 'files':
      $finalValue = $_FILES[$_value] ?? null;
      if ($finalValue) {
        if (!in_array($finalValue['error'], [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE])) {
          funcError('Upload of ' . $_value . ' failed with error code: ' . $finalValue['error']);
        }

        if ($finalValue['error'] == UPLOAD_ERR_NO_FILE) {
          $finalValue = null;
        }
        else {
          $finalValue['type'] = mime_content_type($finalValue['tmp_name']);
        }
      }
      break;
    case 'cookie':
      $finalValue = $_COOKIE[$_value] ?? null;
    case 'var':
      $finalValue = $_value ?? null;
      break;
    default:
      funcError('Incorrect var check');
  }

  if (!$_allowFalsy && (empty($finalValue) || $finalValue === 'none' || $finalValue === '')) {
    return null;
  }

  return $finalValue;
}

/**********************************************************************************************************************
* Check if a module is in $arrayIncludes
*
* @param $_value    A module
* @returns          true or null depending on if $_value is in $arrayIncludes
**********************************************************************************************************************/
function funcCheckModule($_value) {
  if (!array_key_exists('arrayIncludes', $GLOBALS)) {
    funcError('$arrayIncludes is not defined');
  }
  
  if (!in_array($_value, $GLOBALS['arrayIncludes'])) {
    return null;
  }
  
  return true;
}

/**********************************************************************************************************************
* Sends HTTP Headers to client using a short name
*
* @param $_value    Short name of header
**********************************************************************************************************************/
function funcSendHeader($_value) {
  $_arrayHeaders = array(
    '404'           => 'HTTP/1.0 404 Not Found',
    '501'           => 'HTTP/1.0 501 Not Implemented',
    'html'          => 'Content-Type: text/html',
    'text'          => 'Content-Type: text/plain',
    'xml'           => 'Content-Type: text/xml',
    'json'          => 'Content-Type: application/json',
    'css'           => 'Content-Type: text/css',
    'phoebus'       => 'X-Phoebus: https://github.com/Pale-Moon-Addons-Team/phoebus/',
  );
  
  if (!headers_sent() && array_key_exists($_value, $_arrayHeaders)) {
    header($_arrayHeaders['phoebus']);
    header($_arrayHeaders[$_value]);
    
    if ($_value == '404' || $_value == '501') {
      // We are done here
      exit();
    }
  }
}

/**********************************************************************************************************************
* Sends HTTP Header to redirect the client to another URL
*
* @param $_strURL   URL to redirect to
**********************************************************************************************************************/
// This function sends a redirect header
function funcRedirect($_strURL) {
	header('Location: ' . $_strURL , true, 302);
  
  // We are done here
  exit();
}

// --------------------------------------------------------------------------------------------------------------------

/**********************************************************************************************************************
* Sends a 404 error but does it depending on debug mode
***********************************************************************************************************************/
function funcSend404() {
  if (!$GLOBALS['arraySoftwareState']['debugMode']) {
    funcSendHeader('404');
  }
  funcError('404 - Not Found');
}

/**********************************************************************************************************************
* Polyfills for missing functions
* startsWith, endsWith, contains
*
* @param $haystack  string
* @param $needle    substring
* @returns          true if substring exists in string else false
**********************************************************************************************************************/

function startsWith($haystack, $needle) {
   $length = strlen($needle);
   return (substr($haystack, 0, $length) === $needle);
}

// --------------------------------------------------------------------------------------------------------------------

function endsWith($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
    return true;
  }

  return (substr($haystack, -$length) === $needle);
}

// --------------------------------------------------------------------------------------------------------------------

function contains($haystack, $needle) {
  if (strpos($haystack, $needle) > -1) {
    return true;
  }
  else {
    return false;
  }
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$arraySoftwareState = array(
  'authentication'      => null,
  'currentApplication'  => null,
  'orginalApplication'  => null,
  'currentName'         => null,
  'currentScheme'       => funcUnifiedVariable('server', 'SCHEME'),
  'currentDomain'       => null,
  'debugMode'           => null,
  'phpServerName'       => funcUnifiedVariable('server', 'SERVER_NAME'),
  'phpRequestURI'       => funcUnifiedVariable('server', 'REQUEST_URI'),
  'requestComponent'    => funcUnifiedVariable('get', 'component'),
  'requestPath'         => funcUnifiedVariable('get', 'path'),
  'requestApplication'  => funcUnifiedVariable('get', 'appOverride'),
  'requestDebugOff'     => funcUnifiedVariable('get', 'debugOff'),
  'requestSearchTerms'  => funcUnifiedVariable('get', 'terms')
);

// --------------------------------------------------------------------------------------------------------------------

// If the entire site is offline but nothing above is busted.. We want to serve proper but empty responses
if (file_exists(ROOT_PATH . '/.offline' && !funcUnifiedVariable('cookie', 'overrideOffline')) {
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
    case 'discover':
      funcSend404();
    default:
      funcError($strOfflineMessage);
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Decide which application by domain that the software will be serving
// and if debug is enabled
foreach (TARGET_APPLICATION_SITE as $_key => $_value) {
  switch ($arraySoftwareState['phpServerName']) {
    case $_value['domain']['live']:
      $arraySoftwareState['currentApplication'] = $_key;
      $arraySoftwareState['currentDomain'] = $_value['domain']['live'];
      break;
    case $_value['domain']['dev']:
      $arraySoftwareState['currentApplication'] = $_key;
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
    if (array_key_exists($arraySoftwareState['requestApplication'], TARGET_APPLICATION_SITE)) {
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
if (!TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['enabled']) {
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