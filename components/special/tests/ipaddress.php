<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules


// Instantiate modules
$strIPAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$result = array(
  $strIPAddress,
  hash('sha256', $strIPAddress),
  $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
  $_SERVER['REMOTE_ADDR'] ?? null
);

gfGenContent('IP Address', $result);

// ====================================================================================================================

?>
