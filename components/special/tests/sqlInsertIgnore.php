<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase        = new classDatabase();

$strHashIP = hash('sha256', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);

$query = "INSERT IGNORE `tap` SET haship=?s";
$moduleDatabase->query('normal', $query, $strHashIP);

$query = "SELECT `haship` from `tap`";
$queryRV = $moduleDatabase->query('col', $query);

funcGenerateContent('SQL Result', $queryRV);

// ====================================================================================================================

?>