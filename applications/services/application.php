<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | application.php | =====================================================

// This file defines the general entry point of the application. It should not
// contain more than the basic operational logic for defining universal
// variables, components, and modules as well the default component

// ============================================================================

// == | Vars | ================================================================

// Define application paths
$strApplicationPath = $strRootPath . '/applications/services/';
$strComponentsPath = $strApplicationPath . 'components/';
$strModulesPath = $strApplicationPath . 'modules/';

// Define Components
$arrayComponents = array(
    'aus' => $strComponentsPath . 'aus.php',
    'integration' => $strComponentsPath . 'integration.php'
);

// Define Modules
$arrayModules = array(
    'readManifest' => $strRootPath . '/applications/frontend/modules/funcReadManifest.php'
);

// ============================================================================

// == | Main | ================================================================

// Merge Platform Components and Modules into Application Components and Modules
// and unset
$arrayComponents = array_merge($arrayComponents, $arrayPlatformComponents);
$arrayModules = array_merge($arrayModules, $arrayPlatformModules);
unset($arrayPlatformComponents);
unset($arrayPlatformModules);

if ($strRequestComponent == 'site') {
    funcSendHeader('404');
}

// ============================================================================
?>