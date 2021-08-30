<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ================================================================

$strApplication = null;
$strPromoText = null;
$strDivTextAlign = 'center';

switch ($gaRuntime['currentApplication']) {
  case 'palemoon':
    $strApplication = 'Pale Moon';
    $strAppType = 'browser';
    $strDivTextAlign = 'left';
    $strPromoText = ' the following <a href="https://' . 
                    $gaRuntime['currentDomain'] . 
                    '/" target="_blank">Add-ons Site</a> categories';
    break;
  case 'basilisk':
    $strAppType = 'browser';
    $strPromoText = ' your choice of the following repositories';
    break;
  case 'borealis':
    $strAppType = 'navigator';
    break;
  case 'interlink':
    $strAppType = 'client';
    break;
  default:
    $strAppType = 'application';
}

if (!$strApplication) {
  $strApplication = ucfirst($gaRuntime['currentApplication']);
}

$strPromoTextPre = 'You can take advantage of ' . $strApplication . '\'s exceptional extensibility by installing add-ons from';
$strComponentPath = str_replace(ROOT_PATH, '', dirname(COMPONENTS['discover']));

$strAMOButton = '<a class="amobutton" href="http://' . $gaRuntime['currentDomain'] . '/" target="_blank">' .
                '<img class="alignleft" src="' . $strComponentPath . '/skin/' . $gaRuntime['currentApplication'] . '.png?{%EPOCH}" />' .
                '<p><strong>' . $strApplication . ' Add-ons Site</strong></p>' .
                '<p><small>Browse add-ons for ' . $strApplication . '</small></p>' .
                '</a>';

$strPageButtons = '';


if (in_array('extensions-cat', TARGET_APPLICATION_SITE[$gaRuntime['currentApplication']]['features']) &&
    $gaRuntime['currentApplication'] == 'palemoon') {
  foreach (array_merge(EXTENSION_CATEGORY_SLUGS, OTHER_CATEGORY_SLUGS) as $_key => $_value) {
    $strCategoryURL = 'https://' . $gaRuntime['currentDomain'] . '/';
    switch ($_key) {
      case 'themes':
        $_description = 'Complete Themes';
        break;
      case 'personas':
        $_description = 'Lightweight Themes';
        break;
      case 'search-plugins':
        $_description = 'OpenSearch Engines';
        break;
      case 'language-packs':
        $_description = $strApplication . ' in your language';
        break;
      default:
        $_description = 'Extensions';
        $strCategoryURL .= 'extensions/';
    }
    
    $_subst = array(
      '{%CATEGORY_URL}' => $strCategoryURL . $_key . '/',
      '{%CATEGORY_SLUG}' => $_key,
      '{%CATEGORY_NAME}' => $_value,
      '{%CATEGORY_DESCRIPTION}' => $_description
    );
    
    $_button = '<a class="amobutton" href="{%CATEGORY_URL}" target="_blank">' .
               '<img class="alignleft" src="' . str_replace(ROOT_PATH, '', dirname(COMPONENTS['site'])) . '/skin/shared/{%CATEGORY_SLUG}.png?{%EPOCH}" />' .
               '<p><strong>{%CATEGORY_NAME}</strong></p>' .
               '<p><small>{%CATEGORY_DESCRIPTION}</small></p>' .
               '</a>';
    
    foreach ($_subst as $_key2 => $_value2) {
      $_button = NEW_LINE . NEW_LINE . str_replace($_key2, $_value2, $_button);
    }
    
    $strPageButtons .= $_button;
  }
}
elseif ($gaRuntime['currentApplication'] == 'basilisk') {
$strPageButtons = $strAMOButton .
                '<a class="amobutton" href="https://github.com/JustOff/ca-archive/releases" target="_blank">' .
                '<img class="alignleft" src="' . $strComponentPath . '/skin/caa-extension.png?{%EPOCH}" />' .
                '<p><strong>Classic Add-ons Archive</strong></p>' .
                '<p><small>Catalog of classic Firefox Extensions</small></p>' .
                '</a>';
}
else {
  $strPageButtons = $strAMOButton;
}

if ($gaRuntime['currentApplication'] == 'palemoon') {
  $strHTMLTemplate = file_get_contents(ROOT_PATH . $strComponentPath . '/content/template-announcement.xhtml');
}
else {
  $strHTMLTemplate = file_get_contents(ROOT_PATH . $strComponentPath . '/content/template.xhtml');
}

$substs = array(
  '{%BASE_PATH}'              => $strComponentPath,
  '{%SITE_DOMAIN}'            => $gaRuntime['currentDomain'],
  '{%PAGE_TITLE}'             => 'Discover Add-ons for ' . $strApplication,
  '{%APPLICATION_NAME}'       => $strApplication,
  '{%APPLICATION_SHORTNAME}'  => $gaRuntime['currentApplication'],
  '{%APPLICATION_TYPE}'       => $strAppType,
  '{%PROMO_TEXT}'             => $strPromoTextPre . $strPromoText . ':',
  '{%DIV_TEXTALIGN}'          => $strDivTextAlign,
  '{%PAGE_BUTTONS}'           => $strPageButtons,
  '{%EPOCH}'                  => time()
);

$strHTMLTemplate = gfSubst('simple', $substs, $strHTMLTemplate);

gfHeader('html');
print($strHTMLTemplate);

// We are done here...
exit();

// ============================================================================

?>