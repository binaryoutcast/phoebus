<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

/* crap I might need later otherwise ignore / remove
if ($aQueryData == 'search-plugins') {
  return $this->getSearchPlugins();
}

$categories = array_merge(array_keys(self::EXTENSION_CATEGORY_SLUGS),
                         ['themes', 'language-packs', 'dictionaries']);

if (!in_array($aQueryData, $categories)) {
  gfError(__CLASS__ . '::' . __FUNCTION__ . ' - by-category: Unknown category');
}
*/

class classReadManifest {
  private $needsReset;
  private $currentApplication;
  private $currentComponent;
  private $applicationID;
  private $applicationBit;
  private $addonManifest;
  private $addonTypes;
  private $querySelect;

  // ------------------------------------------------------------------------------------------------------------------

  // The current category slugs
  // There is also themes and language-packs in addition to the extension categories
  const EXTENSION_CATEGORY_SLUGS = EXTENSION_CATEGORY_SLUGS;
  const LICENSES = LICENSES;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    if (!gfEnsureModule('database')) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }
    
    $this->needsReset = true;
    $this->reset();
  }

  /********************************************************************************************************************
  * Public Method to get an Add-on Manifest by Slug
  *******************************************************************************************************************/
  public function getAddonBySlug($aSlug, $aOverrideComponent = null) {
    $this->reset();
    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $returnDisabled = null;
    $processContent = null;
    $query = "SELECT ?p
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE addonBase.slug = ?s
              AND addonVersions.thisVersion = addonBase.releaseVersion
              AND addonVersions.application & ?i
              AND addonBase.type IN (?a)";
     
    // Handle SITE specific options
    if ($this->currentComponent == 'site') {
      $processContent = true;
    }

    // Handle PANEL specific options
    if ($this->currentComponent == 'panel') {
      $returnDisabled = true;
      $this->setAllApplicationsBits();
    }

    // Query the database
    $this->addonManifest = $GLOBALS['moduleDatabase']->query('row',
                                                             $query,
                                                             $this->generateSelectString(),
                                                             $aSlug,
                                                             $this->applicationBit,
                                                             $this->addonTypes);

    if (!$this->addonManifest) {
      return null;
    }

    // Process the manifest and return the result
    $this->processManifest($returnDisabled, true, $processContent);
    $this->needsReset = true;
    return $this->addonManifest;
  }

  /********************************************************************************************************************
  * Public Method to get all (applicable) Add-on Versions by Slug
  ********************************************************************************************************************/
  public function getAddonVersions($aSlug, $aOverrideComponent = null) {
    $this->reset();
    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $addonManifests = [];
    $returnDisabled = null;
    $this->querySelect = array(
      'addonBase'       => ['id', 'slug', 'type', 'active', 'reviewed', 'addonBlocked', 'userDisabled', 'releaseVersion'],
      'addonMetadata' => ['name', 'creator', 'homepageURL', 'repositoryURL', 'supportURL', 'supportEmail'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked', 'epoch', 'hash', 'technology', 'licenseCode',
                          'licenseText', 'xpi', 'xpcom', 'targetApplication']
    );

    $query = "SELECT ?p 
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE addonBase.slug = ?s
              AND addonVersions.application & ?i
              AND addonBase.type IN (?a)
              ORDER BY addonVersions.epoch DESC";

    // Handle PANEL specific options
    if ($this->currentComponent == 'panel') {
      $returnDisabled = true;
      $this->setAllApplicationsBits();
    }

    // Query the database
    $queryResult = $GLOBALS['moduleDatabase']->query('rows',
                                                     $query,
                                                     $this->generateSelectString(),
                                                     $aSlug,
                                                     $this->applicationBit,
                                                     $this->addonTypes);

    if (!$queryResult) {
      return null;
    }

    foreach($queryResult as $_value) {
      $this->addonManifest = $_value;
      $this->processManifest($returnDisabled, true);
      if (gfSuperVar('var', $this->addonManifest)) {
        $addonManifests[] = $this->addonManifest;
      }
    }

    if (!gfSuperVar('var', $addonManifests)) {
      return null;
    }

    // Return manifests
    $this->needsReset = true;
    return $addonManifests;
  }

  /********************************************************************************************************************
  * Public Method to get a reduced Add-on Manifest by ID
  ********************************************************************************************************************/
  public function getAddonByID($aID, $aVersion = null) {
    $this->reset();
    $this->querySelect = array(
      'addonBase'       => ['id', 'slug', 'type', 'active', 'reviewed', 'addonBlocked', 'userDisabled', 'releaseVersion'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked', 'hash', 'xpi', 'targetApplication']
    );

    $query = "SELECT ?p
              FROM `addonBase`
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE addonBase.id = ?s
              AND addonVersions.thisVersion = ?p
              AND addonVersions.application & ?i
              AND addonBase.type IN (?a)";

    // Parse the version
    $parsedVersion = "addonBase.releaseVersion";

    if ($aVersion) {
      $parsedVersion = $GLOBALS['moduleDatabase']->query('parse', "?s", $aVersion);
    }

    // Query the database
    $this->addonManifest = $GLOBALS['moduleDatabase']->query('row',
                                                             $query,
                                                             $this->generateSelectString(),
                                                             $aID,
                                                             $parsedVersion,
                                                             $this->applicationBit,
                                                             $this->addonTypes);

    if (!$this->addonManifest) {
      return null;
    }

    // Process the manifest and return the result
    $this->processManifest(false, true);
    $this->needsReset = true;
    return $this->addonManifest;
  }

  /********************************************************************************************************************
  * Public Method to get all add-ons by category
  ********************************************************************************************************************/
  public function getAddonsByCategory($aCategory, $aOverrideComponent = null) {
    $this->reset();

    if ($aCategory == 'search-plugins') {
      $this->needsReset = true;
      return $this->getSearchPlugins();
    }

    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $addonManifests = [];
    $returnDisabled = null;
    $returnUnreviewed = null;
    $this->querySelect = array(
      'addonBase'       => ['*'],
      'addonMetadata' => ['name', 'creator', 'description', 'url'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked']
      
    );

    $query = "SELECT ?p 
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE addonMetadata.category = ?s
              AND addonVersions.application & ?i
              AND addonVersions.thisVersion = addonBase.releaseVersion
              AND addonBase.type IN (?a)
              ORDER BY addonMetadata.name";

    // Hnadle SITE specific options
    if ($this->currentComponent == 'site') {
      $this->addonTypes[] = 'external';
    }

    // Handle PANEL specific options
    if ($this->currentComponent == 'panel') {
      $returnDisabled = true;
      $returnUnreviewed = true;
      $this->setAllApplicationsBits();
    }

    // Query the database
    $queryResult = $GLOBALS['moduleDatabase']->query('rows',
                                                     $query,
                                                     $this->generateSelectString(),
                                                     $aCategory,
                                                     $this->applicationBit,
                                                     $this->addonTypes);

    if (!$queryResult) {
      return null;
    }

    foreach($queryResult as $_value) {
      $this->addonManifest = $_value;
      $this->processManifest($returnDisabled, $returnUnreviewed);
      if (gfSuperVar('var', $this->addonManifest)) {
        $addonManifests[] = $this->addonManifest;
      }
    }

    if (!gfSuperVar('var', $addonManifests)) {
      return null;
    }

    // Return manifests
    $this->needsReset = true;
    return $addonManifests;
  }

  /********************************************************************************************************************
  * Public Method to get all add-ons by type
  ********************************************************************************************************************/
  public function getAddonsByType($aType, $aOverrideComponent = null) {
    $this->reset();
    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $addonManifests = [];
    $returnDisabled = null;
    $returnUnreviewed = null;
    $this->querySelect = array(
      'addonBase'       => ['*'],
      'addonMetadata' => ['name', 'creator', 'description', 'url'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked']
      
    );

    $query = "SELECT ?p 
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE addonBase.type = ?s
              AND addonVersions.application & ?i
              AND addonVersions.thisVersion = addonBase.releaseVersion
              ORDER BY addonMetadata.name";

    // Handle PANEL specific options
    if ($this->currentComponent == 'panel') {
      $returnDisabled = true;
      $returnUnreviewed = true;
      $this->setAllApplicationsBits();
    }

    // Query the database
    $queryResult = $GLOBALS['moduleDatabase']->query('rows',
                                                     $query,
                                                     $this->generateSelectString(),
                                                     $aType,
                                                     $this->applicationBit);

    if (!$queryResult) {
      return null;
    }

    foreach($queryResult as $_value) {
      $this->addonManifest = $_value;
      $this->processManifest($returnDisabled, $returnUnreviewed);
      if (gfSuperVar('var', $this->addonManifest)) {
        $addonManifests[] = $this->addonManifest;
      }
    }

    if (!gfSuperVar('var', $addonManifests)) {
      return null;
    }

    // Return manifests
    $this->needsReset = true;
    return $addonManifests;
  }

  /********************************************************************************************************************
  * Public Method to get add-ons by search terms
  ********************************************************************************************************************/
  public function getAddonsBySearch($aTerms, $aOverrideComponent = null) {
    $this->reset();
    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $addonManifests = [];
    $this->querySelect = array(
      'addonBase'       => ['*'],
      'addonMetadata' => ['name', 'creator', 'description', 'url', 'tags'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked']
    );

    $query = "SELECT ?p 
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE MATCH(addonMetadata.tags) AGAINST(?s IN NATURAL LANGUAGE MODE)
              AND addonVersions.application & ?i
              AND addonVersions.thisVersion = addonBase.releaseVersion
              AND addonBase.type IN (?a)
              AND NOT addonMetadata.category = 'unlisted'
              ORDER BY addonMetadata.name";

    // Handle SITE specific options
    if ($this->currentComponent == 'site') {
      $this->addonTypes[] = 'external';
    }

    // Query the database
    $queryResult = $GLOBALS['moduleDatabase']->query('rows',
                                                     $query,
                                                     $this->generateSelectString(),
                                                     $aTerms,
                                                     $this->applicationBit,
                                                     $this->addonTypes);

    if (!$queryResult) {
      return null;
    }

    foreach($queryResult as $_value) {
      $this->addonManifest = $_value;
      $this->processManifest();
      if (gfSuperVar('var', $this->addonManifest)) {
        $addonManifests[] = $this->addonManifest;
      }
    }

    if (!gfSuperVar('var', $addonManifests)) {
      return null;
    }

    // Return manifests
    $this->needsReset = true;
    return $addonManifests;
  }

  /********************************************************************************************************************
  * Public Method to get add-ons by list of ids (When called from PANEL it takes a list of slugs)
  ********************************************************************************************************************/
  public function getAddonsByList($aAddons, $aOverrideComponent = null) {
    $this->reset();
    $this->currentComponent = $aOverrideComponent ?? $this->currentComponent;
    $addonManifests = [];
    $returnDisabled = null;
    $returnUnreviewed = null;
    $this->querySelect = array(
      'addonBase'       => ['*'],
      'addonMetadata' => ['name', 'creator', 'description', 'url', 'homepageURL'],
      'addonVersions' => ['thisVersion', 'versionBusted', 'versionBlocked', 'epoch', 'hash', 'xpi', 'targetApplication']
      
    );

    $query = "SELECT ?p 
              FROM `addonBase`
              JOIN `addonMetadata` ON addonBase.id = addonMetadata.id
              JOIN `addonVersions` ON addonBase.id = addonVersions.id
              WHERE ?p IN (?a)
              AND addonVersions.application & ?i
              AND addonVersions.thisVersion = addonBase.releaseVersion
              AND addonBase.type IN (?a)
              ORDER BY addonMetadata.name";

    $parsedSlugID = "addonBase.id";

    // Handle PANEL specific options
    if ($this->currentComponent == 'panel') {
      $parsedSlugID = "addonBase.slug";
      $returnDisabled = true;
      $returnUnreviewed = true;
      $this->setAllApplicationsBits();
    }

    // Query the database
    $queryResult = $GLOBALS['moduleDatabase']->query('rows',
                                                     $query,
                                                     $this->generateSelectString(),
                                                     $parsedSlugID,
                                                     $aAddons,
                                                     $this->applicationBit,
                                                     $this->addonTypes);

    if (!$queryResult) {
      return null;
    }

    foreach($queryResult as $_value) {
      $this->addonManifest = $_value;
      $this->processManifest($returnDisabled, $returnUnreviewed);
      if (gfSuperVar('var', $this->addonManifest)) {
        $addonManifests[] = $this->addonManifest;
      }
    }

    if (!gfSuperVar('var', $addonManifests)) {
      return null;
    }

    // Return manifests
    $this->needsReset = true;
    return $addonManifests;
  }

 /********************************************************************************************************************
  * Internal method to post-process a restructured add-on manifest
  *******************************************************************************************************************/
  private function processManifest($aReturnDisabled = null,
                                   $aReturnUnreviewed = null,
                                   $aProcessContent = null) {

    // Why are we even here if there is no manifest?
    if (!$this->addonManifest) {
      gfError('Unhandled null add-on manifest');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // SQL Doesn't store bools so convert them to bools
    $arrayKeys = ['active', 'reviewed', 'addonBlocked', 'userDisabled', 'versionBusted', 'versionBlocked', 'xpcom'];
    foreach ($arrayKeys as $_value) {
      if (array_key_exists($_value, $this->addonManifest)) {
        $this->addonManifest[$_value] = (bool)$this->addonManifest[$_value];
      }
    }

    // We do not want to return manifests under certain conditions
    if (!$aReturnDisabled) {
      if (!$this->addonManifest['active']) {
        $this->addonManifest = null;
        return false;
      }

      $arrayKeys = ['userDisabled', 'addonBlocked', 'versionBusted', 'versionBlocked'];   
      foreach ($arrayKeys as $_value) {
        if (array_key_exists($_value, $this->addonManifest) && $this->addonManifest[$_value]) {
          $this->addonManifest = null;
          return false;
        }
      }
    }

    if (!$aReturnUnreviewed && !$this->addonManifest['reviewed']) {
      $this->addonManifest = null;
      return false;
    }

    // ----------------------------------------------------------------------------------------------------------------

    // If we have targetApplication then JSON decode it
    if (array_key_exists('targetApplication', $this->addonManifest)) {
      $this->addonManifest['targetApplication'] = json_decode($this->addonManifest['targetApplication'], true);
    }

    // If we have an XPI key and it isn't a legacy filename then prefill it with the standard filename
    if (array_key_exists('xpi', $this->addonManifest) && !$this->addonManifest['xpi']) {
      $this->addonManifest['xpi'] = $this->addonManifest['slug'] . '-' . $this->addonManifest['thisVersion'] . '.xpi';
    }

    // ----------------------------------------------------------------------------------------------------------------

    // HTML Encode values that come from the install manifest
    $arrayKeys = ['name', 'creator', 'description'];
    foreach ($arrayKeys as $_value) {
      if (array_key_exists($_value, $this->addonManifest) && $this->addonManifest[$_value]) {
        $this->addonManifest[$_value] = htmlentities($this->addonManifest[$_value], ENT_XHTML);
      }
    }

    // ----------------------------------------------------------------------------------------------------------------
    
    // If we should process content we should process content.. wait.
    if ($aProcessContent && array_key_exists('content', $this->addonManifest)) {
      if ($this->addonManifest['content']) {
        // Process Content
        $this->processContent();
      }
      else {
          // WELL, we don't have any content so use the description instead..
          $this->addonManifest['content'] = $this->addonManifest['description'];
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    if (array_key_exists('licenseCode', $this->addonManifest)) {
      $this->addonManifest['licenseCode'] = strtolower($this->addonManifest['licenseCode']) ?? null;
      
      if ($this->addonManifest['licenseCode'] == 'copyright') {
        $this->addonManifest['licenseName'] = 'Copyright &copy; ' .
                                              $this->addonManifest['creator'] ?? 'This Add-on\'s Developer';
      }
      else {
        foreach (self::LICENSES as $_key => $_value) {
          if ($this->addonManifest['licenseCode'] == strtolower($_key)) {
            $this->addonManifest['licenseName'] = $_value;
            break;
          }
        }
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Set baseURL if applicable
    if ($this->addonManifest['type'] != 'external') {
      $this->addonManifest['baseURL'] = 'http://' . $GLOBALS['gaRuntime']['currentDomain'] .
                                        '/?component=download&id=' . $this->addonManifest['id'] .
                                        '&version=' .
                                        $this->addonManifest['thisVersion'] ?? $this->addonManifest['releaseVersion']; 
    }

    // Set Datastore Paths 
    $this->addonManifest['basePath'] = '.' . DATASTORE_RELPATH . 'addons/' . $this->addonManifest['slug'] . '/';

    // Set reletive url paths
    $addonPath = substr($this->addonManifest['basePath'], 1);
    $defaultPath = str_replace($this->addonManifest['slug'], 'default', $addonPath);

    // Legacy Externals have their icons in an ex-### directory
    if ($this->addonManifest['type'] == 'external' && str_contains($this->addonManifest['id'], '@ex-')) {
      // Extract the legacy external id
      $oldID = preg_replace('/(.*)\@(.*)/iU', '$2', $this->addonManifest['id']);

      // Set basePath
      $this->addonManifest['basePath'] = '.' . DATASTORE_RELPATH . 'addons/' . $oldID . '/';

      // Set reletive url paths
      $addonPath = substr($this->addonManifest['basePath'], 1);
      $defaultPath = str_replace($oldID, 'default', $addonPath);
    }

    // Detect Icon and Preview
    if (in_array($this->currentComponent, ['site', 'panel', 'integration'])) {
      // Detect Icon
      $this->addonManifest['hasIcon'] = file_exists($this->addonManifest['basePath'] . 'icon.png');
      $this->addonManifest['hasPreview'] = file_exists($this->addonManifest['basePath'] . 'preview.png');
      $this->addonManifest['icon'] = $defaultPath . 'icon.png';
      $this->addonManifest['preview'] = $defaultPath . 'preview.png';

      if ($this->addonManifest['hasIcon']) {
        $this->addonManifest['icon'] = $addonPath . 'icon.png';
      }

      if ($this->addonManifest['hasPreview']) {
        $this->addonManifest['preview'] = $addonPath . 'preview.png';
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    return true;
  }

 /********************************************************************************************************************
  * Internal method to process phoebus.content style data
  ********************************************************************************************************************/
  private function processContent() {     
    // html encode phoebus.content
    $this->addonManifest['content'] = htmlentities($this->addonManifest['content'], ENT_XHTML);

    // Replace new lines with <br />
    $this->addonManifest['content'] = nl2br($this->addonManifest['content'], true);

    // create an array that str_contains the strs to pseudo-bbcode to real html
    $arrayPhoebusCode = array(
      'simple' => array(
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
      ),
      'complex' => array(
        '\<(ul|\/ul|li|\/li|p|\/p)\><br \/>' => '<$1>',
        '\[url=(.*)\](.*)\[\/url\]' => '<a href="$1" target="_blank">$2</a>',
        '\[url\](.*)\[\/url\]' => '<a href="$1" target="_blank">$1</a>',
        '\[img(.*)\](.*)\[\/img\]' => ''
      )
    );

    // str replace pseudo-bbcode with real html
    foreach ($arrayPhoebusCode['simple'] as $_key => $_value) {
      $this->addonManifest['content'] = str_replace($_key, $_value, $this->addonManifest['content']);
    }
    
    // Regex replace pseudo-bbcode with real html
    foreach ($arrayPhoebusCode['complex'] as $_key => $_value) {
      $this->addonManifest['content'] = preg_replace('/' . $_key . '/iU', $_value, $this->addonManifest['content']);
    }

    // Less hacky than what is in funcReadManifest
    // Remove linebreak special cases
    $this->addonManifest['content'] = str_replace('<fixme /><br />', '', $this->addonManifest['content']);

    return true;
  }

 /********************************************************************************************************************
  * Gets an indexed array of simplified/legacy search engine manifests
  * XXX: This function has insufficient error checking
  * XXX: This should be converted to SQL
  * 
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  private function getSearchPlugins() {
    $datastorePath = ROOT_PATH . DATASTORE_RELPATH . '/searchplugins/';
    $arraySearchPlugins = array();

    require_once(DATABASES['searchPlugins']);

    asort($searchPluginsDB, SORT_NATURAL);

    foreach ($searchPluginsDB as $_key => $_value) {
      $arraySearchPluginXML = simplexml_load_file($datastorePath . $_value);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['type'] = 'search-plugin';
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['id'] = $_key;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['name'] = (string)$arraySearchPluginXML->ShortName;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['slug'] = substr($_value, 0, -4);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['icon'] = (string)$arraySearchPluginXML->Image;
    }

    return $arraySearchPlugins;
  }

 /********************************************************************************************************************
  * Internal method to generate a select string from $this->querySelect
  *******************************************************************************************************************/
  private function generateSelectString() {
    if (!gfSuperVar('var', $this->querySelect)) {
      return "addonBase.*, addonMetadata.*, addonVersions.*";
    }

    $querySelect = '';
    foreach ($this->querySelect as $_key => $_value) {
      foreach ($_value as $_value2) {
        $querySelect = $querySelect . $_key . '.' . $_value2 . ', '; 
      }
    }

    return substr($querySelect, 0, -2);
  }

 /********************************************************************************************************************
  * Internal method to set $this->applicationBit to all defined applications
  *******************************************************************************************************************/
  private function setAllApplicationsBits() {
    $this->applicationBit = TOOLKIT_BIT;

    foreach (TARGET_APPLICATION as $_value) {
      $this->applicationBit = $this->applicationBit | $_value['bit'];
    }

    return true;
  }

 /********************************************************************************************************************
  * Internal method to reset the state of items that may be used
  *******************************************************************************************************************/
  private function reset() {
    if ($this->needsReset) {
      $this->needsReset = false;
      $this->currentApplication = $GLOBALS['gaRuntime']['currentApplication'];
      $this->currentComponent = $GLOBALS['gaRuntime']['requestComponent'];
      $this->applicationID = TARGET_APPLICATION[$this->currentApplication]['id'];
      $this->applicationBit = TARGET_APPLICATION[$this->currentApplication]['bit'];
      $this->addonManifest = null;
      $this->addonTypes = ['extension', 'theme', 'langpack', 'dictionary'];
    }

    return true;
  }
}

?>
