<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database', 'readManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase        = new classDatabase();
$moduleReadManifest    = new classReadManifest();

$query = "SELECT `id`, `slug`, `releaseXPI`, NULL as `aid`, NULL as `version`
          FROM addon WHERE `id` = 'inspector@mozilla.org'
          UNION
          SELECT NULL, NULL, NULL, `aid`, `version`
          FROM version WHERE `id` = 'inspector@mozilla.org'";

$addonManifest = $moduleDatabase->query('rows', $query);

funcGenerateContent('SQL Version ProtoQuery', $addonManifest);

// ====================================================================================================================

?>