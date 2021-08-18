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

// Enable Smarty Content Generation
$gaRuntime['useSmarty'] = true;

// Include modules
gfImportModules('database', 'account', 'log', 'mozillaRDF', 'vc', 'readManifest', 'writeManifest', 'generateContent');

// Request arguments
$gaRuntime['qPanelTask'] = gfSuperVar('get', 'task');
$gaRuntime['qPanelWhat'] = gfSuperVar('get', 'what');
$gaRuntime['qPanelSlug'] = gfSuperVar('get', 'slug');

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks user level
*
* @param $_level    Required level
* @returns          true 404
***********************************************************************************************************************/
function gfCheckAccessLevel($aLevel, $aReturnNull = null) {
  global $gaRuntime;

  if ($gaRuntime['authentication']['level'] >= $aLevel) {
    return true;
  }

  if (!$aReturnNull) {
    gfRedirect('/panel/login/');
  }

  return null;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

if (file_exists(ROOT_PATH . '/.disablePanel') && !gfSuperVar('cookie', 'overrideDisablePanel')) {
  gfError('The Panel is currently disabled. Please try again later.');
}

$strComponentPath = dirname(COMPONENTS[$gaRuntime['qComponent']]) . '/';
$boolHasPostData = !empty($_POST);

// --------------------------------------------------------------------------------------------------------------------

// The Panel can ONLY be used on HTTPS so redirect those sites without https to Pale Moon
if (!in_array('https', TARGET_APPLICATION_SITE[$gaRuntime['currentApplication']]['features'])) {
  gfRedirect('https://addons.palemoon.org/panel/');
}

if ($gaRuntime['currentScheme'] != 'https') {
  gfRedirect('https://' . $gaRuntime['currentDomain'] . '/panel/');
}

// --------------------------------------------------------------------------------------------------------------------

// Handle URIs
switch ($gaRuntime['qPath']) {
  case URI_PANEL:
    $gmGenerateContent->addonSite('panel-frontpage.xhtml', 'Landing Page');
    break;
  case URI_REG:
    if ($boolHasPostData) {
      $boolRegComplete = $gmAccount->registerUser();

      if (!$boolRegComplete) {
        gfError('Something has gone horribly wrong!');
      }

      $gmGenerateContent->addonSite('panel-account-registration-done', 'Registration Complete', $gmAccount->validationEmail);
    }

    $gmGenerateContent->addonSite('panel-account-registration', 'Registration');
    break;
  case URI_VERIFY:
    if ($boolHasPostData) {
      $boolVerificationComplete = $gmAccount->verifyUser();

      if (!$boolVerificationComplete) {
        gfError('Something has gone horribly wrong!');
      }

      gfRedirect(URI_LOGIN);
    }
    $gmGenerateContent->addonSite('panel-account-validation', 'Account Verification');
    break;
  case URI_LOGIN:
    $gmAccount->authenticate();
    if (gfCheckAccessLevel(3, true)) {
      gfRedirect(URI_ADMIN);
    }
    gfRedirect(URI_DEV);
    break;
  case URI_LOGOUT:
    $gmAccount->authenticate('logout');
    break;
  case URI_DEV:
  case URI_ACCOUNT:
  case URI_ADDONS:
    $gmAccount->authenticate();
    gfCheckAccessLevel(1);
    require_once($strComponentPath . 'developer.php');
    break;
  default:
    if (str_starts_with($gaRuntime['qPath'], URI_ADMIN)){
      $gmAccount->authenticate();
      gfCheckAccessLevel(3);
      require_once($strComponentPath . 'administration.php');
    }

    // No clue send 404
    gfHeader(404);
}

// ====================================================================================================================

?>
