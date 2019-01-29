<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ================================================================

$strApplication = ucfirst($arraySoftwareState['currentApplication']);

if ($strApplication == 'Palemoon') {
  $strApplication = 'Pale Moon';
}

$strAppType = 'application';

if ($strApplication == 'Pale Moon' || $strApplication == 'Basilisk') {
  $strAppType = 'browser';
}

if ($strApplication == 'Borealis') {
  $strAppType = 'navigator';
}

$strComponentPath = dirname(COMPONENTS['discover']);

$strHTMLTemplate = file_get_contents($strComponentPath . '/content/template.xhtml');   

$arrayFilterSubstitute = array(
  '{%BASE_PATH}' => str_replace(ROOT_PATH, '', $strComponentPath),
  '{SITE_DOMAIN}' => $arraySoftwareState['currentDomain'],
  '{%PAGE_TITLE}' => 'Discover Add-ons for ' . $strApplication,
  '{%APPLICATION_NAME}' => $strApplication,
  '{%APPLICATION_SHORTNAME}' => $arraySoftwareState['currentApplication'],
  '{%APPLICATION_TYPE}' => $strAppType,
  '{%EPOCH}' => time()
);

foreach ($arrayFilterSubstitute as $_key => $_value) {
  $strHTMLTemplate = str_replace($_key, $_value, $strHTMLTemplate);
}

funcSendHeader('html');
print($strHTMLTemplate);

// We are done here...
exit();

// ============================================================================

?>