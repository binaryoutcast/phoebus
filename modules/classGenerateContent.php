<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classGenerateContent | ========================================================================================

class classGenerateContent {
  // XML/RDF Default Responses
  const XML_TAG = '<?xml version="1.0" encoding="UTF-8"?>';
  const RDF_AUS_BLANK = '<RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />';
  const XML_API_SEARCH_BLANK = '<searchresults total_results="0" />';
  const XML_API_LIST_BLANK = '<addons />';
  const XML_API_ADDON_ERROR = '<error>Add-on not found!</error>';
  
  private $libSmarty;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    global $gaRuntime;

    // Set the Application ID
    $gaRuntime['targetApplicationID'] =
      TARGET_APPLICATION_ID[$gaRuntime['currentApplication']];

    // ----------------------------------------------------------------------------------------------------------------

    // Component Path
    $componentPath = dirname(COMPONENTS[$gaRuntime['qComponent']]);

    // Component Content Path (for static content)
    $gaRuntime['componentContentPath'] = $componentPath . '/content/';

    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($gaRuntime['qComponent'] == 'site') {
      $skin = $gaRuntime['currentApplication'];
    }

    $gaRuntime['componentSkinPath'] = $componentPath . '/skin/' . $skin . '/';
    $gaRuntime['componentSkinRelPath'] = 
      str_replace(ROOT_PATH, '', $gaRuntime['componentSkinPath']);

    // ----------------------------------------------------------------------------------------------------------------

    if ($gaRuntime['useSmarty']) {
      // Include Smarty
      require_once(LIBRARIES['smarty']);

      // Get smartyDebug HTTP GET Argument
      $gaRuntime['requestSmartyDebug'] = gfSuperVar('get', 'smartyDebug');

      // Initalize Smarty
      $this->libSmarty = new Smarty();

      // Set Smarty Caching
      $this->libSmarty->caching = 0;

      // Set Smarty Debug
      $this->libSmarty->debugging = false;

      if ($gaRuntime['requestSmartyDebug']) {
        $this->libSmarty->debugging = $gaRuntime['debugMode'];
      }

      // Set Smarty Paths
      $smartyObjPath = ROOT_PATH . OBJ_RELPATH . '/smarty/' .
                       $gaRuntime['qComponent'] .
                       '-' . $skin . '/';

      $this->libSmarty->setCacheDir($smartyObjPath . 'cache');
      $this->libSmarty->setCompileDir($smartyObjPath . 'compile');
      $this->libSmarty->setConfigDir($smartyObjPath . 'config');
      $this->libSmarty->addPluginsDir($smartyObjPath . 'plugins');
      $this->libSmarty->setTemplateDir($smartyObjPath . 'template');
    }
  }

  /********************************************************************************************************************
  * This will generate HTML content for the SITE and PANEL components using Smarty
  * 
  * @param $aType         template or content file
  * @param $aTitle        Page title
  * @param $aData         Used if not null
  * @param $aExtraData    Used if not null
  ********************************************************************************************************************/
  public function addonSite($aType, $aTitle, $aData = null, $aExtraData = null) {
    global $gaRuntime;

    // This function will only serve the SITE component
    if (!$this->libSmarty) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - This method requires Smarty');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Read the Site Template
    $template = $this->getContentTemplate('site-template.xhtml');

    if (!$template) {
      gfError('Main template file could not be read or is missing');
    }

    // Read the Site Stylesheet
    $stylesheet = $this->getContentTemplate('site-stylesheet.css');

    if (!$stylesheet) {
      gfError('Mail stylesheet file could not be read or is missing');
    }

    // ----------------------------------------------------------------------------------------------------------------

    switch ($aType) {
      case 'addon-page':
      case 'addon-releases':
        $content = $this->getContentTemplate('addon-page.xhtml');
        break;
      case 'addon-license':
        $content = $this->getContentTemplate('addon-license.xhtml');
        break;
      case 'cat-extension-category':
        $content = $this->getContentTemplate('extension-category.xhtml');
        break;
      case 'cat-all-extensions':
      case 'cat-extensions':
      case 'cat-themes':
      case 'search':
        $content = $this->getContentTemplate('addon-category.xhtml');
        break;
      case 'cat-personas':
        $content = $this->getContentTemplate('persona-category.xhtml');
        break;
      case 'cat-language-packs':
        $content = $this->getContentTemplate('langpack-category.xhtml');
        break;
      case 'cat-search-plugins':
        $content = $this->getContentTemplate('searchplugin-category.xhtml');
        break;
      case 'panel-account-registration':
      case 'panel-account-registration-done':
         $content = $this->getContentTemplate('account-registration.xhtml');
         break;
      case 'panel-account-validation':
         $content = $this->getContentTemplate('account-validation.xhtml');
         break;
      case 'developer-account':
      case 'admin-edit-account-metadata':
        $content = $this->getContentTemplate('account-metadata.xhtml');
        break;
      case 'developer-addons-list':
      case 'admin-user-addons-list':
        $content = $this->getContentTemplate('developer-addons-list.xhtml');
        break;
      case 'administration-list':
      case 'admin-list-extensions':
      case 'admin-list-externals':
      case 'admin-list-themes':
      case 'admin-list-langpacks':
      case 'admin-list-unreviewed':
        $content = $this->getContentTemplate('administration-addon-list.xhtml');
        break;
      case 'admin-list-users':
        $content = $this->getContentTemplate('administration-users-list.xhtml');
        break;
      case 'admin-list-logs':
        $content = $this->getContentTemplate('administration-logs-list.xhtml');
        break;
      case 'developer-edit-addon-metadata':
      case 'admin-edit-addon-metadata':
        $content = $this->getContentTemplate('addon-metadata.xhtml');
        break;
      case 'admin-edit-external-metadata':
        $content = $this->getContentTemplate('external-metadata.xhtml');
        break;
      case 'admin-delete-addon':
        $content = $this->getContentTemplate('delete-addon.xhtml');
        break;
      case 'panel-submit-addon':
      case 'panel-submit-langpack':
      case 'panel-submit-external':
      case 'panel-update-release':
        $content = $this->getContentTemplate('addon-submit-update.xhtml');
        break;
      case 'addon-bulk-upload-langpack':
      case 'addon-bulk-upload-result':
        $content = $this->getContentTemplate('addon-bulk-upload.xhtml');
        break;
      default:
        $content = $this->getContentTemplate($aType, 'content');
        if (!$content) {
          gfError('Unkown template or content');
        }
    }

    if (!$content) {
      gfError('Content or template file could not be read or is missing');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Build the final template
    $finalTemplate = str_replace('{%SITE_STYLESHEET}', $stylesheet,
      str_replace('{%PAGE_CONTENT}', $content, $template)
    );

    // ----------------------------------------------------------------------------------------------------------------

    // Assign Data to Smarty
    $this->libSmarty->assign('APPLICATION_DEBUG', $gaRuntime['debugMode']);
    $this->libSmarty->assign('SITE_DOMAIN',
                             $gaRuntime['currentScheme'] . '://' .
                             $gaRuntime['currentDomain']);
    $this->libSmarty->assign('PAGE_TITLE', $aTitle);
    $this->libSmarty->assign('PAGE_PATH', $gaRuntime['qPath']);
    $this->libSmarty->assign('BASE_PATH', $gaRuntime['componentSkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);
    $this->libSmarty->assign('SITE_NAME', $gaRuntime['currentName']);
    $this->libSmarty->assign('SEARCH_TERMS', $gaRuntime['qSearchTerms']);
    $this->libSmarty->assign('APPLICATION_ID', $gaRuntime['targetApplicationID']);
    $this->libSmarty->assign('PAGE_TYPE', $aType);
    $this->libSmarty->assign('PAGE_DATA', $aData);
    $this->libSmarty->assign('EXTRA_DATA', $aExtraData);
    
    if ($gaRuntime['qComponent'] == 'panel') {
      $this->libSmarty->assign('USER_LEVEL',
      $gaRuntime['authentication']['level'] ?? 0);
    }

    // The Panel should NEVER be cached
    if ($gaRuntime['qComponent'] == 'panel') {
      header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
    }

    // Send html header
    gfHeader('html');
    
    // Send the final template to smarty and output
    $this->libSmarty->display('string:' . $finalTemplate);
    
    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * This will generate RDF content for the Add-on Update Service
  * 
  * @param $aAddonManifest   Add-on Manifest data structure
  ********************************************************************************************************************/
  public function addonUpdateService($aAddonManifest = null) {
    global $gaRuntime;

    if ($gaRuntime['qComponent'] != 'aus') {
      gfError(
        __CLASS__ . '::' . __FUNCTION__ . ' - This method is designed to work with the AUS component only'
      );
    }

    if (!$aAddonManifest) {
      // Send XML header
      gfHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::RDF_AUS_BLANK);

      // We're done here
      exit();
    }

    $updateRDF = gfReadFile($gaRuntime['componentContentPath'] . 'update.rdf');

    $addonXPInstall = $aAddonManifest['xpinstall'][$aAddonManifest['releaseXPI']];
    $addonTargetApplication = $addonXPInstall['targetApplication'][$gaRuntime['targetApplicationID']];
    
    // Language Packs are an 'item' as far as update.rdf is conserned
    if ($aAddonManifest['type'] == 'langpack') {
      $aAddonManifest['type'] = 'item';
    }
    
    $substs = array(
      '{%ADDON_TYPE}'       => $aAddonManifest['type'],
      '{%ADDON_ID}'         => $aAddonManifest['id'],
      '{%ADDON_VERSION}'    => $addonXPInstall['version'],
      '{%APPLICATION_ID}'   => $gaRuntime['targetApplicationID'],
      '{%ADDON_MINVERSION}' => $addonTargetApplication['minVersion'],
      '{%ADDON_MAXVERSION}' => $addonTargetApplication['maxVersion'],
      '{%ADDON_XPI}'        => $aAddonManifest['baseURL'] . $aAddonManifest['id'],
      '{%ADDON_HASH}'       => $addonXPInstall['hash']
    );

    $updateRDF = gfSubst('simple', $substs, $updateRDF);

    // Send XML header
    gfHeader('xml');

    // Print Update RDF
    print($updateRDF);

    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * This will generate XML content for Add-ons Manager Search Results
  * 
  * @param $aSearchManifest    Search Result Manifest
  ********************************************************************************************************************/
  public function amSearch($aSearchManifest = null) {
    global $gaRuntime;

    if (!$aSearchManifest) {
      // Send XML header
      gfHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::XML_API_SEARCH_BLANK);

      // We're done here
      exit();
    }

    $addonXML = gfReadFile($gaRuntime['componentContentPath'] . 'addon.xml');

    $intResultCount = count($aSearchManifest);

    $searchXML = self::XML_TAG . NEW_LINE . '<searchresults total_results="' . $intResultCount .'">' . NEW_LINE;
    
    foreach ($aSearchManifest as $_value) {     
      $_addonXML = $addonXML;
      $_addonType = null;
      
      if (!$_value['homepageURL']) {
        $_addonHomepageURL = '';
      }
      else {
        $_addonHomepageURL = $_value['homepageURL'];
      }

      $_addonXPInstall = $_value['xpinstall'][$_value['releaseXPI']];
      $_addonTargetApplication = $_addonXPInstall['targetApplication'][$gaRuntime['targetApplicationID']];

      switch ($_value['type']) {
        case 'extension':
          $_addonType = 1;
          break;
        case 'theme':
          $_addonType = 2;
          break;
        case 'langpack':
          $_addonType = 8;
          break;
        default:
          $_addonType = 0;
      }        

      $substs = array(
        '{%ADDON_TYPE}'         => $_addonType,
        '{%ADDON_ID}'           => $_value['id'],
        '{%ADDON_VERSION}'      => $_addonXPInstall['version'],
        '{%ADDON_EPOCH}'        => $_addonXPInstall['epoch'],
        '{%ADDON_NAME}'         => $_value['name'],
        '{%ADDON_CREATOR}'      => $_value['creator'],
        '{%ADDON_CREATORURL}'   => 'about:blank',
        '{%ADDON_DESCRIPTION}'  => $_value['description'],
        '{%ADDON_URL}'          => 'http://' . $gaRuntime['currentDomain'] . $_value['url'],
        '{%ADDON_ICON}'         => 'http://' . $gaRuntime['currentDomain'] . $_value['icon'],
        '{%ADDON_HOMEPAGEURL}'  => $_addonHomepageURL,
        '{%APPLICATION_ID}'     => $gaRuntime['targetApplicationID'],
        '{%ADDON_MINVERSION}'   => $_addonTargetApplication['minVersion'],
        '{%ADDON_MAXVERSION}'   => $_addonTargetApplication['maxVersion'],
        '{%ADDON_XPI}'          => $_value['baseURL'] . $_value['id']
      );

      $_addonXML = gfSubst('simple', $substs, $_addonXML);
      
      $searchXML .= $_addonXML . NEW_LINE;
    }

    $searchXML .= '</searchresults>';
    
    // Send XML header
    gfHeader('xml');

    // Print Update RDF
    print($searchXML);

    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * This will read files from content or skin locations
  * 
  * @param $aSource     component content or skin
  * @param $aFilename   name of file
  ********************************************************************************************************************/
  private function getContentTemplate($aFilename, $aSource = 'skin') {
    global $gaRuntime;
    $aSource = ucfirst($aSource);
    return gfReadFile($gaRuntime['component' . $aSource . 'Path'] . $aFilename);
  }
}

// ====================================================================================================================

?>