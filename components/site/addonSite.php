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

// Enable Smarty Content Generation
$gaRuntime['useSmarty'] = true;

// Include modules
gfImportModules('database', 'readManifest', 'persona', 'generateContent');

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Strips path to obtain the slug
*
* @param $aPath     $gaRuntime['qPath']
* @param $aPrefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function gfStripPath($aPath, $aPrefix) {
  return str_replace('/', '', str_replace($aPrefix, '', $aPath));
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Site Name
$gaRuntime['currentName'] = TARGET_APPLICATION_SITE[$gaRuntime['currentApplication']]['name'];

// When in debug mode it displays the software name and version and if git
// is detected it will append the branch and short sha1 hash
// else it will use the name defined in TARGET_APPLICATION_SITE
if ($gaRuntime['debugMode']) {
  $gaRuntime['currentName'] = SOFTWARE_NAME . ' Development - Version: ' . SOFTWARE_VERSION;
  // Git stuff
  if (file_exists('./.git/HEAD')) {
    $_strGitHead = file_get_contents('./.git/HEAD');
    $_strGitSHA1 = file_get_contents('./.git/' . substr($_strGitHead, 5, -1));
    $_strGitBranch = substr($_strGitHead, 16, -1);
    $gaRuntime['currentName'] = 
      $gaRuntime['currentName'] . ' - ' .
      'Branch: ' . $_strGitBranch . ' - ' .
      'Commit: ' . substr($_strGitSHA1, 0, 7);
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Handle URIs
switch ($gaRuntime['qPath']) {
  case URI_ROOT:
    // Special Case: Interlink should go to Extensions instead of a front page
    if ($gaRuntime['currentApplication'] == 'interlink') {
      gfRedirect('/extensions/');
    }

    // Front Page
    // Generate the frontpage from SITE content
    $gmGenerateContent->addonSite(
      $gaRuntime['currentApplication'] . '-frontpage.xhtml', 'Explore Add-ons'
    );
    break;
  case URI_SEARCH:
    // Search Page
    // Send the search terms to SQL
    $gaRuntime['qSearchTerms'] = str_replace('*', '', $gaRuntime['qSearchTerms']);
    $searchManifest = $gmReadManifest->getAddons('site-search', $gaRuntime['qSearchTerms']);

    // If no results generate a page indicating that
    if (!$searchManifest) {
      $gmGenerateContent->addonSite('search', 'No search results');
    }

    // We have results so generate the page with them
    $gmGenerateContent->addonSite('search',
      'Search results for "' . $gaRuntime['qSearchTerms'] . '"',
      $searchManifest
    );
    break;
  case URI_EXTENSIONS:
    // Extensions Category (Top Level)
    // Find out if we should use Extension Subcategories or All Extensions
    $gaRuntime['requestAllExtensions'] = gfSuperVar('get', 'all');
    $useExtensionSubcategories = gfEnabledFeature('extensions-cat', true);

    if ($useExtensionSubcategories && !$gaRuntime['requestAllExtensions']) {
      // We are using Extension Subcategories so generate a page that lists all the subcategories
      $gmGenerateContent->addonSite('cat-extension-category',
                                        'Extensions',
                                        classReadManifest::EXTENSION_CATEGORY_SLUGS);
    }

    // We are doing an "All Extensions" Page
    // Get all extensions from SQL
    $categoryManifest = $gmReadManifest->getAddons('site-all-extensions');

    // If there are no extensions then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // Generate the "All Extensions" Page
    $gmGenerateContent->addonSite('cat-all-extensions',
                                      'Extensions',
                                      $categoryManifest,
                                      classReadManifest::EXTENSION_CATEGORY_SLUGS);
    break;
  case URI_THEMES:
    // Themes Category
    // Check if Themes are enabled
    gfEnabledFeature('themes');

    // Query SQL and get all themes
    $categoryManifest = $gmReadManifest->getAddons('site-addons-by-category', 'themes');

    // If there are no themes then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have themes so generate the page
    $gmGenerateContent->addonSite('cat-themes', 'Themes', $categoryManifest);
    break;
  case URI_PERSONAS:
    // Personas Category
    // Check if Personas are enabled
    gfEnabledFeature('personas');

    // Query SQL and get all personas
    $categoryManifest = $gmPersona->getPersonas('site-all-personas');

    // If there are no Personas then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have personas so generate the page
    $gmGenerateContent->addonSite('cat-personas', 'Personas', $categoryManifest);
    break;
  case URI_LANGPACKS:
    // Language Packs Category
    // See if LangPacks are enabled
    gfEnabledFeature('language-packs');

    // Query SQL for langpacks
    $categoryManifest = $gmReadManifest->getAddons('site-addons-by-category', 'language-packs');

    // If there are no langpacks then 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // We have langpacks so generate the page
    $gmGenerateContent->addonSite('cat-language-packs', 'Language Packs', $categoryManifest);
    break;
  case URI_DICTIONARIES:
    gfRedirect('http://repository.binaryoutcast.com/dicts/');
    break;
  case URI_SEARCHPLUGINS:
    // Search Engine Plugins Category
    // See if Search Engine Plugins are enabled
    gfEnabledFeature('search-plugins');

    // Get an array of hardcoded Search Engine Plugins from readManifest
    $categoryManifest = $gmReadManifest->getSearchPlugins();

    // If for some reason there aren't any even though there is no error checking in the method, 404
    if (!$categoryManifest) {
      $categoryManifest = [];
    }

    // Generate the Search Engine Plugins category page
    $gmGenerateContent->addonSite('cat-search-plugins', 'Search Plugins', $categoryManifest);
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
    if (str_starts_with($gaRuntime['qPath'], URI_EXTENSIONS)) {
      // Check if Extension Subcategories are enabled
      gfEnabledFeature('extensions-cat');

      // Strip the path to get the slug
      $strSlug = gfStripPath($gaRuntime['qPath'], URI_EXTENSIONS);

      // See if the slug exists in the category array
      if (!array_key_exists($strSlug, classReadManifest::EXTENSION_CATEGORY_SLUGS)) {
        gfRedirect('/addon/' . $strSlug);
      }

      // Query SQL for extensions in this specific category
      $categoryManifest = $gmReadManifest->getAddons('site-addons-by-category', $strSlug);
      
      // If there are no extensions then 404
      if (!$categoryManifest) {
        gfHeader(404);
      }

      // We have extensions so generate the subcategory page
      $gmGenerateContent->addonSite('cat-extensions',
                                        'Extensions: ' . classReadManifest::EXTENSION_CATEGORY_SLUGS[$strSlug],
                                        $categoryManifest, classReadManifest::EXTENSION_CATEGORY_SLUGS);
    }
    // Add-on Page
    elseif (str_starts_with($gaRuntime['qPath'], URI_ADDON_PAGE)) {
      // Strip the path to get the slug
      $strSlug = gfStripPath($gaRuntime['qPath'], URI_ADDON_PAGE);

      // Query SQL for the add-on
      $addonManifest = $gmReadManifest->getAddon('by-slug', $strSlug);

      // If there is no add-on, 404
      if (!$addonManifest) {
        gfHeader(404);
      }

      // Generate the Add-on Releases Page
      $gmGenerateContent->addonSite('addon-page', $addonManifest['name'], $addonManifest);
    }
    // Add-on Releases
    elseif (str_starts_with($gaRuntime['qPath'], URI_ADDON_RELEASES)) {
      // Strip the path to get the slug
      $strSlug = gfStripPath($gaRuntime['qPath'], URI_ADDON_RELEASES);

      // Query SQL for the add-on
      $addonManifest = $gmReadManifest->getAddon('by-slug', $strSlug);

      // If there is no add-on, 404
      if (!$addonManifest) {
        gfHeader(404);
      }

      // Generate the Add-on Releases Page
      $gmGenerateContent->addonSite('addon-releases', $addonManifest['name'] . ' - Releases', $addonManifest);
    }
    // Add-on License
    elseif (str_starts_with($gaRuntime['qPath'], URI_ADDON_LICENSE)) {
      // Strip the path to get the slug
      $strSlug = gfStripPath($gaRuntime['qPath'], URI_ADDON_LICENSE);

      // Query SQL for the add-on
      $addonManifest = $gmReadManifest->getAddon('by-slug', $strSlug);

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
        $gmGenerateContent->addonSite('addon-license', $addonManifest['name'] . ' - License', $addonManifest);
      }

      // The license is assumed to be an OSI license so redirect there
      gfRedirect('https://opensource.org/licenses/' . $addonManifest['license']);
    }

    // There are no matches so 404
    gfHeader(404); 
}

// ====================================================================================================================

?>