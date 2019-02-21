<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();

$notBlocked = '{3C9A65A6-9563-4485-BA4A-4BCD698BCFB4}';
$blocked = '{34274bf4-1d97-a289-e984-17e546307e4f}';

$query = "SELECT `id`, `blocked` FROM `amo` WHERE `id` = ?s AND `blocked` = 1";
$result = $GLOBALS['moduleDatabase']->query('rows', $query, $notBlocked);

funcGenerateContent('AMO Extension IDs', $result);

// ====================================================================================================================

?>