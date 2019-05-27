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

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';
$strStripPath = funcStripPath($arraySoftwareState['requestPath'], '/special/');

if (!$arraySoftwareState['debugMode']) {
  if ($strStripPath != 'phpinfo') {
    funcRedirect('/');
  }
}

// --------------------------------------------------------------------------------------------------------------------

switch ($strStripPath) {
  case 'phpinfo':
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_ENVIRONMENT | INFO_VARIABLES);
    break;
  case 'software-state':
    $arrayIncludes = ['database', 'account'];
    foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }
    $moduleDatabase = new classDatabase();
    $moduleAccount = new classAccount();
    $moduleAccount->authenticate();
    funcGenerateContent('Authenticated Software State', $arraySoftwareState);
    break;
  case 'restructure':
    require_once($strComponentPath . 'migrateRestructure.php');
    break;
  case 'test':
    $arraySoftwareState['requestTestCase'] = funcUnifiedVariable('get', 'case');
    $arrayTestsGlob = glob($strComponentPath . 'tests/*.php');
    $arrayFinalTests = [];

    foreach ($arrayTestsGlob as $_value) {
      $arrayFinalTests[] = str_replace('.php',
                                       '',
                                       str_replace($strComponentPath . 'tests/', '', $_value));
    }

    unset($arrayTestsGlob);

    if ($arraySoftwareState['requestTestCase'] &&
        in_array($arraySoftwareState['requestTestCase'], $arrayFinalTests)) {
      require_once($strComponentPath . 'tests/' . $arraySoftwareState['requestTestCase'] . '.php');
    }

    $testsHTML = '';

    foreach ($arrayFinalTests as $_value) {
      $testsHTML .= '<li><a href="/special/test/?case=' . $_value . '">' . $_value . '</a></li>';
    }

    $testsHTML = '<ul>' . $testsHTML . '</ul>';

    funcGenerateContent('Test Cases - Special Component', $testsHTML);
    break;
  default:
    $rootHTML = '<a href="/special/restructure/">Restructure SQL Data</a></li><li>' . 
                '<a href="/special/test/">Test Cases</a></li><li>' .
                '<a href="/special/phpinfo/">PHP Info</a></li><li>' .
                '<a href="/special/software-state/">Authenticated Software State</a>';
    funcGenerateContent('Special Component', $rootHTML, null, true);
}

exit();

// ====================================================================================================================

?>