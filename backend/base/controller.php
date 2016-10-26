<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Vars | ================================================================

$arrayComponents = array(
    'aus' => './components/aus.php',
    'download' => './components/download.php',
    'integration' => './components/integration.php',
    'metadata' => './components/metadata.php',
    'discover' => './components/discover.php',
);

$arrayModules = array(
    'database' => './modules/database.php',
    'vc' => './modules/nsIVersionComparator.php'
);

$strRequestFunction = funcHTTPGetValue('function');

// ============================================================================

// == | Main | ================================================================

if ($strRequestFunction != null) {
    if (array_key_exists($strRequestFunction, $arrayComponents)) {
        include_once($arrayComponents[$strRequestFunction]);
    }
    else {
        funcError($strRequestFunction . ' is an unknown controller function');
    }
}
else {
    funcError('You did not specify a controller function');
}

// ============================================================================
?>

