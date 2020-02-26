<?php
// == | classReadManifest | ===================================================

class classWriteManifest {
  private $postData;
  private $validatorData;
  private $xpiUpload;
  private $iconUpload;
  private $previewUpload;
  private $bulkUpload;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    gfError(__CLASS__ . ' is currently busted.');
    if (!gfEnsureModule('database')) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }

    $this->xpiUpload          = gfSuperVar('files', 'xpiUpload');
    $this->iconUpload         = gfSuperVar('files', 'iconUpload');
    $this->previewUpload      = gfSuperVar('files', 'previewUpload');
    $this->bulkUpload         = gfSuperVar('files', 'bulkUpload');
  }

  /********************************************************************************************************************
  * Submits a new Add-on
  ********************************************************************************************************************/
  public function submitNewAddon($aLangPack = null, $aAccumulateErrors = null) {
    // Populate Post Data with data from $_POST
    $this->postData = array(
      'slug'          => gfSuperVar('post', 'slug'),
    );

    if (!$aLangPack) {
      if (!$this->postData['slug'] || preg_match('/[^a-z0-9\-]|(^\-)/', $this->postData['slug']) ||
          strlen($this->postData['slug']) < 3 || strlen($this->postData['slug']) > 32) {
        gfError('You did not specify a valid slug.
                   Slugs must be 3+ chars not exceeding 32 chars.
                   Please use only lower case letters, numbers, and/or dashes (-)');
      }
    }

    $this->validateAddon((bool)($GLOBALS['gaRuntime']['authentication']['level'] < 3), $aLangPack, $aAccumulateErrors);

    // For langpacks create a slug
    if ($aLangPack) {
      if (!($this->validatorData['supportedApplications']['palemoon'] ?? false)) {
        return $this->error('Only Pale Moon Language Packs are supported at this time', $aAccumulateErrors);
      }

      $this->postData['slug'] = strtolower('pm-' . str_replace('@palemoon.org', '', $this->validatorData['installManifest']['id']));
    }

    if (!$this->validatorData['finalResult']) {
      return $this->error('validatorError', $aAccumulateErrors, true);
    }

    $addonExists = $this->addonExists($this->validatorData['installManifest']['id'], $this->postData['slug']);

    if ($addonExists) {
      return $this->error('The the add-on id or slug you chose is not available. Please select another.', $aAccumulateErrors);
    }

    $addonAMO = $this->addonAMO($this->validatorData['installManifest']['id']);

    if ($addonAMO && $GLOBALS['gaRuntime']['authentication']['level'] < 3) {
      return $this->error('The add-on id <strong>' . $this->validatorData['installManifest']['id'] . '</strong> is known to have existed on the Mozilla Add-ons Site.</li><li>' .
                          'If you are the original developer of this add-on, you will need to contact a member of the Add-ons Team or a Phoebus Administrator to check the validity of this submission and verify your identity.</li><li>' .
                          'If this add-on is a proper fork, you can simply change the add-on\'s id and submit again.',
                          $aAccumulateErrors);
    }

    $addonType = null;
    switch($this->validatorData['installManifest']['type']) {
      case 2: $addonType = 'extension'; break;
      case 4: $addonType = 'theme'; break;
      case 8: $addonType = 'langpack'; break;
    }

    if (!$addonType) {
      return $this->error('Unable to determin Add-on Type', $aAccumulateErrors);
    }

    $releaseXPI = $this->postData['slug'] . '-' . $this->validatorData['installManifest']['version'] . '.xpi';
    $xpInstall[$releaseXPI]['version'] = $this->validatorData['installManifest']['version'];
    $xpInstall[$releaseXPI]['hash'] = hash_file('sha256', $this->xpiUpload['tmp_name']);
    $xpInstall[$releaseXPI]['epoch'] = time();
    $xpInstall[$releaseXPI]['targetApplication'] = $this->validatorData['installManifest']['targetApplication'];

    $addonManifest = array(
      'id'          => $this->validatorData['installManifest']['id'],
      'slug'        => $this->postData['slug'],
      'type'        => $addonType,
      'active'      => (bool)($GLOBALS['gaRuntime']['authentication']['level'] > 1),
      'reviewed'    => (bool)($GLOBALS['gaRuntime']['authentication']['level'] > 2),
      'releaseXPI'  => $releaseXPI,
      'category'    => 'unlisted',
      'url'         => '/addon/' . $this->postData['slug'] . '/',
      'name'        => $this->validatorData['installManifest']['name']['en-US'],
      'creator'     => $this->validatorData['installManifest']['creator'] ?? null,
      'description' => $this->validatorData['installManifest']['description']['en-US'] ?? null,
      'homepageURL' => $this->validatorData['installManifest']['homepageURL'] ?? null,
      'license'     => $this->validatorData['installManifest']['license'] ?? 'copyright',
      'xpinstall'   => json_encode($xpInstall, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? null
    );

    if ($aLangPack) {
      $addonManifest['category'] = 'language-packs';
      $addonManifest['creator'] = 'Moonchild Productions';
      $addonManifest['name'] = str_replace(' Language Pack', '', $addonManifest['name']);
      $addonManifest['description'] = 'Pale Moon Language Pack';
    }
    else {
      $GLOBALS['moduleAccount']->assignAddonToUser($GLOBALS['gaRuntime']['authentication']['username'],
                                                   $addonManifest['slug']);
    }

    $datastore = $this->filesToDatastore($addonManifest['id'],
                                         $addonManifest['slug'],
                                         $addonManifest['type'],
                                         $this->validatorData['installManifest']['version'],
                                         $aAccumulateErrors);

    if ($aAccumulateErrors && is_string($datastore)) {
      return $datastore;
    }

    $this->validatorData['supportedApplications']['addonID'] = $addonManifest['id'];

    // Insert the new manifest data into the database
    $query = "INSERT INTO ?n SET ?u";
    $GLOBALS['moduleDatabase']->query('normal', $query, 'client', $this->validatorData['supportedApplications']);
    $GLOBALS['moduleDatabase']->query('normal', $query, 'addon', $addonManifest);

    if ($aAccumulateErrors) {
      return true;
    }

    return $addonManifest['slug'];
  }

  /********************************************************************************************************************
  * Update Add-on Release
  ********************************************************************************************************************/
  public function updateAddonRelease($aAddonManifest, $aLangPack = null, $aAccumulateErrors = null) {
    // Populate Post Data with data from $_POST
    $this->postData = array(
      'slug'          => gfSuperVar('post', 'slug'),
    );

    if (!$aAccumulateErrors) {
      // Sanity
      if (!$this->postData['slug']) {
        return $this->error('Slug was not found in POST');
      }

      if ($this->postData['slug'] != $aAddonManifest['slug']) {
        return $this->error('POST Slug does not match GET/Manifest Slug');
      }
    }

    $this->validateAddon(null, $aLangPack, $aAccumulateErrors);

    $addonType = null;

    if (!$this->validatorData['finalResult']) {
      return $this->error('validatorError', $aAccumulateErrors, true);
    }

    switch($this->validatorData['installManifest']['type']) {
      case 2: $addonType = 'extension'; break;
      case 4: $addonType = 'theme'; break;
      case 8: $addonType = 'langpack'; break;
    }

    if ($aAddonManifest['type'] != $addonType) {
      return $this->error('Unable to determin Add-on Type', $aAccumulateErrors);
    }

    if ($aLangPack && !($this->validatorData['supportedApplications']['palemoon'] ?? false)) {
      return $this->error('Only Pale Moon Language Packs are supported at this time', $aAccumulateErrors);
    }

    if ($aAddonManifest['id'] != $this->validatorData['installManifest']['id']) {
      return $this->error('Add-on ID mismatch', $aAccumulateErrors);
    }

    $vcCurr = $aAddonManifest['xpinstall'][$aAddonManifest['releaseXPI']]['version'];
    $vcComp = $this->validatorData['installManifest']['version'];
    $vcResult = ToolkitVersionComparator::compare($vcComp, $vcCurr);

    if ($vcResult <= 0) {
      return $this->error('The submitted add-on\'s version is the same or lower as the current release xpi', $aAccumulateErrors);
    }

    $releaseXPI = $aAddonManifest['slug'] . '-' . $this->validatorData['installManifest']['version'] . '.xpi';
    $xpInstall = $aAddonManifest['xpinstall'];
    $xpInstall[$releaseXPI]['version'] = $this->validatorData['installManifest']['version'];
    $xpInstall[$releaseXPI]['hash'] = hash_file('sha256', $this->xpiUpload['tmp_name']);
    $xpInstall[$releaseXPI]['epoch'] = time();
    $xpInstall[$releaseXPI]['targetApplication'] = $this->validatorData['installManifest']['targetApplication'];

    $addonManifest = array(
      'releaseXPI'  => $releaseXPI,
      'license'     => $this->validatorData['installManifest']['license'] ?? $aAddonManifest['license'],
      'xpinstall'   => json_encode($xpInstall, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? null
    );

    if (!$aLangPack) {
      $addonManifest['name']        = $this->validatorData['installManifest']['name']['en-US'];
      $addonManifest['creator']     = $this->validatorData['installManifest']['creator'];
      $addonManifest['description'] = $this->validatorData['installManifest']['description']['en-US'];
      $addonManifest['homepageURL'] = $this->validatorData['installManifest']['homepageURL'] ?? null;
    }

    $datastore = $this->filesToDatastore($aAddonManifest['id'],
                                         $aAddonManifest['slug'],
                                         $aAddonManifest['type'],
                                         $this->validatorData['installManifest']['version'],
                                         $aAccumulateErrors);

    if ($aAccumulateErrors && is_string($datastore)) {
      return $datastore;
    }

    // Update the manifest data in the database
    $queryClient = "UPDATE `client` SET ?u WHERE `addonID` = ?s";
    $GLOBALS['moduleDatabase']->query('normal',
                                      $queryClient,
                                      $this->validatorData['supportedApplications'],
                                      $aAddonManifest['id']);

    $queryAddon = "UPDATE `addon` SET ?u WHERE `slug` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $queryAddon, $addonManifest, $aAddonManifest['slug']);

    if ($aAccumulateErrors) {
      return true;
    }

    return $aAddonManifest['type'] . 's';
  }

  /********************************************************************************************************************
  * Updates the manifest data for Extensions, Themes, and Language Packs (if allowed)
  ********************************************************************************************************************/
  public function updateAddonMetadata($aAddonManifest) {
    // Populate Post Data with data from $_POST
    $this->postData = array(
      'slug'          => gfSuperVar('post', 'slug'),
      'active'        => (bool)gfSuperVar('post', 'active'),
      'reviewed'      => (bool)gfSuperVar('post', 'reviewed'),
      'category'      => gfSuperVar('post', 'category'),
      'license'       => gfSuperVar('post', 'license'),
      'licenseText'   => gfSuperVar('post', 'licenseText'),
      'repository'    => gfSuperVar('post', 'repository'),
      'supportURL'    => gfSuperVar('post', 'supportURL'),
      'supportEmail'  => gfSuperVar('post', 'supportEmail'),
      'tags'          => gfSuperVar('post', 'tags'),
      'url'           => '/addon/' . $aAddonManifest['slug'] . '/',
      'content'       => gfSuperVar('post', 'content')
    );

    // Sanity
    if (!$this->postData['slug']) {
      gfError('Slug was not found in POST');
    }

    if ($this->postData['slug'] != $aAddonManifest['slug']) {
      gfError('POST Slug does not match GET/Manifest Slug');
    }

    if ($aAddonManifest['type'] == 'langpack') {
      // Special case for language packs
      $this->postData['reviewed'] = true;
      $this->postData['content'] = null;
      $this->postData['category'] = 'language-packs';
      unset($this->postData['repository'],
            $this->postData['supportURL'],
            $this->postData['supportEmail']);
    }

    // Hackers are a superstitious cowardly lot
    if ($GLOBALS['gaRuntime']['authentication']['level'] < 3) {
      unset($this->postData['active']);
      unset($this->postData['reviewed']);
      unset($this->postData['slug']);
    }

    if (empty($this->postData['content'])) {
      $this->postData['content'] = null;
    }

    if (empty($this->postData['licenseText'])) {
      if ($this->postData['license'] == 'custom') {
        gfError('You must specify a custom license text');
      }

      $this->postData['licenseText'] = null;
    }

    // Check for and update icons and previews
    $this->filesToDatastore($aAddonManifest['id'], $aAddonManifest['slug'], $aAddonManifest['type']);

    unset($this->postData['slug']);

    // Insert the new manifest data into the database
    $query = "UPDATE `addon` SET ?u WHERE `slug` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData, $aAddonManifest['slug']);

    return true;
  }

  /********************************************************************************************************************
  * Submits a new External
  ********************************************************************************************************************/
  public function submitNewExternal() {
    // Populate Post Data with data from $_POST
    $this->postData = array(
      'slug'          => gfSuperVar('post', 'slug'),
      'active'        => false,
      'reviewed'      => true,
      'type'          => 'external'
    );

    if (!$this->postData['slug'] || preg_match('/[^a-z0-9\-]|(^\-)/', $this->postData['slug']) ||
        strlen($this->postData['slug']) < 3 || strlen($this->postData['slug']) > 32) {
      gfError('You did not specify a valid slug.
                 Slugs must be 3+ chars not exceeding 32 chars.
                 Please use only lower case letters, numbers, and/or dashes (-)');
    }

    $externalID = $this->postData['slug'] . '@external';

    $addonExists = $this->addonExists($externalID, $this->postData['slug']);

    if ($addonExists) {
      gfError('The slug you chose is not available. Please select another.');
    }

    $this->postData['id'] = $externalID;

    // Insert the new manifest data into the database
    $query = "INSERT INTO ?n SET ?u";
    $GLOBALS['moduleDatabase']->query('normal', $query, 'client', array('addonID' => $this->postData['id']));
    $GLOBALS['moduleDatabase']->query('normal', $query, 'addon', $this->postData);

    return $this->postData['slug'];
  }

  /********************************************************************************************************************
  * Updates the manifest data for Externals
  ********************************************************************************************************************/
  public function updateExternalMetadata($aAddonManifest) {
    // Populate Post Data with data from $_POST
    $this->postData = array(
      'slug'          => gfSuperVar('post', 'slug'),
      'active'        => (bool)gfSuperVar('post', 'active'),
      'reviewed'      => true,
      'category'      => gfSuperVar('post', 'category'),
      'name'          => gfSuperVar('post', 'name'),
      'description'   => gfSuperVar('post', 'description'),
      'url'           => gfSuperVar('post', 'url'),
      'tags'          => gfSuperVar('post', 'tags'),
    );

    $arrayApplications = array(
      'palemoon'      => (bool)gfSuperVar('post', 'palemoon'),
      'basilisk'      => (bool)gfSuperVar('post', 'basilisk'),
      'ambassador'    => (bool)gfSuperVar('post', 'ambassador'),
      'borealis'      => (bool)gfSuperVar('post', 'borealis'),
      'interlink'     => (bool)gfSuperVar('post', 'interlink'),
    );

    // Sanity
    if (!$this->postData['slug']) {
      gfError('Slug was not found in POST');
    }

    if ($this->postData['slug'] != $aAddonManifest['slug']) {
      gfError('POST Slug does not match GET/Manifest Slug');
    }

    foreach ($this->postData as $_key => $_value) {
      if ($_key != 'active' && $_key != 'tags' && !$_value) {
        gfError('Please ensure that all fields are filled in');
      }
    }

    // Check for and update icons and previews
    $this->filesToDatastore($aAddonManifest['id'], $aAddonManifest['slug'], $aAddonManifest['type']);

    unset($this->postData['slug']);

    // Insert the new manifest data into the database
    $query = "UPDATE `addon` JOIN `client` ON addon.id = client.addonID SET ?u WHERE `slug` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, array_merge($this->postData, $arrayApplications), $aAddonManifest['slug']);

    return true;
  }

  /********************************************************************************************************************
  * Bulk Add-on Uploader
  ********************************************************************************************************************/
  public function bulkUploader($aType) {
    if ($aType != 'langpack') {
      gfError('Unknown bulk upload type');
    }

    if (!$this->bulkUpload || $this->bulkUpload['type'] != 'application/zip') {
      gfError('An error occured with the uploaded file. Please try again.');
    }
    
    $obj = ROOT_PATH . OBJ_RELPATH . 'bulk-upload/' . $aType . '-' . time() . '/';
    $zip = new ZipArchive();

    if (!@$zip->open($this->bulkUpload['tmp_name'])) {
      gfError('Could not read zip file');
    }

    $result = @mkdir($obj);

    if (!$result) {
      gfError('Could not create ' . $obj);
    }

    $result = @$zip->extractTo($obj);

    if (!$result) {
      gfError('There was an error extracting the zip file');
    }

    $zip->close();

    $glob = glob($obj . '*.xpi');

    if (!$glob || empty($glob)) {
      gfError('There does not seem to be any XPI files in ' . $obj);
    }

    $accumulatedErrors = [];
    $accumulatedMessages = [];
    $addons = array(
      'new'      => [],
      'existing' => []
    );
    
    foreach ($glob as $_value) {
      $basename = '<strong>' . basename($_value) . ':</strong> ';
      if (mime_content_type($_value) == 'application/zip') {
        $this->xpiUpload = array(
          'tmp_name' => $_value,
          'type' => mime_content_type($_value)
        );

        $_result = $this->validateAddon(false, true, true, true);
        
        if (is_string($_result)) {
          if (str_contains($_result, 'uploaded') || str_contains($_result, 'Jetpack') ||
              str_contains($_result, 'Jetpack') || str_contains($_result, 'WebExtensions')) {
            $accumulatedErrors[] = $basename . $_result;
            continue; 
          }
        }

        $_addonExists = $this->addonExists($_result, 'will-never-match');      

        if ($_addonExists) {
          $addons['existing'][strtolower('pm-' . str_replace('@palemoon.org', '', $_result))] = $_value;
        }
        else {
          $addons['new'][] = $_value;
        }
      }
      else {
        $accumulatedErrors[] = $basename . 'Does not seem to be an XPI file';
      }

      $this->xpiUpload = null;
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Submit
    foreach ($addons['new'] as $_value) {
      $basename = '<strong>' . basename($_value) . ':</strong> ';
      $this->xpiUpload = array(
        'tmp_name' => $_value,
        'type' => mime_content_type($_value)
      );

      $accumulatedMessages[] = $basename . 'Submitting';
      $process = $this->submitNewAddon(true, true);

      if (is_string($process)) {
        if ($process == 'validatorError') {
          foreach ($this->validatorData['errors'] as $_value2) {
            $accumulatedErrors[] = $basename . $_value2;
          }
        }

        $accumulatedErrors[] = $basename . $process;
        $accumulatedMessages[] = $basename . 'SUBMIT FAILED';
        continue;
      }

      $accumulatedMessages[] = $basename . 'Submitted';
      $this->validatorData = null;
    }

    // Update
    foreach ($addons['existing'] as $_key => $_value) {
      $basename = '<strong>' . basename($_value) . ' - ' . $_key . ':</strong> ';
      $this->xpiUpload = array(
        'tmp_name' => $_value,
        'type' => mime_content_type($_value)
      );

      $accumulatedMessages[] = $basename . 'Getting manifest data';
      $addonManifest = $GLOBALS['moduleReadManifest']->getAddon('panel-by-slug', $_key);

      if (!$addonManifest) {
        $accumulatedErrors[] = $basename . 'Could not get manifest data';
        $accumulatedMessages[] = $basename . 'GET MANIFEST FAILED';
        continue;
      }

      $accumulatedMessages[] = $basename . 'Got manifest data';


      $accumulatedMessages[] = $basename . 'Updating Release';
      $process = $this->updateAddonRelease($addonManifest, true, true);

      if (is_string($process)) {
        if ($process == 'validatorError') {
          foreach ($this->validatorData['errors'] as $_value2) {
            $accumulatedErrors[] = $basename . $_value2;
          }
        }

        $accumulatedErrors[] = $basename . $process;
        $accumulatedMessages[] = $basename . 'UPDATE FAILED';
        continue;
      }

      $accumulatedMessages[] = $basename . 'Updated Release';
    }

    // ----------------------------------------------------------------------------------------------------------------

    //gfError([$accumulatedErrors, $accumulatedMessages, $glob, $addons], 99);
    return ['errors' => $accumulatedErrors, 'messages' => $accumulatedMessages];
  }

  /********************************************************************************************************************
  * Public Validator Access
  ********************************************************************************************************************/
  public function publicValidator() {
    if (!$this->xpiUpload) {
      gfError('You did not upload an XPI file');
    }

    $checkID = true;
    $langPack = null;
    $idOnly = null;
    $result = $this->validateAddon($checkID, $langPack, $idOnly);

    if ($idOnly) {
      return $result;
    }

    return $this->validatorData;
  }

  /********************************************************************************************************************
  * Validates an XPI file
  ********************************************************************************************************************/
  private function validateAddon($aCheckID = null, $aLangPack = null,
                                 $aAccumulateErrors = null, $aReturnIDOnly = null) {
    $this->validatorData = array(
      'errors' => [],
      'status' => array(
        'installManifestExists'       => null,
        'webExManifestExists'         => null,
        'oldJetpackManifestExists'    => null,
        'hasID'                       => null,
        'hasType'                     => null,
        'hasVersion'                  => null,
        'hasCreator'                  => null,
        'hasName'                     => null,
        'hasDescription'              => null,
        'hasTargetApplication'        => null,
        'hasEmbeddedWebExtension'     => null,
        'hasUpdateURL'                => null,
        'hasUpdateKey'                => null,
        'isValidID'                   => null,
        'isRestrictedID'              => null,
        'isValidTargetApplication'    => null,
        'isValidTargetAppVersions'    => null,
      ),
      'installManifest' => null,
      'supportedApplications' => null,
      'finalResult' => null,
    );

    $arrayAllowedTypes = ['application/zip', 'application/x-xpinstall', 'application/java-archive'];
    /* 2 = Extension
     * 4 = Theme
     * 8 = Locale (langpack)
     * 32 = Multiple Item Package
     * 64 = Spell check dictionary */
    $arrayAddonTypes = ['2', '4'];

    // Special Case for langpacks
    if ($aLangPack) {
      $arrayAddonTypes = ['8'];
    }

    $strInstallManifest = 'install.rdf';
    $strChromeManifest= 'chrome.manifest';
    $strJetpackManifest = 'package.json';
    $strOldJetpackManifest = 'harness-options.json';
    $strWebExManifest = 'manifest.json';
    $strRegexGUID = '/^\{[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}$/i';
    $strRegexID = '/[a-z0-9-\._]+\@[a-z0-9-\._]+/i';

    $arrayRestrictedIDs = array(
      '-bfc5-fc555c87dbc4}', // Moonchild Productions
      '-9376-3763d1ad1978}', // Pseudo-Static
      '-b98e-98e62085837f}', // Ryan
      '-9aa0-aa0e607640b9}', // BinOC
      'palemoon.org',
      'basilisk-browser.org',
      'thereisonlyxul.org',
      'binaryoutcast.com',
      'mattatobin.com',
      'mozilla.org',
      'lootyhoof-pm',
      'srazzano.com' // BANNED FOR LIFE
    );

    // ----------------------------------------------------------------------------------------------------------------

    // Check to see if the xpi file is really an xpi file
    if (!in_array($this->xpiUpload['type'], $arrayAllowedTypes)) {
      if ($aReturnIDOnly) {
        $this->validatorData = null;
        return null;
      }

      return $this->error('File uploaded is not a valid XPI file', $aAccumulateErrors);
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Read and set status for install.rdf
    $this->validatorData['installManifest'] = $this->readFileFromArchive($this->xpiUpload['tmp_name'],
                                                                         $strInstallManifest);

    if ($this->validatorData['installManifest']) {
      $this->validatorData['installManifest'] =
        $GLOBALS['moduleMozillaRDF']->parseInstallManifest($this->validatorData['installManifest']);

      if (is_string($this->validatorData['installManifest'])) {
        gfError('RDF Parsing Error: ' . $this->validatorData['installManifest'], $aAccumulateErrors);
      }
    }

    if (is_array($this->validatorData['installManifest'])) {
      $this->validatorData['status']['installManifestExists'] = true;
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Look for other types of manifest files
    $this->validatorData['status']['oldJetpackManifestExists'] = (bool)$this->readFileFromArchive($this->xpiUpload['tmp_name'],
                                                                                      $strOldJetpackManifest,
                                                                                      true);
    if ($this->validatorData['status']['oldJetpackManifestExists']) {
      return $this->error('Old style Jetpack based extensions are almost certainly not going to work properly, thus they are unsupported',
             $aAccumulateErrors);
    }

    $this->validatorData['status']['webExManifestExists'] = (bool)$this->readFileFromArchive($this->xpiUpload['tmp_name'],
                                                                                        $strWebExManifest,
                                                                                        true);
    if ($this->validatorData['status']['webExManifestExists']) {
      return $this->error('WebExtensions are not supported', $aAccumulateErrors);
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Special case if we ONLY want to extract the ID from an xpi file..
    // This code is SO screwy but it works.. to hell with it...
    if ($aReturnIDOnly) {
      if ($this->validatorData['installManifest']['id'] ?? false) {
        $result = $this->validatorData['installManifest']['id'];
      }

      if (!($this->validatorData['installManifest']['type'] ?? false) ||
          !in_array($this->validatorData['installManifest']['type'], $arrayAddonTypes) ?? false) {
        $result = null;
      }

      $this->validatorData = null;
      return $result;
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Install Manifest Sanity
    if ($this->validatorData['status']['installManifestExists']) {
      // Add-on ID
      $this->validatorData['status']['hasID'] =
        (bool)($this->validatorData['installManifest']['id'] ?? null);

      if (!$this->validatorData['status']['hasID']) {
        $this->validatorData['errors'][] =
          'em:id is missing from install.rdf';
      }

      // Add-on Type
      $this->validatorData['status']['hasType'] =
        (bool)($this->validatorData['installManifest']['type'] ?? null);

      if (!$this->validatorData['status']['hasType'] ||
          !in_array($this->validatorData['installManifest']['type'], $arrayAddonTypes)) {
        $this->validatorData['errors'][] =
          'em:type is either missing or not supported by this submission mechanism';
      }

      // Add-on Version
      $this->validatorData['status']['hasVersion'] =
        (bool)($this->validatorData['installManifest']['version'] ?? null);

      if (!$this->validatorData['status']['hasVersion']) {
        $this->validatorData['errors'][] =
          'em:version is missing from install.rdf';
      }

      // Add-on Creator
      $this->validatorData['status']['hasCreator'] =
        (bool)($this->validatorData['installManifest']['creator'] ?? null);

      if (!$this->validatorData['status']['hasCreator']) {
        $this->validatorData['errors'][] =
          'em:creator is missing from install.rdf';
      }

      // Add-on Name
      $this->validatorData['status']['hasName'] =
        (bool)($this->validatorData['installManifest']['name']['en-US'] ?? null);

      if (!$this->validatorData['status']['hasName']) {
        $this->validatorData['errors'][] =
          'em:name is missing from install.rdf';
      }

      if (!$aLangPack) {
        // Add-on Description
        $this->validatorData['status']['hasDescription'] =
          (bool)($this->validatorData['installManifest']['description']['en-US'] ?? null);

        if (!$this->validatorData['status']['hasDescription']) {
          $this->validatorData['errors'][] =
            'em:description is missing from install.rdf';
        }
      }

      // Add-on targetApplication
      $this->validatorData['status']['hasTargetApplication'] =
        (bool)($this->validatorData['installManifest']['targetApplication'] ?? null);

      if (!$this->validatorData['status']['hasTargetApplication']) {
        $this->validatorData['errors'][] =
          'em:targetApplication is missing from install.rdf';
      }

      // Add-on Embedded WebExtension
      $this->validatorData['status']['hasEmbeddedWebExtension'] =
        (bool)($this->validatorData['installManifest']['hasEmbeddedWebExtension'] ?? null);

      if ($this->validatorData['status']['hasEmbeddedWebExtension']) {
        $this->validatorData['errors'][] =
          'Embedded WebExtensions are not supported';
      }

      // Add-on updateURL and updateKey
      $this->validatorData['status']['hasUpdateURL'] =
        (bool)($this->validatorData['installManifest']['updateURL'] ?? null);

      $this->validatorData['status']['hasUpdateKey'] =
        (bool)($this->validatorData['installManifest']['updateKey'] ?? null);

      if ($this->validatorData['status']['hasUpdateURL'] || $this->validatorData['status']['hasUpdateKey']) {
        $this->validatorData['errors'][] =
          'em:updateURL and/or em:updateKey conflicts with the Add-on Update Service';
      }

      // We support an em:license with a value matching the LICENSES array class constant in classReadManifest
      // Except for custom of course.. That will have to be done through the panel
      if ($this->validatorData['installManifest']['license'] ?? false) {
        $arrayLicenses = array_keys(array_change_key_case($GLOBALS['moduleReadManifest']::LICENSES, CASE_LOWER));
        $licenseCode = strtolower($this->validatorData['installManifest']['license']);
    
        if (!in_array($licenseCode, $arrayLicenses) || $licenseCode == 'custom') {
          $this->validatorData['installManifest']['license'] = null;
        }
      }
      else {
        $this->validatorData['installManifest']['license'] = null;
      }
    }
    else {
      return $this->error('install.rdf is missing from xpi file', $aAccumulateErrors);
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Check to make sure that the add-on ID is valid and is not restricted
    if ($aCheckID) {
      if (preg_match($strRegexGUID, $this->validatorData['installManifest']['id']) ||
          preg_match($strRegexID, $this->validatorData['installManifest']['id'])) {
        $this->validatorData['status']['isValidID'] = true;
      }

      if (!$this->validatorData['status']['isValidID']) {
        $this->validatorData['status']['isValidID'] = false;
        $this->validatorData['errors'][] =
          'em:id is not valid. Add-on IDs should be in the Windows GUID foum that uses lowercase a-f or in the form of user@host.';
      }

      foreach ($arrayRestrictedIDs as $_value) {
        if (str_contains($this->validatorData['installManifest']['id'], $_value)) {
          $this->validatorData['status']['isRestrictedID'] = true;
        }
      }

      if ($this->validatorData['status']['isRestrictedID']) {
        $this->validatorData['errors'][] =
          'em:id str_contains restricted elements. Please change it.';
      }
      else {
        $this->validatorData['status']['isRestrictedID'] = false;
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Check to make sure there is at least one supported targetApplication
    foreach (TARGET_APPLICATION as $_key => $_value) {
      if (array_key_exists($_value['id'], $this->validatorData['installManifest']['targetApplication'])) {
        $this->validatorData['supportedApplications'][$_key] = true;

        if ($_key != 'toolkit') {
          $this->validatorData['status']['isValidTargetApplication'] = true;
        }
      }
      else {
        $this->validatorData['supportedApplications'][$_key] = false;
      }
    }

    if (!$this->validatorData['status']['isValidTargetApplication']) {
      $this->validatorData['status']['isValidTargetApplication'] = false;
      $this->validatorData['errors'][] =
        'em:targetApplication does not contain a currently supported application id';
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Infinite maxVersions are not allowed, I don't care WHO you are or what application you are targeting...
    foreach ($this->validatorData['installManifest']['targetApplication'] as $_key => $_value) {
      if ($_key == 'toolkit@mozilla.org') {
        continue;
      }

      foreach ($_value as $_key2 => $_value2) {
        if ($_key2 == 'maxVersion' && $_value2 == '*') {
          $this->validatorData['status']['isValidTargetAppVersions'] = false;
          $this->validatorData['errors'][] =
            'em:targetApplication ' . $_key . ' has an infinite maxVersion. This is not allowed.';
        }
      }
    }

    if ($this->validatorData['status']['isValidTargetAppVersions'] == null) {
      $this->validatorData['status']['isValidTargetAppVersions'] = true;
    }

    // ----------------------------------------------------------------------------------------------------------------
    
    // Set the final result
    $this->validatorData['finalResult'] = empty($this->validatorData['errors']);

    return true;
  }

  /********************************************************************************************************************
  * Read file from zip
  ********************************************************************************************************************/
  private function readFileFromArchive($aArchive, $aFile, $aCheckExistance = null) {
    $file = gfSuperVar('var', @file_get_contents('zip://' . $aArchive . "#" . $aFile));

    if (!$file) {
      return null;
    }

    if ($aCheckExistance) {
      unset($file);
      return true;
    }

    return $file;
  }

  /********************************************************************************************************************
  * Checks if a slug or id in the database
  ********************************************************************************************************************/
  private function addonExists($aID, $aSlug) {
    $query = "SELECT `id`, `slug` FROM `addon` WHERE `id` = ?s OR `slug` = ?s";
    $result = $GLOBALS['moduleDatabase']->query('rows', $query, $aID, $aSlug);

    if ($result) {
      return true;
    }

    return null;
  }

  /********************************************************************************************************************
  * Checks if an ID was known to be on AMO
  ********************************************************************************************************************/
  private function addonAMO($aSlug) {
    $query = "SELECT `id`, `blocked` FROM `amo` WHERE `id` = ?s AND `blocked` = 1";
    $result = $GLOBALS['moduleDatabase']->query('rows', $query, $aSlug);

    if ($result) {
      return true;
    }

    return null;
  }

  /********************************************************************************************************************
  * Moves files to the datastore
  ********************************************************************************************************************/
  private function filesToDatastore($aID, $aSlug, $aType, $aVersion = null, $aAccumulateErrors = null) {
    $strAddonDir = ROOT_PATH . DATASTORE_RELPATH . 'addons/' . $aSlug;

    // Deal with legacy external ids
    if ($aType == 'external') {
      if ($this->xpiUpload) {
        return $this->error('I have no idea how you managed to upload an XPI file for an external but.. NOPE!', $aAccumulateErrors);
      }

      if (str_contains($aID, '@ex-')) {
        $strAddonDir = ROOT_PATH . DATASTORE_RELPATH . 'addons/' . preg_replace('/(.*)\@(.*)/iU', '$2', $aID);
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Check if the add-on directory exists and if we are uploading files
    // If it doesn't and we are then create it
    if (!file_exists($strAddonDir) && ($this->xpiUpload || $this->iconUpload || $this->previewUpload)) {
      mkdir($strAddonDir, 0755, true);

      if (!file_exists($strAddonDir)) {
        return $this->error('Could not create ' . str_replace(ROOT_PATH, '', $strAddonDir), $aAccumulateErrors);
      }
    }

    // ----------------------------------------------------------------------------------------------------------------

    if ($this->xpiUpload) {
      if (!$aVersion) {
        return $this->error('Not sure why but $aVersion was not passed to this method', $aAccumulateErrors);
      }

      if ($this->iconUpload || $this->previewUpload) {
        return $this->error('I have no idea how you managed to upload an icon/preview while uploading an XPI file but.. NOPE!', $aAccumulateErrors);
      }

      $xpiFile = '/' . $aSlug . '-' . $aVersion . '.xpi';

      if ($aType = 'langpack') {
        $result = @rename($this->xpiUpload['tmp_name'], $strAddonDir . $xpiFile);
      }
      else {
        $result = @move_uploaded_file($this->xpiUpload['tmp_name'], $strAddonDir . $xpiFile);
      }

      if (!$result) {
        return $this->error('Could not create ' . str_replace(ROOT_PATH, '', $strAddonDir) . $xpiFile, $aAccumulateErrors);
      }

      return true;
    }

    // ----------------------------------------------------------------------------------------------------------------

    $intMaxImageBytes = 524288;
    $arrayAllowedImageTypes = ['image/png'];
    $iconFile = '/icon.png';
    $previewFile = '/preview.png';

    // Handle icon
    if ($this->iconUpload) {
      if (!in_array($this->iconUpload['type'], $arrayAllowedImageTypes)) {
        gfError('Icon must be a png image!');
      }

      if ($this->iconUpload['size'] > $intMaxImageBytes) {
        gfError('Icon file size must not exceed ' . (string)$intMaxImageBytes . ' bytes!');
      }

      $result = @move_uploaded_file($this->iconUpload['tmp_name'], $strAddonDir . $iconFile);

      if (!$result) {
        gfError('Could not create ' . str_replace(ROOT_PATH, '', $strAddonDir) . $iconFile);
      }
    }

    // Handle preview
    if ($this->previewUpload) {
      if (!in_array($this->previewUpload['type'], $arrayAllowedImageTypes)) {
        gfError('Preview must be a png image!');
      }

      if ($this->previewUpload['size'] > $intMaxImageBytes) {
        gfError('Preview file size must not exceed ' . (string)$intMaxImageBytes . ' bytes!');
      }

      $result = @move_uploaded_file($this->previewUpload['tmp_name'], $strAddonDir . $previewFile);

      if (!$result) {
        gfError('Could not create ' . str_replace(ROOT_PATH, '', $strAddonDir) . $previewFile);
      }
    }

    return true;
  }

  /********************************************************************************************************************
  * Error method to better accumilate errors when using bulkLangPackUpload
  * Should only be used in bulkLangPackUpload, submitNewAddon, updateAddonRelease, and the xpi part of filesToDatastore
  ********************************************************************************************************************/
  private function error($aErrorMessage, $aAccumulateErrors = null, $aValidatorErrors = null) {
    if (!$aAccumulateErrors) {
      if ($aValidatorErrors) {
        $validatorErrors = '';

        foreach ($this->validatorData['errors'] as $_value) {
          $validatorErrors .= '<li>' . $_value . '</li>';
        }

        gfGenContent('Add-on Validator Error', '<ul>' . $validatorErrors . '</ul>');
      }

      gfError($aErrorMessage);
    }

    return $aErrorMessage;
  }
}

// ====================================================================================================================

?>