<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database', 'mozillaRDF', 'readManifest', 'writeManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleMozillaRDF       = new classMozillaRDF();
$moduleReadManifest    = new classReadManifest();
$moduleWriteManifest    = new classWriteManifest();

//$arrayInstallManifest = $moduleMozillaRDF->parseInstallManifest($strInstallRDF);

$boolHasPostData = !empty($_POST);

if ($boolHasPostData) {
  $result = $moduleWriteManifest->publicValidator();
  gfGenContent('Validator Result', $result);
}

$content = '<form method="POST" accept-charset="UTF-8" autocomplete="off" enctype="multipart/form-data">' .
           '<input type="file" name="xpiUpload" />' .
           '<input type="hidden" name="slug" value="1" />' .
           '<input type="submit" value="Upload" />' .
           '</form>';

gfGenContent('Validator Test', $content);

// ====================================================================================================================

?>