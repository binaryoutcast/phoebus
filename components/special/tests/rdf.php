<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['mozillaRDF'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleMozillaRDF = new classMozillaRDF();

$strInstallRDF = '<?xml version="1.0"?>

<!-- This Source Code is subject to the terms of the Mozilla Public License
   - version 2.0 (the "License"). You can obtain a copy of the License at
   - http://mozilla.org/MPL/2.0/. -->


<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:em="http://www.mozilla.org/2004/em-rdf#">

  <Description about="urn:mozilla:install-manifest"
               em:id="inspector@mozilla.org"
               em:version="3.0.1"
               em:name="DOM Inspector"
               em:description="DOM Inspector is a tool that can be used to inspect and edit the live DOM of any web document or XUL application."
               em:creator="Binary Outcast"
               em:type="2"
               em:unpack="true">
    <em:targetApplication name="Pale Moon">
      <Description em:id="{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}"
                   em:minVersion="28.0.0a1"
                   em:maxVersion="28.*"/>
    </em:targetApplication>
    <em:targetApplication name="Unified XUL Platform">
      <Description em:id="toolkit@mozilla.org"
                   em:minVersion="1.0"
                   em:maxVersion="4.*"/>
    </em:targetApplication>
  </Description>
</RDF>';

$arrayInstallManifest = $moduleMozillaRDF->parseInstallManifest($strInstallRDF);

funcGenerateContent('RDF Test', $arrayInstallManifest);

// ====================================================================================================================

?>