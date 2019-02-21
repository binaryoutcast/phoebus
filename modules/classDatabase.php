<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classDatabase {
  private $arraySoftwareState;
  private $libSafeMySQL;
  private $connection;
  
  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    // Assign current software state to a class property by reference
    $this->arraySoftwareState = &$GLOBALS['arraySoftwareState'];

    @include_once(ROOT_PATH . DATASTORE_RELPATH . '.phoebus/sql');

    if (!($arrayCreds ?? false)) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - Could not read aql file');
    }

    $arrayCreds['currentDB'] = $arrayCreds['liveDB'];

    if($this->arraySoftwareState['debugMode'] || $this->arraySoftwareState['requestDebugOff']) {
      $arrayCreds['currentDB'] = $arrayCreds['devDB'];;
    }

    $this->connection = mysqli_connect('localhost', $arrayCreds['username'], $arrayCreds['password'], $arrayCreds['currentDB']);
    
    if (mysqli_connect_errno($this->connection)) {
      funcError('SQL Connection Error: ' . mysqli_connect_errno($this->connection));
    }
    
    mysqli_set_charset($this->connection, 'utf8');

    require_once(LIBRARIES['safeMySQL']);
    $this->libSafeMySQL = new SafeMysql(['mysqli' => &$this->connection]);
  }

  /********************************************************************************************************************
  * Class deconstructor that cleans up items
  ********************************************************************************************************************/
  function __destruct() {
    if ($this->connection) {
      $this->libSafeMySQL = null;
      mysqli_close($this->connection);
    }
  }

  /********************************************************************************************************************
  * Performs actions on SQL via SafeMySQL
  *
  * @param    string    row|rows|normal
  * @param    ...       The rest of the arguments. See SafeMySQL
  * @return   array with result or null
  ********************************************************************************************************************/
  public function query($aQueryType, ...$aExtraArgs) {
    $result = null;

    if (!$this->connection) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - An SQL Connection is required');
    }

    switch ($aQueryType) {
      case 'row':
        $result = $this->libSafeMySQL->getRow(...$aExtraArgs);
        break;
      case 'rows':
        $result = $this->libSafeMySQL->getAll(...$aExtraArgs);
        break;
      case 'col':
        $result = $this->libSafeMySQL->getCol(...$aExtraArgs);
        break;
      case 'normal':
        $result = $this->libSafeMySQL->query(...$aExtraArgs);
        break;
      default:
        funcError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    return funcUnifiedVariable('var', $result);
  }
}

?>
