<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Constants
const URI_PANEL                         = '/panel/';
const URI_REG                           = URI_PANEL . 'registration/';
const URI_VERIFY                        = URI_PANEL . 'verification/';
const URI_LOGIN                         = URI_PANEL . 'login/';
const URI_LOGOUT                        = URI_PANEL . 'logout/';
const URI_DEV                           = URI_PANEL . 'developer/';
const URI_ACCOUNT                       = URI_PANEL . 'account/';
const URI_ADDONS                        = URI_PANEL . 'addons/';
const URI_ADMIN                         = URI_PANEL . 'administration/';

// Include modules
$arrayIncludes = ['database', 'account', 'mozillaRDF', 'vc', 'oldReadManifest', 'writeManifest', 'generateContent'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase                         = new classDatabase();
$moduleAccount                          = new classAccount();
$moduleMozillaRDF                       = new classMozillaRDF();
$moduleReadManifest                     = new classReadManifest();
$moduleWriteManifest                    = new classWriteManifest();
$moduleGenerateContent                  = new classGenerateContent('smarty');

// Request arguments
$gaRuntime['requestPanelTask'] = gfSuperVar('get', 'task');
$gaRuntime['requestPanelWhat'] = gfSuperVar('get', 'what');
$gaRuntime['requestPanelSlug'] = gfSuperVar('get', 'slug');

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks user level
*
* @param $_level    Required level
* @returns          true 404
***********************************************************************************************************************/
function funcCheckAccessLevel($aLevel, $aReturnNull = null) {
  if ($GLOBALS['gaRuntime']['authentication']['level'] >= $aLevel) {
    return true;
  }

  if (!$aReturnNull) {
    funcRedirect('/panel/login/');
  }

  return null;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

if (file_exists(ROOT_PATH . '/.disablePanel')) {
  gfError('The Panel is currently unavailable. Please try again later.');
}

$strComponentPath = dirname(COMPONENTS[$gaRuntime['requestComponent']]) . '/';
$boolHasPostData = !empty($_POST);

// --------------------------------------------------------------------------------------------------------------------

// The Panel can ONLY be used on HTTPS so redirect those sites without https to Pale Moon
if (!in_array('https', TARGET_APPLICATION[$gaRuntime['currentApplication']]['features'])) {
  funcRedirect('https://' . TARGET_APPLICATION['palemoon']['domain']['live'] . '/panel/');
}

if ($gaRuntime['currentScheme'] != 'https') {
  funcRedirect('https://' . $gaRuntime['currentDomain'] . '/panel/');
}

// --------------------------------------------------------------------------------------------------------------------

// Handle URIs
switch ($gaRuntime['requestPath']) {
  case URI_PANEL:
    $moduleGenerateContent->addonSite('panel-frontpage.xhtml', 'Landing Page');
    break;
  case URI_REG:
    if ($boolHasPostData) {
      $boolRegComplete = $moduleAccount->registerUser();

      if (!$boolRegComplete) {
        gfError('Something has gone horribly wrong!');
      }

      $moduleGenerateContent->addonSite('panel-account-registration-done', 'Registration Complete', $moduleAccount->validationEmail);
    }

    $moduleGenerateContent->addonSite('panel-account-registration', 'Registration');
    break;
  case URI_VERIFY:
    if ($boolHasPostData) {
      $boolVerificationComplete = $moduleAccount->verifyUser();

      if (!$boolVerificationComplete) {
        gfError('Something has gone horribly wrong!');
      }

      funcRedirect(URI_LOGIN);
    }
    $moduleGenerateContent->addonSite('panel-account-validation', 'Account Verification');
    break;
  case URI_LOGIN:
    $moduleAccount->authenticate();
    if (funcCheckAccessLevel(3, true)) {
      funcRedirect(URI_ADMIN);
    }
    funcRedirect(URI_DEV);
    break;
  case URI_LOGOUT:
    $moduleAccount->authenticate('logout');
    break;
  case URI_DEV:
  case URI_ACCOUNT:
  case URI_ADDONS:
    $moduleAccount->authenticate();
    funcCheckAccessLevel(1);
    require_once($strComponentPath . 'developer.php');
    break;
  default:
    if (startsWith($gaRuntime['requestPath'], URI_ADMIN)){
      $moduleAccount->authenticate();
      funcCheckAccessLevel(3);
      require_once($strComponentPath . 'administration.php');
    }

    // No clue send 404
    gfHeader(404);
}

// ====================================================================================================================

?>
