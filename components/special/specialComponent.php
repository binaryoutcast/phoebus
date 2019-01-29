<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Strips path to obtain the slug
*
* @param $aPath     $arraySoftwareState['requestPath']
* @param $aPrefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function funcStripPath($aPath, $aPrefix) {
  return str_replace('/', '', str_replace($aPrefix, '', $aPath));
}

/**********************************************************************************************************************
* Strips path to obtain the slug
***********************************************************************************************************************/
function funcCheckDebug() {
  if (!$GLOBALS['arraySoftwareState']['debugMode']) {
    funcRedirect('/');
  }
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';
$strStripPath = funcStripPath($arraySoftwareState['requestPath'], '/special/');
$strSkinPath = $strComponentPath . '/skin/';

// --------------------------------------------------------------------------------------------------------------------

switch ($strStripPath) {
  case 'phpinfo':
    phpinfo();
    break;
  case 'phpvars':
    phpinfo(32);
    break;
  case 'softwareState':
    funcCheckDebug();
    $arrayIncludes = ['database', 'account'];
    foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }
    $moduleDatabase = new classDatabase();
    $moduleAccount = new classAccount();
    $moduleAccount->authenticate();
    funcError($arraySoftwareState, 98);
    break;
  case 'migrator':
    funcCheckDebug();
    if (file_exists(ROOT_PATH . '/.migration')) {
      require_once($strComponentPath . 'addonMigrator.php');
    }
    else {
      funcRedirect('/');
    }
    break;
  case 'test':
    funcCheckDebug();
    $arraySoftwareState['requestTestCase'] = funcUnifiedVariable('get', 'case');
    $arrayTestsGlob = glob($strComponentPath . 'tests/*.php');
    $arrayFinalTests = [];
    foreach ($arrayTestsGlob as $_value) {
      $arrayFinalTests[] = str_replace('.php',
                                       '',
                                       str_replace($strComponentPath . 'tests/', '', $_value));
    }
    if ($arraySoftwareState['requestTestCase'] &&
        in_array($arraySoftwareState['requestTestCase'], $arrayFinalTests)) {
      require_once($strComponentPath . 'tests/' . $arraySoftwareState['requestTestCase'] . '.php');
    }
    else {
      funcError('Invalid test case');
    }
    break;
  default:
    funcRedirect('/');
}

exit();

// ====================================================================================================================

?>