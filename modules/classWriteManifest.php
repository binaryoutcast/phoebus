<?php
// == | classReadManifest | ===================================================

class classWriteManifest {
  private $postData;
  private $xpiFile;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }

    $this->postData = array(
      'slug'          => funcUnifiedVariable('post', 'slug'),
      'active'        => (bool)funcUnifiedVariable('post', 'active'),
      'reviewed'      => (bool)funcUnifiedVariable('post', 'reviewed'),
      'category'      => funcUnifiedVariable('post', 'category'),
      'license'       => funcUnifiedVariable('post', 'license'),
      'licenseText'   => funcUnifiedVariable('post', 'licenseText'),
      'repository'    => funcUnifiedVariable('post', 'repository'),
      'supportURL'    => funcUnifiedVariable('post', 'supportURL'),
      'supportEmail'  => funcUnifiedVariable('post', 'supportEmail'),
      'tags'          => funcUnifiedVariable('post', 'tags'),
      'content'       => funcUnifiedVariable('post', 'content')
    );

    $this->xpiFile = funcUnifiedVariable('files', 'xpiFile');
  }

  public function updateAddonMetadata($aAddonManifest) {
    // Sanity
    if (!$this->postData['slug']) {
      funcError('Slug was not found in POST');
    }

    if ($this->postData['slug'] != $aAddonManifest['slug']) {
      funcError('POST Slug does not match GET/Manifest Slug');
    }

    // Hackers are a superstitious cowardly lot
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      unset($this->postData['active']);
      unset($this->postData['reviewed']);
      unset($this->postData['slug']);
    }

    if (empty($this->postData['content'])) {
      $this->postData['content'] = null;
    }

    if (empty($this->postData['licenseText'])) {
      if ($this->postData['license'] == 'custom') {
        funcError('You must specify a custom license text');
      }

      $this->postData['licenseText'] = null;
    }

    unset($this->postData['slug']);

    // Insert the new manifest data into the database
    $query = "UPDATE `addon` SET ?u WHERE `slug` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData, $aAddonManifest['slug']);

    return true;
  }
}

// ====================================================================================================================

?>