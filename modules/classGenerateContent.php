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
  function __construct($aUseSmarty = null) {
    // Assign current software state to a class property by reference
    $GLOBALS['arraySoftwareState'] = &$GLOBALS['arraySoftwareState'];

    // Set the Application ID
    $GLOBALS['arraySoftwareState']['targetApplicationID'] =
      TARGET_APPLICATION_ID[$GLOBALS['arraySoftwareState']['currentApplication']];

    // ----------------------------------------------------------------------------------------------------------------

    // Component Path
    $componentPath = dirname(COMPONENTS[$GLOBALS['arraySoftwareState']['requestComponent']]);

    // Component Content Path (for static content)
    $GLOBALS['arraySoftwareState']['componentContentPath'] = $componentPath . '/content/';

    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($GLOBALS['arraySoftwareState']['requestComponent'] == 'site') {
      $skin = $GLOBALS['arraySoftwareState']['currentApplication'];
    }

    $GLOBALS['arraySoftwareState']['componentSkinPath'] = $componentPath . '/skin/' . $skin . '/';
    $GLOBALS['arraySoftwareState']['componentSkinRelPath'] = 
      str_replace(ROOT_PATH, '', $GLOBALS['arraySoftwareState']['componentSkinPath']);

    // ----------------------------------------------------------------------------------------------------------------

    if ($aUseSmarty) {
      // Include Smarty
      require_once(LIBRARIES['smarty']);

      // Get smartyDebug HTTP GET Argument
      $GLOBALS['arraySoftwareState']['requestSmartyDebug'] = funcUnifiedVariable('get', 'smartyDebug');

      // Initalize Smarty
      $this->libSmarty = new Smarty();

      // Set Smarty Caching
      $this->libSmarty->caching = 0;

      // Set Smarty Debug
      $this->libSmarty->debugging = false;

      if ($GLOBALS['arraySoftwareState']['requestSmartyDebug']) {
        $this->libSmarty->debugging = $GLOBALS['arraySoftwareState']['debugMode'];
      }

      // Set Smarty Paths
      $smartyObjPath = ROOT_PATH . OBJ_RELPATH . '/smarty/' .
                       $GLOBALS['arraySoftwareState']['requestComponent'] .
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
    // This function will only serve the SITE component
    if (!$this->libSmarty) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - This method requires Smarty');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Read the Site Template
    $template = $this->getContentTemplate('site-template.xhtml');

    if (!$template) {
      funcError('Main template file could not be read or is missing');
    }

    // Read the Site Stylesheet
    $stylesheet = $this->getContentTemplate('site-stylesheet.css');

    if (!$stylesheet) {
      funcError('Mail stylesheet file could not be read or is missing');
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
        $content = $this->getContentTemplate('administration-addon-list.xhtml');
        break;
      case 'admin-list-users':
        $content = $this->getContentTemplate('administration-users-list.xhtml');
        break;
      case 'developer-edit-addon-metadata':
      case 'admin-edit-addon-metadata':
        $content = $this->getContentTemplate('addon-metadata.xhtml');
        break;
      case 'admin-edit-external-metadata':
        $content = $this->getContentTemplate('external-metadata.xhtml');
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
          funcError('Unkown template or content');
        }
    }

    if (!$content) {
      funcError('Content or template file could not be read or is missing');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Build the final template
    $finalTemplate = str_replace('{%SITE_STYLESHEET}', $stylesheet,
      str_replace('{%PAGE_CONTENT}', $content, $template)
    );

    // ----------------------------------------------------------------------------------------------------------------

    // Assign Data to Smarty
    $this->libSmarty->assign('APPLICATION_DEBUG', $GLOBALS['arraySoftwareState']['debugMode']);
    $this->libSmarty->assign('SITE_DOMAIN',
                             $GLOBALS['arraySoftwareState']['currentScheme'] . '://' .
                             $GLOBALS['arraySoftwareState']['currentDomain']);
    $this->libSmarty->assign('PAGE_TITLE', $aTitle);
    $this->libSmarty->assign('PAGE_PATH', $GLOBALS['arraySoftwareState']['requestPath']);
    $this->libSmarty->assign('BASE_PATH', $GLOBALS['arraySoftwareState']['componentSkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);
    $this->libSmarty->assign('SITE_NAME', $GLOBALS['arraySoftwareState']['currentName']);
    $this->libSmarty->assign('SEARCH_TERMS', $GLOBALS['arraySoftwareState']['requestSearchTerms']);
    $this->libSmarty->assign('APPLICATION_ID', $GLOBALS['arraySoftwareState']['targetApplicationID']);
    $this->libSmarty->assign('PAGE_TYPE', $aType);
    $this->libSmarty->assign('PAGE_DATA', $aData);
    $this->libSmarty->assign('EXTRA_DATA', $aExtraData);
    
    if ($GLOBALS['arraySoftwareState']['requestComponent'] == 'panel') {
      $this->libSmarty->assign('USER_LEVEL',
                               $GLOBALS['arraySoftwareState']['authentication']['level'] ?? 0);
    }

    // The Panel should NEVER be cached
    if ($GLOBALS['arraySoftwareState']['requestComponent'] == 'panel') {
      header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
    }

    // Send html header
    funcSendHeader('html');
    
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
    if ($GLOBALS['arraySoftwareState']['requestComponent'] != 'aus') {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ . ' - This method is designed to work with the AUS component only'
      );
    }

    if (!$aAddonManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::RDF_AUS_BLANK);

      // We're done here
      exit();
    }

    $updateRDF = file_get_contents($GLOBALS['arraySoftwareState']['componentContentPath'] . 'update.rdf');

    $addonXPInstall = $aAddonManifest['xpinstall'][$aAddonManifest['releaseXPI']];
    $addonTargetApplication = $addonXPInstall['targetApplication'][$GLOBALS['arraySoftwareState']['targetApplicationID']];
    
    // Language Packs are an 'item' as far as update.rdf is conserned
    if ($aAddonManifest['type'] == 'langpack') {
      $aAddonManifest['type'] = 'item';
    }
    
    $arrayFilterSubstitute = array(
      '{%ADDON_TYPE}'       => $aAddonManifest['type'],
      '{%ADDON_ID}'         => $aAddonManifest['id'],
      '{%ADDON_VERSION}'    => $addonXPInstall['version'],
      '{%APPLICATION_ID}'   => $GLOBALS['arraySoftwareState']['targetApplicationID'],
      '{%ADDON_MINVERSION}' => $addonTargetApplication['minVersion'],
      '{%ADDON_MAXVERSION}' => $addonTargetApplication['maxVersion'],
      '{%ADDON_XPI}'        => $aAddonManifest['baseURL'] . $aAddonManifest['id'],
      '{%ADDON_HASH}'       => $addonXPInstall['hash']
    );

    foreach ($arrayFilterSubstitute as $_key => $_value) {
      $updateRDF = str_replace($_key, $_value, $updateRDF);
    }

    // Send XML header
    funcSendHeader('xml');

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
    if (!$aSearchManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::XML_API_SEARCH_BLANK);

      // We're done here
      exit();
    }

    $addonXML = file_get_contents($GLOBALS['arraySoftwareState']['componentContentPath'] . 'addon.xml');

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
      $_addonTargetApplication = $_addonXPInstall['targetApplication'][$GLOBALS['arraySoftwareState']['targetApplicationID']];

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

      $_arrayFilterSubstitute = array(
        '{%ADDON_TYPE}'         => $_addonType,
        '{%ADDON_ID}'           => $_value['id'],
        '{%ADDON_VERSION}'      => $_addonXPInstall['version'],
        '{%ADDON_EPOCH}'        => $_addonXPInstall['epoch'],
        '{%ADDON_NAME}'         => $_value['name'],
        '{%ADDON_CREATOR}'      => $_value['creator'],
        '{%ADDON_CREATORURL}'   => 'about:blank',
        '{%ADDON_DESCRIPTION}'  => $_value['description'],
        '{%ADDON_URL}'          => 'http://' . $GLOBALS['arraySoftwareState']['currentDomain'] . $_value['url'],
        '{%ADDON_ICON}'         => 'http://' . $GLOBALS['arraySoftwareState']['currentDomain'] . $_value['icon'],
        '{%ADDON_HOMEPAGEURL}'  => $_addonHomepageURL,
        '{%APPLICATION_ID}'     => $GLOBALS['arraySoftwareState']['targetApplicationID'],
        '{%ADDON_MINVERSION}'   => $_addonTargetApplication['minVersion'],
        '{%ADDON_MAXVERSION}'   => $_addonTargetApplication['maxVersion'],
        '{%ADDON_XPI}'          => $_value['baseURL'] . $_value['id']
      );

      foreach ($_arrayFilterSubstitute as $_key => $_value) {
        $_addonXML = str_replace($_key, $_value, $_addonXML);
      }
      
      $searchXML .= $_addonXML . NEW_LINE;
    }

    $searchXML .= '</searchresults>';
    
    // Send XML header
    funcSendHeader('xml');

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
    $aSource = ucfirst($aSource);
    return @file_get_contents($GLOBALS['arraySoftwareState']['component' . $aSource . 'Path'] . $aFilename) ?? null;
  }
}

// ====================================================================================================================

?>