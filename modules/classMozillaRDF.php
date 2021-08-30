<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classMozillaRDF {
  const EM_NS = 'http://www.mozilla.org/2004/em-rdf#';
  const INSTALL_MANIFEST_RESOURCE = 'urn:mozilla:install-manifest';
  const ANON_PREFIX = '#genid';

  private $rdfParser;

  /********************************************************************************************************************
  * Class constructor that sets initial state of things
  ********************************************************************************************************************/
  function __construct() {
    // Include the Rdf_parser
    require_once(LIBRARIES['rdfParser']);
    $this->rdfParser = new Rdf_parser();
  }

  /********************************************************************************************************************
  * Parses install.rdf using Rdf_parser class
  *
  * @param string     $manifestData
  * @return array     $data["manifest"]
  ********************************************************************************************************************/
  public function parseInstallManifest($manifestData) {
    $data = array();

    $this->rdfParser->rdf_parser_create(null);
    $this->rdfParser->rdf_set_user_data($data);
    $this->rdfParser->rdf_set_statement_handler(array('classMozillaRDF', 'installManifestStatementHandler'));
    $this->rdfParser->rdf_set_base('');

    if (!$this->rdfParser->rdf_parse($manifestData, strlen($manifestData), true)) {
      return xml_error_string(xml_get_error_code($this->rdfParser->rdf_parser['xml_parser']));
    }

    // Set the targetApplication data
    $targetArray = array();
    if (!empty($data['manifest']['targetApplication']) && is_array($data['manifest']['targetApplication'])) {
      foreach ($data['manifest']['targetApplication'] as $targetApp) {
        if (str_starts_with($data[$targetApp][self::EM_NS."id"], self::ANON_PREFIX) ||
            str_starts_with($data[$targetApp][self::EM_NS.'minVersion'], self::ANON_PREFIX) ||
            str_starts_with($data[$targetApp][self::EM_NS.'maxVersion'], self::ANON_PREFIX)) {
          gfError('em:targetApplication description tags/attributes em:id, em:minVersion, and em:maxVersion MUST have a value');
        }

        $id = $data[$targetApp][self::EM_NS."id"];
        $targetArray[$id]['minVersion'] = $data[$targetApp][self::EM_NS.'minVersion'];
        $targetArray[$id]['maxVersion'] = $data[$targetApp][self::EM_NS.'maxVersion'];
      }
    }

    $data['manifest']['targetApplication'] = $targetArray;

    $this->rdfParser->rdf_parser_free();

    return $data['manifest'];
  }


  /********************************************************************************************************************
  * Parses install.rdf for our desired properties
  *
  * @param string     $manifestData
  * @param array      &$data
  * @param string     $subjectType
  * @param string     $subject
  * @param string     $predicate
  * @param int        $ordinal
  * @param string     $objectType
  * @param string     $object
  * @param string     $xmlLang
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
    $singleProps = ['id', 'type', 'version', 'creator', 'homepageURL', 'updateURL', 'updateKey', 'bootstrap',
                    'hasEmbeddedWebExtension', 'multiprocessCompatible', 'skinnable', 'strictCompatibility',
                    'license', 'iconURL', 'icon64URL'];

    //multiple properties - ignoring: File
    $multiProps = ['contributor', 'developer', 'translator', 'targetApplication'];
    
    //localizable properties   
    $localeProps = ['name', 'description'];

    //Look for properties on the install manifest itself
    if ($subject == self::INSTALL_MANIFEST_RESOURCE) {
      //we're only really interested in EM properties
      $length = strlen(self::EM_NS);
      if (strncmp($predicate, self::EM_NS, $length) == 0) {
        $prop = substr($predicate, $length, strlen($predicate)-$length);

        if (in_array($prop, $singleProps) &&
            !str_starts_with($object, self::ANON_PREFIX) &&
            $object != 'false') {
          $data['manifest'][$prop] = $object;
        }
        elseif (in_array($prop, $multiProps)) {
          $data['manifest'][$prop][] = $object;
        }
        elseif (in_array($prop, $localeProps)) {
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
