<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// URI Constants
const URI_ROOT            = '/';
const URI_ADDON_PAGE      = '/addon/';
const URI_ADDON_RELEASES  = '/releases/';
const URI_ADDON_LICENSE   = '/license/';
const URI_EXTENSIONS      = '/extensions/';
const URI_THEMES          = '/themes/';
const URI_PERSONAS        = '/personas/';
const URI_SEARCHPLUGINS   = '/search-plugins/';
const URI_LANGPACKS       = '/language-packs/';
const URI_DICTIONARIES    = '/dictionaries/';
const URI_SEARCH          = '/search/';

// Include modules
$arrayIncludes = ['database', 'oldReadManifest', 'persona', 'generateContent'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();
$modulePersona = new classPersona;
$moduleGenerateContent = new classGenerateContent(true);

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Strips path to obtain the slug
*
* @param $aPath     $gaRuntime['requestPath']
* @param $aPrefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function funcStripPath($aPath, $aPrefix) {
  return str_replace('/', '', str_replace($aPrefix, '', $aPath));
}

/**********************************************************************************************************************
* Checks for enabled features
*
* @param $aFeature    feature
* @param $aReturn     if true we will return a value else 404
***********************************************************************************************************************/
function funcCheckEnabledFeature($aFeature, $aReturn = null) {
  $currentApplication = $GLOBALS['gaRuntime']['currentApplication'];
  if (!in_array($aFeature, TARGET_APPLICATION[$currentApplication]['features'])) {
    if(!$aReturn) {
      gfHeader(404);
    }

    return null;
  }

  return true;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Site Name
$gaRuntime['currentSiteTitle'] = TARGET_APPLICATION[$gaRuntime['currentApplication']]['siteTitle'];

// When in debug mode it displays the software name and version and if git
// is detected it will append the branch and short sha1 hash
// else it will use the name defined in TARGET_APPLICATION
if ($gaRuntime['debugMode']) {
  $gaRuntime['currentSiteTitle'] = SOFTWARE_NAME . ' Development - Version: ' . SOFTWARE_VERSION;
  // Git stuff
  if (file_exists('./.git/HEAD')) {
    $_strGitHead = file_get_contents('./.git/HEAD');
    $_strGitSHA1 = file_get_contents('./.git/' . substr($_strGitHead, 5, -1));
    $_strGitBranch = substr($_strGitHead, 16, -1);
    $gaRuntime['currentSiteTitle'] = 
      $gaRuntime['currentSiteTitle'] . ' - ' .
      'Branch: ' . $_strGitBranch . ' - ' .
      'Commit: ' . substr($_strGitSHA1, 0, 7);
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Handle URIs
switch ($gaRuntime['requestPath']) {
  case URI_ROOT:
    // Special Case: Interlink should go to Extensions instead of a front page
    if ($gaRuntime['currentApplication'] == 'interlink') {
      gfRedirect('/extensions/');
    }

    // Front Page
    // Generate the frontpage from SITE content
    $moduleGenerateContent->addonSite(
      $gaRuntime['currentApplication'] . '-frontpage.xhtml', 'Explore Add-ons'
    );
    break;
  case URI_SEARCH:
    // Search Page
    // Send the search terms to SQL
    $gaRuntime['requestSearchTerms'] = str_replace('*', '', $gaRuntime['requestSearchTerms']);
    $searchManifest = $moduleReadManifest->getAddons('site-search', $gaRuntime['requestSearchTerms']);

    // If no results generate a page indicating that
    if (!$searchManifest) {
      $moduleGenerateContent->addonSite('search', 'No search results');
    }

    // We have results so generate the page with them
    $moduleGenerateContent->addonSite('search',
      'Search results for "' . $gaRuntime['requestSearchTerms'] . '"',
      $searchManifest
    );
    break;
  case URI_EXTENSIONS:
    // Extensions Category (Top Level)
    // Find out if we should use Extension Subcategories or All Extensions
    $gaRuntime['requestAllExtensions'] = gfSuperVar('get', 'all');
    $useExtensionSubcategories = funcCheckEnabledFeature('extensions-cat', true);

    if ($useExtensionSubcategories && !$gaRuntime['requestAllExtensions']) {
      // We are using Extension Subcategories so generate a page that lists all the subcategories
      $moduleGenerateContent->addonSite('cat-extension-category',
                                        'Extensions',
                                        classReadManifest::EXTENSION_CATEGORY_SLUGS);
    }

    // We are doing an "All Extensions" Page
    // Get all extensions from SQL
    $categoryManifest = $moduleReadManifest->getAddons('site-all-extensions');

    // If there are no extensions then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // Generate the "All Extensions" Page
    $moduleGenerateContent->addonSite('cat-all-extensions',
                                      'Extensions',
                                      $categoryManifest,
                                      classReadManifest::EXTENSION_CATEGORY_SLUGS);
    break;
  case URI_THEMES:
    // Themes Category
    // Check if Themes are enabled
    funcCheckEnabledFeature('themes');

    // Query SQL and get all themes
    $categoryManifest = $moduleReadManifest->getAddons('site-addons-by-category', 'themes');

    // If there are no themes then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have themes so generate the page
    $moduleGenerateContent->addonSite('cat-themes', 'Themes', $categoryManifest);
    break;
  case URI_PERSONAS:
    // Personas Category
    // Check if Personas are enabled
    funcCheckEnabledFeature('personas');

    // Query SQL and get all personas
    $categoryManifest = $modulePersona->getPersonas('site-all-personas');

    // If there are no Personas then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have personas so generate the page
    $moduleGenerateContent->addonSite('cat-personas', 'Personas', $categoryManifest);
    break;
  case URI_LANGPACKS:
    // Language Packs Category
    // See if LangPacks are enabled
    funcCheckEnabledFeature('language-packs');

    // Query SQL for langpacks
    $categoryManifest = $moduleReadManifest->getAddons('site-addons-by-category', 'language-packs');

    // If there are no langpacks then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have langpacks so generate the page
    $moduleGenerateContent->addonSite('cat-language-packs', 'Language Packs', $categoryManifest);
    break;
  case URI_DICTIONARIES:
    gfRedirect('https://addons.thunderbird.net/en-US/thunderbird/language-tools/');
    break;
  case URI_SEARCHPLUGINS:
    // Search Engine Plugins Category
    // See if Search Engine Plugins are enabled
    funcCheckEnabledFeature('search-plugins');

    // Get an array of hardcoded Search Engine Plugins from readManifest
    $categoryManifest = $moduleReadManifest->getSearchPlugins();

    // If for some reason there aren't any even though there is no error checking in the method, 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // Generate the Search Engine Plugins category page
    $moduleGenerateContent->addonSite('cat-search-plugins', 'Search Plugins', $categoryManifest);
    break;
  case URI_ADDON_PAGE:
  case URI_ADDON_RELEASES:
  case URI_ADDON_LICENSE:
    // These have no content so send the client back to root
    gfRedirect(URI_ROOT);
    break;
  default:
    // Complex URIs need more complex conditional checking
    // Extension Subcategories
    if (startsWith($gaRuntime['requestPath'], URI_EXTENSIONS)) {
      // Check if Extension Subcategories are enabled
      funcCheckEnabledFeature('extensions-cat');

      // Strip the path to get the slug
      $strSlug = funcStripPath($gaRuntime['requestPath'], URI_EXTENSIONS);

      // See if the slug exists in the category array
      if (!array_key_exists($strSlug, classReadManifest::EXTENSION_CATEGORY_SLUGS)) {
        gfRedirect('/addon/' . $strSlug);
      }

      // Query SQL for extensions in this specific category
      $categoryManifest = $moduleReadManifest->getAddons('site-addons-by-category', $strSlug);
      
      // If there are no extensions then 404
      if (!$categoryManifest) {
        gfHeader(404);
      }

      // We have extensions so generate the subcategory page
      $moduleGenerateContent->addonSite('cat-extensions',
                                        'Extensions: ' . classReadManifest::EXTENSION_CATEGORY_SLUGS[$strSlug],
                                        $categoryManifest, classReadManifest::EXTENSION_CATEGORY_SLUGS);
    }
    // Add-on Page
    elseif (startsWith($gaRuntime['requestPath'], URI_ADDON_PAGE)) {
      // Strip the path to get the slug
      $strSlug = funcStripPath($gaRuntime['requestPath'], URI_ADDON_PAGE);

      // Query SQL for the add-on
      $addonManifest = $moduleReadManifest->getAddon('by-slug', $strSlug);

      // If there is no add-on, 404
      if (!$addonManifest) {
        gfHeader(404);
      }

      // Generate the Add-on Releases Page
      $moduleGenerateContent->addonSite('addon-page', $addonManifest['name'], $addonManifest);
    }
    // Add-on Releases
    elseif (startsWith($gaRuntime['requestPath'], URI_ADDON_RELEASES)) {
      // Strip the path to get the slug
      $strSlug = funcStripPath($gaRuntime['requestPath'], URI_ADDON_RELEASES);

      // Query SQL for the add-on
      $addonManifest = $moduleReadManifest->getAddon('by-slug', $strSlug);

      // If there is no add-on, 404
      if (!$addonManifest) {
        gfHeader(404);
      }

      // Generate the Add-on Releases Page
      $moduleGenerateContent->addonSite('addon-releases', $addonManifest['name'] . ' - Releases', $addonManifest);
    }
    // Add-on License
    elseif (startsWith($gaRuntime['requestPath'], URI_ADDON_LICENSE)) {
      // Strip the path to get the slug
      $strSlug = funcStripPath($gaRuntime['requestPath'], URI_ADDON_LICENSE);

      // Query SQL for the add-on
      $addonManifest = $moduleReadManifest->getAddon('by-slug', $strSlug);

      // If there is no add-on, 404
      if (!$addonManifest) {
        gfHeader(404);
      }

      // If there is a licenseURL then redirect to it
      if ($addonManifest['licenseURL']) {
        gfRedirect($addonManifest['licenseURL']);
      }
      
      // If the license is public domain, copyright, or custom then we want to generate a page for it
      if ($addonManifest['license'] == 'pd' || $addonManifest['license'] == 'copyright' ||
          $addonManifest['license'] == 'custom') {
        // If we have licenseText we want to convert newlines to xhtml line breaks
        if ($addonManifest['licenseText']) {
          $addonManifest['licenseText'] = nl2br($addonManifest['licenseText'], true);
        }

        // Smarty will handle displaying content for these license types
        $moduleGenerateContent->addonSite('addon-license', $addonManifest['name'] . ' - License', $addonManifest);
      }

      // The license is assumed to be an OSI license so redirect there
      gfRedirect('https://opensource.org/licenses/' . $addonManifest['license']);
    }

    // There are no matches so 404
    gfHeader(404); 
}

// ====================================================================================================================

?>