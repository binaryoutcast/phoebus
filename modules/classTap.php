<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classTap { 
  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    if (!gfEnsureModule('database')) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }

    if (!$GLOBALS['gaRuntime']['remoteAddr']) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - could not determin the remote addr');
    }
  }

  /********************************************************************************************************************
  * Tap
  ********************************************************************************************************************/
  public function execute() {
    $strHashIP = hash('sha256', $GLOBALS['gaRuntime']['remoteAddr']);
    $query = "INSERT IGNORE `tap` SET haship=?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, $strHashIP);
  }
}

?>
