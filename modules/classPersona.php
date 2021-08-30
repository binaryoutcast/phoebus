<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classPersonas | ===============================================================================================

class classPersona {
  /********************************************************************************************************************
  * Class constructor that sets initial state of things
  ********************************************************************************************************************/
  function __construct() {  
    gfEnsureModules(__CLASS__, 'database');
  }

 /********************************************************************************************************************
  * Gets a single manifest for a persona by ID
  * 
  * @param $_addonID        Add-on ID either GUID or user@host
  * @returns                reduced add-on manifest or null
  ********************************************************************************************************************/
  public function getPersonaByID($_addonID) {
    global $gmDatabase;
    $query = "
      SELECT *
      FROM `persona`
      WHERE `id` = ?s
      ORDER BY `name`
    ";
    $queryResult = $gmDatabase->query('row', $query, $_addonID);

    if (!$queryResult) {
      return null;
    }
    
    $addonManifest = $this->funcProcessManifest($queryResult, null, true);
    
    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Method to replace a bunch of methods that are virtually identical
  * Mostly those that get an indexed array of manifests
  * 
  * @param $aQueryType      Type of query to be performed
  * @param $aQueryData      Data for the query such as slugs or search terms
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getPersonas($aQueryType, $aQueryData = null) {
    global $gmDatabase;

    $query = null;
    $returnInactive = null;
    $returnUnreviewed = null;
    $processContent = true;

    switch ($aQueryType) {
      case 'site-all-personas':
        $query = "
          SELECT *
          FROM `persona`
          ORDER BY `name`
        ";
        $queryResults = $gmDatabase->query('rows', $query);
        break;
      case 'panel-user-personas':
        $returnInactive = true;
        $returnUnreviewed = true;
        $query = "
          SELECT *
          FROM `persona`
          WHERE `slug` IN (?a)
          AND `type` IN ('extension', 'theme')
          ORDER BY `name`
        ";
        $queryResults = $gmDatabase->query('rows', $query, $aQueryData);
        break;
      case 'panel-all-personas':
        $returnInactive = true;
        $returnUnreviewed = true;
        $query = "
          SELECT *
          FROM `persona`
          ORDER BY `name`
        ";
        $queryResults = $gmDatabase->query('rows', $query);
        break;
      default:
        gfError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    if (!$queryResults) {
      return null;
    }

    $manifestData = array();
    
    foreach($queryResults as $_value) {
      $addonManifest = $this->funcProcessManifest($_value, $returnInactive, $returnUnreviewed);

      if (!$addonManifest) {
        continue;
      }

      $manifestData[] = $addonManifest;
    }

    return $manifestData;
  }

 /********************************************************************************************************************
  * Internal method to post-process an add-on manifest
  * 
  * @param $aPersonaManifest       add-on manifest
  * @param $aReturnInactive      Optional, return inactive add-on instead of null
  * @param $aReturnUnreviewed    Optional, return unreviewed add-on instead of null
  * @returns                    add-on manifest or null
  ********************************************************************************************************************/
  // This is where we do any post-processing on an Add-on Manifest
  private function funcProcessManifest($aPersonaManifest,
                                       $aReturnInactive = null,
                                       $aReturnUnreviewed = null) {
    global $gaRuntime;

    // Cast the int-strings to bool
    $aPersonaManifest['reviewed'] = (bool)$aPersonaManifest['reviewed'];
    $aPersonaManifest['active'] = (bool)$aPersonaManifest['active'];

    if (!$aPersonaManifest['active'] && !$aReturnInactive) {
      return null;
    }
    
    if (!$aPersonaManifest['reviewed'] && !$aReturnUnreviewed) {
      return null;
    }
  
    // Truncate description if it is too long..
    $aPersonaManifest['finalDescription'] = $aPersonaManifest['description'];
    if (array_key_exists('description', $aPersonaManifest) && strlen($aPersonaManifest['description']) >= 235) {
      $aPersonaManifest['finalDescription'] = substr($aPersonaManifest['description'], 0, 230) . '&hellip;';
    }

    // Assign Icon, Header, and Footer
    $strPersonaDomainPrefix = 'http://' . $gaRuntime['currentDomain'];
    $strPersonaBasePath = DATASTORE_RELPATH . 'personas/' . $aPersonaManifest['id'] . '/';

    $aPersonaManifest['headerURL'] = $strPersonaDomainPrefix . $strPersonaBasePath . 'header.png';
    
    if ($aPersonaManifest['hasFooter']) {
      $aPersonaManifest['footerURL'] = $strPersonaDomainPrefix . $strPersonaBasePath . 'footer.png';
    }

    $aPersonaManifest['iconURL'] = $strPersonaDomainPrefix . $strPersonaBasePath . 'icon.png';
    if (!file_exists('.' . $strPersonaBasePath . 'icon.png')) {
      $aPersonaManifest['iconURL'] = $strPersonaDomainPrefix . DATASTORE_RELPATH . 'addons/default/theme.png';
    }

    $aPersonaManifest['previewURL'] = $strPersonaDomainPrefix . $strPersonaBasePath . 'preview.png';
    if (!file_exists('.' . $strPersonaBasePath . 'preview.png')) {
      $this->funcCreatePreview('.' . $strPersonaBasePath);
    }

    // Persona Update URL
    $aPersonaManifest['updateURL'] =
      str_replace('http://', 'https://', $strPersonaDomainPrefix) . '/?component=aus&persona=' . $aPersonaManifest['id'];

    // Return Add-on Manifest to internal caller
    return $aPersonaManifest;
  }

  private function funcCreatePreview($aBasePath) {

    $desiredImageWidth = 240;
    $desiredImageHeight = 60;

    $source_path = $aBasePath . 'header.png';

    list($source_width, $source_height, $source_type) = getimagesize($source_path);

    switch ($source_type) {
        case IMAGETYPE_GIF:
            $source_gdim = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_JPEG:
            $source_gdim = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_gdim = imagecreatefrompng($source_path);
            break;
    }

    $source_aspect_ratio = $source_width / $source_height;
    $desired_aspect_ratio = $desiredImageWidth / $desiredImageHeight;

    if ($source_aspect_ratio > $desired_aspect_ratio) {
        /*
         * Triggered when source image is wider
         */
        $temp_height = $desiredImageHeight;
        $temp_width = ( int ) ($desiredImageHeight * $source_aspect_ratio);
    } else {
        /*
         * Triggered otherwise (i.e. source image is similar or taller)
         */
        $temp_width = $desiredImageWidth;
        $temp_height = ( int ) ($desiredImageWidth / $source_aspect_ratio);
    }

    /*
     * Resize the image into a temporary GD image
     */

    $temp_gdim = imagecreatetruecolor($temp_width, $temp_height);
    imagecopyresampled(
        $temp_gdim,
        $source_gdim,
        0, 0,
        0, 0,
        $temp_width, $temp_height,
        $source_width, $source_height
    );

    /*
     * Copy cropped region from temporary image into the desired GD image
     */

    $x0 = ($temp_width - $desiredImageWidth);
    $y0 = ($temp_height - $desiredImageHeight) / 2;
    $desired_gdim = imagecreatetruecolor($desiredImageWidth, $desiredImageHeight);
    imagecopy(
        $desired_gdim,
        $temp_gdim,
        0, 0,
        $x0, $y0,
        $desiredImageWidth, $desiredImageHeight
    );

    /*
     * Render the image
     * Alternatively, you can save the image in file-system or database
     */

    imagepng($desired_gdim, $aBasePath . 'preview.png');
  }

}

// ====================================================================================================================

?>