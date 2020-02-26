<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

class classDatabase {
  public $connection;
  private $gaRuntime;
  private $libSafeMySQL;
  
  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    // Assign current software state to a class property by reference
    $this->gaRuntime = &$GLOBALS['gaRuntime'];

    @include_once(ROOT_PATH . DATASTORE_RELPATH . '.phoebus/sql');

    if (!($arrayCreds ?? false)) {
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - Could not read aql file');
    }

    $arrayCreds['currentDB'] = $arrayCreds['liveDB'];

    if($this->gaRuntime['debugMode'] || $this->gaRuntime['requestDebugOff']) {
      $arrayCreds['currentDB'] = $arrayCreds['devDB'];;
    }

    $this->connection = mysqli_connect('localhost', $arrayCreds['username'], $arrayCreds['password'], $arrayCreds['currentDB']);
    
    if (mysqli_connect_errno($this->connection)) {
      gfError('SQL Connection Error: ' . mysqli_connect_errno($this->connection));
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
      gfError(__CLASS__ . '::' . __FUNCTION__ . ' - An SQL Connection is required');
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
      case 'standard':
        $result = $this->libSafeMySQL->query(...$aExtraArgs);
        break;
      case 'parse':
        $result = $this->libSafeMySQL->parse(...$aExtraArgs);
        break;
      case 'singleRaw':
        $result = mysqli_query($this->connection, $aExtraArgs[0]);
        break;
      case 'multiRaw':
        $result = mysqli_multi_query($this->connection, $aExtraArgs[0]);
        break;
      default:
        gfError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    return gfSuperVar('var', $result);
  }
}

?>
