<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classMozillaRDF {
  const EM_NS = 'http://www.mozilla.org/2004/em-rdf#';
  const INSTALL_MANIFEST_RESOURCE = 'urn:mozilla:install-manifest';

  private $libRdfParser;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    // Include the Rdf_parser
    require_once(LIBRARIES['rdfParser']);
    $this->libRdfParser = new Rdf_parser();
  }

  /********************************************************************************************************************
  * Parses install.rdf using Rdf_parser class
  *
  * @param string     $manifestData
  * @return array     $data["manifest"]
  ********************************************************************************************************************/
  public function parseInstallManifest($manifestData) {
    $data = array();

    $this->libRdfParser->rdf_parser_create(null);
    $this->libRdfParser->rdf_set_user_data($data);
    $this->libRdfParser->rdf_set_statement_handler(array('classMozillaRDF', 'installManifestStatementHandler'));
    $this->libRdfParser->rdf_set_base('');

    if (!$this->libRdfParser->rdf_parse($manifestData, strlen($manifestData), true)) {
      return xml_error_string(xml_get_error_code($this->libRdfParser->rdf_parser['xml_parser']));
    }

    // Set the targetApplication data
    $targetArray = array();
    if (!empty($data['manifest']['targetApplication']) && is_array($data['manifest']['targetApplication'])) {
      foreach ($data['manifest']['targetApplication'] as $targetApp) {
        $id = $data[$targetApp][self::EM_NS."id"];
        $targetArray[$id]['minVersion'] = $data[$targetApp][self::EM_NS.'minVersion'];
        $targetArray[$id]['maxVersion'] = $data[$targetApp][self::EM_NS.'maxVersion'];
      }
    }

    $data['manifest']['targetApplication'] = $targetArray;

    $this->libRdfParser->rdf_parser_free();

    return $data['manifest'];
  }


  /********************************************************************************************************************
  * Parses install.rdf for our desired properties
  *
  * @param string     $manifestData
  * @param array &$data
  * @param string $subjectType
  * @param string $subject
  * @param string $predicate
  * @param int $ordinal
  * @param string $objectType
  * @param string $object
  * @param string $xmlLang
  ********************************************************************************************************************/
  static function installManifestStatementHandler(&$data,
                                                  $subjectType,
                                                  $subject,
                                                  $predicate,
                                                  $ordinal,
                                                  $objectType,
                                                  $object,
                                                  $xmlLang) {   
    //single properties - ignoring: optionsURL, aboutURL, and anything not listed
    $singleProps = array(
      'id' => 1,
      'type' => 1,
      'version' => 1,
      'creator' => 1,
      'homepageURL' => 1,
      'updateURL' => 1,
      'updateKey' => 1,
      'bootstrap' => 1,
      'hasEmbeddedWebExtension' => 1,
      'multiprocessCompatible' => 1,
      'skinnable' => 1,
      'strictCompatibility' => 1,
      'license' => 1,
      'iconURL' => 1,
      'icon64URL' => 1
    );
    
    //multiple properties - ignoring: File
    $multiProps = array(
      'contributor' => 1,
      'developer' => 1,
      'translator' => 1,
      'targetApplication' => 1
    );
    
    //localizable properties
    $l10nProps = array(
      'name' => 1,
      'description' => 1
    );

    //Look for properties on the install manifest itself
    if ($subject == self::INSTALL_MANIFEST_RESOURCE) {
      //we're only really interested in EM properties
      $length = strlen(self::EM_NS);
      if (strncmp($predicate, self::EM_NS, $length) == 0) {
        $prop = substr($predicate, $length, strlen($predicate)-$length);

        if (array_key_exists($prop, $singleProps) ) {
          $data['manifest'][$prop] = $object;
        }
        elseif (array_key_exists($prop, $multiProps)) {
          $data['manifest'][$prop][] = $object;
        }
        elseif (array_key_exists($prop, $l10nProps)) {
          $lang = ($xmlLang) ? $xmlLang : 'en-US';
          $data['manifest'][$prop][$lang] = $object;
        }
      }
    }
    else {
      //save it anyway
      $data[$subject][$predicate] = $object;
    }
    
    return $data;
  }
}
?>
