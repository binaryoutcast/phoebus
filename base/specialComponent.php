<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks the exploded count against the number of path parts in an exploded path and 404s it if it is greater
***********************************************************************************************************************/
function gfCheckPathCount($aExpectedCount) {
  global $gaRuntime;
  if (count($gaRuntime['explodedPath']) > $aExpectedCount) {
    gfHeader(404);
  }
}

// == | Main | ========================================================================================================

// Catch the former content test cases early
if (gfSuperVar('get', 'case') == 'content') {
  gfRedirect(gfBuildPath('special', 'content-test'));
}

// Explode the path
$gaRuntime['explodedPath'] = gfExplodePath($gaRuntime['qPath']);

// The Special Component never has more than one level below it
// We still have to determin the root of the component though...
if (count($gaRuntime['explodedPath']) == 1) {
  $gvSpecialFunction = 'root';
}
else {
  gfCheckPathCount(2);
  $gvSpecialFunction = $gaRuntime['explodedPath'][1];
}

switch ($gvSpecialFunction) {
  case 'root':
    $rootHTML = '<a href="/special/validator/">Add-on Validator</a></li><li>' .
    $rootHTML = '<a href="/special/test/">Test Cases</a></li><li>' .
                '<a href="/special/phpinfo/">PHP Info</a></li><li>' .
                '<a href="/special/software-state/">Authenticated Software State</a>';
    gfGenContent('Special Component', $rootHTML, null, true);
  case 'validator':
    gfImportModules('database', 'account', 'mozillaRDF', 'readManifest', 'writeManifest');

    if ($_POST ?? false) {
      $result = $gmWriteManifest->publicValidator();
      gfGenContent('Validator Result', $result);
    }

    $content = '<form method="POST" accept-charset="UTF-8" autocomplete="off" enctype="multipart/form-data">' .
               '<input type="file" name="xpiUpload" />' .
               '<input type="hidden" name="slug" value="1" />' .
               '<input type="submit" value="Upload" />' .
               '</form>';

    gfGenContent('Validator Test', $content);
  case 'test':
    $gaRuntime['qTestCase'] = gfSuperVar('get', 'case');
    $arrayTestsGlob = glob('./base/tests/*.php');
    $arrayFinalTests = EMPTY_ARRAY;

    foreach ($arrayTestsGlob as $_value) {
      $arrayFinalTests[] = str_replace('.php', '', str_replace('./base/tests/', '', $_value));
    }

    unset($arrayTestsGlob);

    if ($gaRuntime['qTestCase']) {
      if (!in_array($gaRuntime['qTestCase'], $arrayFinalTests)) {
        gfError('Unknown test case');
      }

      require_once('./base/tests/' . $gaRuntime['qTestCase'] . '.php');
    }

    $testsHTML = EMPTY_STRING;

    foreach ($arrayFinalTests as $_value) {
      $testsHTML .= '<li><a href="/special/test/?case=' . $_value . '">' . $_value . '</a></li>';
    }

    $testsHTML = '<ul>' . $testsHTML . '</ul>';

    gfGenContent('Test Cases', $testsHTML);
    break;
  case 'phpinfo':
    gfHeader('html');
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_ENVIRONMENT | INFO_VARIABLES);
    break;
  case 'software-state':
    gfImportModules('database', 'account');
    $gmAccount->authenticate();
    gfGenContent('Authenticated Software State', $gaRuntime);
    break;
  default:
    gfHeader(404);
}

// ====================================================================================================================

?>