<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classLog { 
  /********************************************************************************************************************
  * Class constructor that sets initial state of things
  ********************************************************************************************************************/
  function __construct() {  
    gfEnsureModules(__CLASS__, 'database');
  }

  /********************************************************************************************************************
  * Add a log record to the log table in the database
  ********************************************************************************************************************/
  public function record($aAction, $aReturnData = null) {
    global $gaRuntime;
    global $gmDatabase;

    $data = array(
      'epoch'     => time(),
      'username'  => $gaRuntime['authentication']['username'] ?? 'anonymous',
      'ip'        => $gaRuntime['remoteAddr'],
      'action'    => $aAction,
    );

    $query = "INSERT ?n SET ?u";
    $gmDatabase->query('normal', $query, 'log', $data);

    if ($aReturnData) {
      return $data;
    }
  }

  /********************************************************************************************************************
  * Get logs
  ********************************************************************************************************************/
  public function fetch($aGetAllLogs = null) {
    global $gmDatabase;

    $query = "SELECT * FROM ?n ORDER BY ?n";

    if (!$aGetAllLogs) {
      $query .= " DESC LIMIT 250";
    }

    $result = $gmDatabase->query('rows', $query, 'log', 'eventID');

    if ($result) {
      foreach ($result as $_key => $_value) {
        $result[$_key]['date'] = date('Y-m-d @ H:i:s', $result[$_key]['epoch']);
      }
    }

    return $result;
  }
}

?>
