<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classReadManifest | ===========================================================================================

class classReadManifest {
  private $currentApplication;
  private $currentAppID;

  // ------------------------------------------------------------------------------------------------------------------

  // The current category slugs
  // There is also themes and language-packs in addition to the extension categories
  const EXTENSION_CATEGORY_SLUGS = EXTENSION_CATEGORY_SLUGS;

  // ------------------------------------------------------------------------------------------------------------------

  const LICENSES = array(
      'Apache-2.0' => 'Apache License 2.0',
      'Apache-1.1' => 'Apache License 1.1',
      'BSD-3-Clause' => 'BSD 3-Clause',
      'BSD-2-Clause' => 'BSD 2-Clause',
      'GPL-3.0' => 'GNU General Public License 3.0',
      'GPL-2.0' => 'GNU General Public License 2.0',
      'LGPL-3.0' => 'GNU Lesser General Public License 3.0',
      'LGPL-2.1' => 'GNU Lesser General Public License 2.1',
      'AGPL-3.0' => 'GNU Affero General Public License v3',
      'MIT' => 'MIT License',
      'MPL-2.0' => 'Mozilla Public License 2.0',
      'MPL-1.1' => 'Mozilla Public License 1.1',
      'Custom' => 'Custom License',
      'PD' => 'Public Domain',
      'COPYRIGHT' => ''
    );

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    global $gaRuntime;

    gfEnsureModules(__CLASS__, 'database');

    if ($gaRuntime['qComponent'] == 'panel') {
      gfEnsureModules(__CLASS__, 'account');
    }
    
    // Assign currentApplication
    $this->currentApplication = $gaRuntime['currentApplication'];
    $this->currentAppID = TARGET_APPLICATION_ID[$gaRuntime['currentApplication']];
  }

 /********************************************************************************************************************
  * Gets a single add-on manifest
  * 
  * @param $aQueryType      Type of query to be performed
  * @param $aQueryData      Data for the query such as add-on slug
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getAddon($aQueryType, $aQueryData) {
    global $gmDatabase;

    $query = null;
    $returnInactive = null;
    $returnUnreviewed = null;
    $processContent = null;
    $xpInstallFixup = null;

    switch ($aQueryType) {
      case 'by-id':
        $returnUnreviewed = true;
        $query = "SELECT `id`, `slug`, `type`, `releaseXPI`, `reviewed`, `active`, `xpinstall`
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `id` = ?s
                  AND `type` IN ('extension', 'theme', 'langpack')";
        $queryResult = $gmDatabase->query('row', $query, $this->currentApplication, $aQueryData);
        break;
      case 'by-slug':
        $returnUnreviewed = true;
        $processContent = true;
        $xpInstallFixup = true;
        $query = "SELECT addon.*
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `slug` = ?s
                  AND `type` IN ('extension', 'theme', 'langpack')";
        $queryResult = $gmDatabase->query('row', $query, $this->currentApplication, $aQueryData);
        break;
      case 'panel-by-id':
        $returnInactive = true;
        $returnUnreviewed = true;
        $query = "SELECT `id`, `slug`, `type`, `releaseXPI`, `reviewed`, `active`, `xpinstall`
                  FROM `addon`
                  WHERE `id` = ?s
                  AND `type` IN ('extension', 'theme', 'langpack')";
        $queryResult = $gmDatabase->query('row', $query, $aQueryData);
        break;
      case 'panel-by-slug':
        $returnInactive = true;
        $returnUnreviewed = true;
        $query = "SELECT *
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE `slug` = ?s
                  AND `type` IN ('extension', 'theme', 'external', 'langpack')";
        $queryResult = $gmDatabase->query('row', $query, $aQueryData);
        break;
      default:
        gfError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    if (!$queryResult) {
      return null;
    }

    $addonManifest = $this->processManifest($queryResult,
                                            $returnInactive,
                                            $returnUnreviewed,
                                            $processContent,
                                            $xpInstallFixup);
    
    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Gets an indexed array of add-ons
  * 
  * @param $aQueryType      Type of query to be performed
  * @param $_queryData      Data for the query such as slugs or search terms
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getAddons($aQueryType, $aQueryData = null) {
    global $gmDatabase;

    $query = null;
    $returnInactive = null;
    $returnUnreviewed = null;
    $processContent = true;
    $xpInstallFixup = true;

    switch ($aQueryType) {
      case 'site-addons-by-category':
        $query = "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `category` = ?s
                  AND NOT `category` = 'unlisted'
                  ORDER BY `name`";
        $queryResults = $gmDatabase->query('rows', $query, $this->currentApplication, $aQueryData);
        break;
      case 'site-all-extensions':
        $query = "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`
                 FROM `addon`
                 JOIN `client` ON addon.id = client.addonID
                 WHERE ?n = 1
                 AND `type` IN ('extension', 'external')
                 AND NOT `category` IN ('unlisted', 'themes', 'langpack')
                 ORDER BY `name`";
        $queryResults = $gmDatabase->query('rows', $query, $this->currentApplication);
        break;
      case 'site-search':
        $query = "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `type` IN ('extension', 'theme', 'langpack', 'external')
                  AND NOT `category` = 'unlisted'
                  AND MATCH(`tags`) AGAINST(?s IN NATURAL LANGUAGE MODE)";
        $queryResults = $gmDatabase->query('rows', $query, $this->currentApplication, $aQueryData);
        break;
      case 'api-search':
        $xpInstallFixup = null;
        $query = "SELECT `id`, `slug`, `type`, `creator`, `releaseXPI`, `name`, `homepageURL`, `description`,
                         `url`, `reviewed`, `active`, `xpinstall`
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `type` IN ('extension', 'theme', 'langpack')
                  AND NOT `category` = 'unlisted'
                  AND MATCH(`tags`) AGAINST(?s IN NATURAL LANGUAGE MODE)";
        $queryResults = $gmDatabase->query('rows', $query, $this->currentApplication, $aQueryData);
        break;
      case 'api-get':
        $xpInstallFixup = null;
        $query = "SELECT `id`, `slug`, `type`, `creator`, `releaseXPI`, `name`, `homepageURL`, `description`,
                         `url`, `reviewed`, `active`, `xpinstall`
                  FROM `addon`
                  JOIN `client` ON addon.id = client.addonID
                  WHERE ?n = 1
                  AND `id` IN (?a)
                  AND `type` IN ('extension', 'theme', 'langpack')";
        $queryResults = $gmDatabase->query('rows', $query, $this->currentApplication, $aQueryData);
        break;
      case 'panel-user-addons':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $xpInstallFixup = null;
        $query = "SELECT `id`, `slug`, `type`, `name`, `url`, `reviewed`, `active`
                  FROM `addon`
                  WHERE `slug` IN (?a)
                  AND `type` IN ('extension', 'theme')
                  ORDER BY `name`";
        $queryResults = $gmDatabase->query('rows', $query, $aQueryData);
        break;
      case 'panel-unreviewed-addons':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $xpInstallFixup = null;
        $query = "SELECT `id`, `slug`, `type`, `name`, `category`, `url`, `reviewed`, `active`
                  FROM `addon`
                  WHERE `type` IN ('extension', 'theme')
                  AND `reviewed` = 0
                  ORDER BY `name`";
        $queryResults = $gmDatabase->query('rows', $query);

        if ($queryResults) {
          // Exclude JustOff from this list due to being a traitor
          $addonsJustOff = $gmDatabase->query('row', "SELECT `addons` FROM `user` WHERE `username` = 'justoff'");
          $addonsJustOff = json_decode($addonsJustOff['addons']);

          if ($addonsJustOff) {
            $filteredQueryResults = $queryResults;

            foreach ($queryResults as $_key => $_value) {
              if (in_array($_value['slug'], $addonsJustOff)) {
                unset($filteredQueryResults[$_key]);
              }
            }

            $queryResults = $filteredQueryResults;
          }
        }
        break;
      case 'panel-addons-by-type':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $xpInstallFixup = null;
        $query = "SELECT `id`, `slug`, `type`, `name`, `category`, `url`, `reviewed`, `active`
                  FROM `addon`
                  WHERE `type` = ?s
                  ORDER BY `name`";
        $queryResults = $gmDatabase->query('rows', $query, $aQueryData);
        break;
      default:
        gfError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    if (!$queryResults) {
      return null;
    }

    $manifestData = array();

    foreach($queryResults as $_value) {
      $addonManifest = $this->processManifest($_value,
                                              $returnInactive,
                                              $returnUnreviewed,
                                              $processContent,
                                              $xpInstallFixup);

      if (!$addonManifest) {
        continue;
      }

      $manifestData[] = $addonManifest;
    }

    return $manifestData;
  }

 /********************************************************************************************************************
  * Gets an indexed array of simplified/legacy search engine manifests
  * XXX: This function has insufficient error checking
  * XXX: This should be converted to SQL
  * 
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getSearchPlugins() {
    require_once(DATABASES['searchPlugins']);
    $datastorePath = gfBuildPath(ROOT_PATH, 'datastore', 'searchplugins');
    $searchPlugins = EMPTY_ARRAY;
    asort($searchPluginsDB, SORT_NATURAL);

    foreach ($searchPluginsDB as $_key => $_value) {
      $metadata = EMPTY_ARRAY;
      $xml = gfObjectToArray(simplexml_load_file($datastorePath . $_value));

      if (!$xml) {
        continue;
      }

      $metadata['type'] = 'search-plugin';
      $metadata['id'] = $_key;
      $metadata['slug'] = substr($_value, 0, -4);
      $metadata['name'] = $xml['ShortName'];
      $metadata['icon'] = $xml['Image'];

      $searchPlugins[$metadata['name']] = $metadata;
    }

    return $searchPlugins;
  }

 /********************************************************************************************************************
  * Gets an indexed array of dictionary manifests
  ********************************************************************************************************************/
  public function getDictionaries() {
    $manifestData = null;

    if (!$manifestData) {
      $manifestData = [];
    }

    return $manifestData;
  }

 /********************************************************************************************************************
  * Internal method to post-process an add-on manifest
  * 
  * @param $addonManifest       add-on manifest
  * @param $returnInactive      Optional, return inactive add-on instead of null
  * @param $returnUnreviewed    Optional, return unreviewed add-on instead of null
  * @returns                    add-on manifest or null
  ********************************************************************************************************************/
  // This is where we do any post-processing on an Add-on Manifest
  private function processManifest($addonManifest,
                                   $returnInactive = null,
                                   $returnUnreviewed = null,
                                   $processContent = true,
                                   $xpInstallFixup = true) {
    global $gaRuntime;

    // Cast the int-strings to bool
    $addonManifest['reviewed'] = (bool)$addonManifest['reviewed'];
    $addonManifest['active'] = (bool)$addonManifest['active'];

    if (!$addonManifest['active'] && !$returnInactive) {
      return null;
    }
    
    if (!$addonManifest['reviewed'] && !$returnUnreviewed) {
      return null;
    }

    // In the PANEL we join the client table but we only need it for externals
    if ($gaRuntime['qComponent'] == 'panel' && $addonManifest['type'] != 'external') {
      foreach (TARGET_APPLICATION_ID as $_key => $_value) {
        unset($addonManifest[$_key]);
      }

      unset($addonManifest['addonID']);
    }

    // It would be nice if we could have the owner of an add-on in the panel
    if ($gaRuntime['qComponent'] == 'panel') {
      $level = $gaRuntime['authentication']['level'] ?? 0;
      if ($level >= 3) {
        $addonManifest['owner'] = $GLOBALS['gmAccount']->findUserAddon($addonManifest['slug']) ?? null;
      }
    }

    // Actions on xpinstall key
    if ($addonManifest['xpinstall'] ?? false) {
      // JSON Decode xpinstall
      $addonManifest['xpinstall'] = json_decode($addonManifest['xpinstall'], true);

      if ($xpInstallFixup) {
        // We need to perform some minor post processing on XPInstall
        foreach ($addonManifest['xpinstall'] as $_key => $_value) {
          // Remove entries that are not compatible with the current application
          if (!array_key_exists($this->currentAppID, $addonManifest['xpinstall'][$_key]['targetApplication'])) {
            unset($addonManifest['xpinstall'][$_key]);
            continue;
          }

          if ($processContent) {
            // XXX: We should get Smarty to do the conversion...
            // Set a human readable date based on epoch
            $addonManifest['xpinstall'][$_key]['date'] = date('F j, Y', $addonManifest['xpinstall'][$_key]['epoch']);
          }
        }
      }

      // Ensure that the xpinstall keys are reverse sorted using an anonymous function and a spaceship
      uasort($addonManifest['xpinstall'], function ($_xpi1, $_xpi2) { return $_xpi2['epoch'] <=> $_xpi1['epoch']; });
    }

    // Remove whitespace from description and html encode
    if ($addonManifest['description'] ?? false) {
      $addonManifest['description'] = htmlentities(trim($addonManifest['description']), ENT_XHTML);
    }

    // If content exists, process it
    if ($processContent && array_key_exists('content', $addonManifest)) {
      // Check to ensure that there really is content
      $addonManifest['content'] = gfSuperVar('var', $addonManifest['content']);

      // Process content or assign description to it
      if ($addonManifest['content'] != null) {
        $addonManifest['content'] = $this->processContent($addonManifest['content']);
      }
      else {
        $addonManifest['content'] = $addonManifest['description'];
      }
    }

    // Process license
    if (array_key_exists('license', $addonManifest)) {
      $addonManifest = $this->processLicense($addonManifest);
    }
    
    // XXX: Why the fuck do we need this?
    // Set baseURL if applicable
    if ($addonManifest['type'] != 'external') {
      $forcedScheme = gfEnabledFeature('https', true) ? 'https://' : 'http://';
      $addonManifest['baseURL'] = $forcedScheme .
                                  $gaRuntime['currentDomain'] . '/?component=download&version=latest&id=';
    }

    // XXX: Do we need this?
    // Set Datastore Paths 
    $addonManifest['basePath'] = '.' . DATASTORE_RELPATH . 'addons/' . $addonManifest['slug'] . '/';

    // XXX: Ditto
    // Set reletive url paths
    $_addonPath = substr($addonManifest['basePath'], 1);
    $_defaultPath = str_replace($addonManifest['slug'], 'default', $_addonPath);

    // Legacy Externals have their icons in an ex-### directory
    if ($addonManifest['type'] == 'external' && str_contains($addonManifest['id'], '@ex-')) {
      // Extract the legacy external id
      $_oldID = preg_replace('/(.*)\@(.*)/iU', '$2', $addonManifest['id']);

      // Set basePath
      $addonManifest['basePath'] =
        '.' . DATASTORE_RELPATH . 'addons/' . $_oldID . '/';

      // Set reletive url paths
      $_addonPath = substr($addonManifest['basePath'], 1);
      $_defaultPath = str_replace($_oldID, 'default', $_addonPath);
    }

    // We want to not have to hit this unless we are coming from the SITE
    if (array_key_exists('name', $addonManifest)) {
      // Detect Icon
      if (file_exists($addonManifest['basePath'] . 'icon.png')) {
        $addonManifest['icon'] = $_addonPath . 'icon.png';
        $addonManifest['hasIcon'] = true;
      }
      else {
        $addonManifest['icon'] = $_defaultPath . 'icon.png';
        $addonManifest['hasIcon'] = false;
      }

      // Detect Preview
      if (file_exists($addonManifest['basePath'] . 'preview.png')) {
        $addonManifest['preview'] = $_addonPath . 'preview.png';
        $addonManifest['hasPreview'] = true;
      }
      else {
        $addonManifest['preview'] = $_defaultPath . 'preview.png';
        $addonManifest['hasPreview'] = false;
      }
    }

    // Return Add-on Manifest to internal caller
    return $addonManifest;
  }

 /********************************************************************************************************************
  * Internal (most of the time) method to process "phoebus.content"
  * 
  * @param $_addonPhoebusContent    raw "phoebus.content"
  * @returns                        processed "phoebus.content"
  ********************************************************************************************************************/
  public function processContent($aAddonContent) {     
    // html encode phoebus.content
    $aAddonContent = htmlentities($aAddonContent, ENT_XHTML);

    // Replace new lines with <br />
    $aAddonContent = nl2br($aAddonContent, true);

    $phoebusCodeSimpleTags = array(
      '[b]' => '<strong>',
      '[/b]' => '</strong>',
      '[i]' => '<em>',
      '[/i]' => '</em>',
      '[u]' => '<u>',
      '[/u]' => '</u>',
      '[ul]' => '</p><ul><fixme />',
      '[/ul]' => '</ul><p><fixme />',
      '[ol]' => '</p><ol><fixme />',
      '[/ol]' => '</ol><p><fixme />',
      '[li]' => '<li>',
      '[/li]' => '</li>',
      '[section]' => '</p><h3>',
      '[/section]' => '</h3><p><fixme />'
    );

    $phoebusCodeRegexTags = array(
      '\<(ul|\/ul|li|\/li|p|\/p)\><br \/>' => '<$1>',
      '\[url=(.*)\](.*)\[\/url\]' => '<a href="$1" target="_blank">$2</a>',
      '\[url\](.*)\[\/url\]' => '<a href="$1" target="_blank">$1</a>',
      '\[img(.*)\](.*)\[\/img\]' => EMPTY_STRING
    );

    // Process the substs
    $aAddonContent = gfSubst('simple', $phoebusCodeSimpleTags, $aAddonContent);
    $aAddonContent = gfSubst('regex', $phoebusCodeRegexTags, $aAddonContent);

    // Less hacky than what is in funcReadManifest
    // Remove linebreak special cases
    $aAddonContent = str_replace('<fixme /><br />', '', $aAddonContent);

    return $aAddonContent;
  }

 /********************************************************************************************************************
  * Internal method to process "phoebus.content"
  * 
  * @param $aAddonManifest   add-on manifest
  * @returns                 add-on manifest with additional license metadata
  ********************************************************************************************************************/
  private function processLicense($aAddonManifest) {
    // Approved Licenses  
    $arrayLicenses = array_change_key_case(self::LICENSES, CASE_LOWER);
    $arrayLicenses['copyright'] = '&copy; ' . date("Y") . ' - ' . $aAddonManifest['creator'];
     
    // Set to lowercase
    if ($aAddonManifest['license'] != null) {
      $aAddonManifest['license'] = strtolower($aAddonManifest['license']);
    }

    // phoebus.license trumps all
    // If existant override any license* keys and load the file into the manifest
    if ($aAddonManifest['licenseText'] != null) {
      $aAddonManifest['license'] = 'custom';
      $aAddonManifest['licenseName'] = $arrayLicenses[$aAddonManifest['license']];
      $aAddonManifest['licenseDefault'] = null;
      $aAddonManifest['licenseURL'] = null;

      return $aAddonManifest;
    }

    // If license is not set then default to copyright
    if ($aAddonManifest['license'] == null) {
      $aAddonManifest['license'] = 'copyright';
      $aAddonManifest['licenseName'] = $arrayLicenses[$aAddonManifest['license']];
      $aAddonManifest['licenseDefault'] = true;

      return $aAddonManifest;
    }

    if ($aAddonManifest['license'] != null) {
      if ($aAddonManifest['license'] == 'custom' && str_starts_with($aAddonManifest['licenseURL'], 'http')) {
        $aAddonManifest['license'] = 'custom';
        $aAddonManifest['licenseName'] = $arrayLicenses[$aAddonManifest['license']];

        return $aAddonManifest;
      }
      elseif (array_key_exists($aAddonManifest['license'], $arrayLicenses)) {
        $aAddonManifest['licenseName'] = $arrayLicenses[$aAddonManifest['license']];
        $aAddonManifest['licenseDefault'] = null;
        $aAddonManifest['licenseURL'] = null;
        $aAddonManifest['licenseText'] = null;

        return $aAddonManifest;
      }
      else {
        $aAddonManifest['license'] = 'unknown';
        $aAddonManifest['licenseName'] = 'Unknown License';
        $aAddonManifest['licenseDefault'] = null;
        $aAddonManifest['licenseURL'] = null;
        $aAddonManifest['licenseText'] = null;
        
        return $aAddonManifest;
      }
    }
  }
}

// ====================================================================================================================

?>