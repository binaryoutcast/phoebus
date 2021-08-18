<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | INFO | ========================================================================================================

/* Phoebus User Levels
  Level 1 - Add-on Developer
  Level 2 - Advanced/Legacy Add-on Developer
  Level 3 - Add-ons Team Member
  Level 4 - Add-ons Team Leader
  Level 5 - Phoebus Administrator
*/

// ====================================================================================================================

// == | classAuthentication | =========================================================================================

class classAccount {
  public $validationEmail;
  private $postData;
  private $banned;

  /********************************************************************************************************************
  * Class constructor that sets initial state of things
  ********************************************************************************************************************/
  function __construct() {
    gfEnsureModules(__CLASS__, 'database');

    $this->postData = array(
      'username'      => gfSuperVar('post', 'username'),
      'password'      => gfSuperVar('post', 'password'),
      'active'        => (bool)gfSuperVar('post', 'active'),
      'level'         => (int)gfSuperVar('post', 'level'),
      'displayName'   => gfSuperVar('post', 'displayName'),
      'email'         => gfSuperVar('post', 'email'),
      'verification'  => gfSuperVar('post', 'verification'),
    );

    $this->banned = array(
      'srazzano'
    );
  }

  /********************************************************************************************************************
  * Register an account
  ********************************************************************************************************************/
  public function registerUser() {   
    global $gmDatabase;

    $regex = '/[^a-z0-9_\-]/';

    $this->postData['active'] = false;
    $this->postData['level'] = 1;
    unset($this->postData['verification']);

    $username = preg_replace($regex, '', $this->postData['username']);
    if (!$this->postData['username'] ||
        strlen($this->postData['username']) < 3 ||
        strlen($this->postData['username']) > 32 ||
        $this->postData['username'] !== $username) {
      gfError('You did not specify a valid username.</li>' .
                '<li>Usernames must be 3+ chars not exceeding 32 chars.</li>' .
                '<li>Please use only lower case letters, numbers, and/or underscore (_) or dash (-)');
    }

    if (!$this->postData['password'] ||
        strlen($this->postData['password']) < 8 ||
        strlen($this->postData['password']) > 64 ) {
      gfError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
    }

    $this->postData['password'] = password_hash($this->postData['password'], PASSWORD_BCRYPT);

    if (!$this->postData['email']) {
      gfError('You did not specify a valid email address. You will not be able to activate your account without one');
    }

    if (!$this->postData['displayName']) {
      gfError('You did not specify a display name.');
    }

    $query = "SELECT `username`, `email` FROM `user` WHERE `username` = ?s OR `email` = ?s";
    (bool)$isUsernameOrEmailExisting = $gmDatabase->query('rows', $query,
                                                          $this->postData['username'],
                                                          $this->postData['email']);

    $isEmailBlacklisted = $this->checkEmailAgainstBlacklist($this->postData['email']);
    if ($isUsernameOrEmailExisting || $isEmailBlacklisted) {
      gfError('Your username or e-mail address is not available. Please select another.</li>' .
                '<li>You may only have one account per valid e-mail address.');
    }

    foreach ($this->banned as $_value) {
      if (str_contains($this->postData['username'], $_value) || str_contains($this->postData['email'], $_value)) {
        gfError('Yourself or someone like you has been permanently banned from using this software and service.</li><li>' . 
                  'If this automatic determination is in error please contact the Add-ons Team or a Phoebus Administrator.</li><li>' .
                  'Have a nice day!');
      }
    }

    $code = gfHexString();

    $extraData = array(
      'regEpoch' => time(),
      'verification' => $code
    );

    $this->postData['extraData'] = json_encode($extraData, 320);
    $this->postData['addons'] = '[]';

    $query = "INSERT INTO `user` SET ?u";
    $boolQueryRV = $gmDatabase->query('normal', $query, $this->postData);

    if (!$boolQueryRV) {
      return null;
    }

    $boolSendMailRV = $this->sendVerificationEmail($this->postData['email'], $extraData['verification']);

    if (!$boolSendMailRV) {
      return null;
    }

    $this->validationEmail = $this->postData['email'];
    return true;
  }

  /********************************************************************************************************************
  * Verify an account
  ********************************************************************************************************************/
  public function verifyUser() {
    global $gmDatabase;

    if (!$this->postData['username'] || !$this->postData['verification']) {
      gfError('You must provide a username and verification code!');
    }

    $userManifest = $this->getSingleUser($this->postData['username'], true);

    if (!$userManifest) {
      gfError('You must provide a valid registered username');
    }

    if (!$userManifest['extraData']['verification']) {
      gfError('This account has already been verified.');
    }

    if ($userManifest['extraData']['verification'] != $this->postData['verification']) {
      gfError('The verification code is incorrect. Please try again!');
    }

    $userManifest['extraData']['verification'] = null;

    unset($userManifest['extraData']['regDate']);

    $insertManifest = array(
      'active' => true,
      'extraData' => json_encode($userManifest['extraData'], 320)
    );

    $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
    $boolQueryRV = $gmDatabase->query('normal', $query, $insertManifest, $userManifest['username']);

    if (!$boolQueryRV) {
      return null;
    }

    return true;
  }

  /********************************************************************************************************************
  * Update a user manifest
  ********************************************************************************************************************/
  public function updateUserManifest($aUserManifest) {
    global $gaRuntime;
    global $gmDatabase;
    unset($aUserManifest['addons']);

    if (!$this->postData['username']) {
      gfError('Username was not found in POST');
    }

    if ($this->postData['username'] != $aUserManifest['username']) {
      gfError('POST Slug does not match GET/Manifest Slug');
    }

    if (!in_array($this->postData['level'], [1, 2, 3, 4, 5])) {
      $this->postData['level'] = $aUserManifest['level'];
    }

    // Hackers are a superstitious cowardly lot
    if ($gaRuntime['authentication']['level'] < 3) {
      unset($this->postData['active']);
      unset($this->postData['level']);
      unset($this->postData['username']);

      // User code path for dealing with email addresses
      if ($aUserManifest['email'] != $this->postData['email']) {
        (bool)$isExistingEmail = $gmDatabase->query('rows',
                                                    "SELECT `username`, `email` FROM `user` WHERE `email` = ?s",
                                                    $this->postData['email']);
        $isEmailBlacklisted = $this->checkEmailAgainstBlacklist($this->postData['email']);
        if ($isExistingEmail || $isEmailBlacklisted) {
          gfError('Your email address is not available. Please select another.');
        }

        $this->postData['extraData'] = $aUserManifest['extraData'];
        unset($this->postData['extraData']['regDate']);

        $code = gfHexString();
        $this->postData['extraData']['verification'] = $code;

        $this->sendVerificationEmail($this->postData['email'], $code);
        $this->postData['extraData'] = json_encode($this->postData['extraData'], 320);
      }
    }
    else {
      if ($gaRuntime['authentication']['level'] != 5 &&
          $this->postData['level'] >= $gaRuntime['authentication']['level']) {
        switch ($gaRuntime['authentication']['username']) {
          case $this->postData['username']:
            if ($this->postData['level'] <= $gaRuntime['authentication']['level']) {
              break;
            }
          default:
            gfError('Seriously, did you think manipulating user levels was going to work? I\'m disappointed!');
        }
      }

      if ($aUserManifest['extraData']['verification']) {
        $this->postData['active'] = true;
        $this->postData['extraData'] = $aUserManifest['extraData'];
        $this->postData['extraData']['verification'] = null;
        unset($this->postData['extraData']['regDate']);
        $this->postData['extraData'] = json_encode($this->postData['extraData'], 320);
      }
    }

    if ($this->postData['password']) {
      if (strlen($this->postData['password']) < 8 || strlen($this->postData['password']) > 64 ) {
        gfError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
      }
      $this->postData['password'] = password_hash($this->postData['password'], PASSWORD_BCRYPT);
    }
    else {
      unset($this->postData['password']);
    }

    if (empty($this->postData)) {
      return true;
    }

    unset($this->postData['verification']);
    unset($this->postData['username']);

    // Insert the new manifest data into the database
    $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
    $gmDatabase->query('normal', $query, $this->postData, $aUserManifest['username']);

    return true;
  }

  /********************************************************************************************************************
  * Gets all users at or below the requesting user level
  ********************************************************************************************************************/
  public function getUsers() {
    global $gaRuntime;
    global $gmDatabase;

    if ($gaRuntime['authentication']['level'] < 3) {
      gfError('I have no idea how you managed to get here but seriously you need to piss off...');
    }

    $query = "SELECT * FROM `user` WHERE ?i = 5 OR `level` < ?i OR `username` = ?s";
    $allUsers = $gmDatabase->query('rows',
                                   $query,
                                   $gaRuntime['authentication']['level'],
                                   $gaRuntime['authentication']['level'],
                                   $gaRuntime['authentication']['username']);

    foreach ($allUsers as $_key => $_value) {
      unset($allUsers[$_key]['password']);
      $allUsers[$_key]['active'] = (bool)$_value['active'];
      $allUsers[$_key]['level'] = (int)$_value['level'];
      $allUsers[$_key]['extraData'] = json_decode($_value['extraData'], true);
      $allUsers[$_key]['extraData']['regDate'] = date('F j, Y', $allUsers[$_key]['extraData']['regEpoch']);
      $allUsers[$_key]['addons'] = json_decode($_value['addons']);
    }

    return $allUsers;
  }

  /********************************************************************************************************************
  * Gets a single user manifest
  ********************************************************************************************************************/
  public function getSingleUser($aUserName, $aRemovePassword = null) {
    global $gmDatabase;

    $query = "SELECT * FROM `user` WHERE `username` = ?s";
    $userManifest = $gmDatabase->query('row', $query, $aUserName);

    if (!$userManifest) {
      return null;
    }

    $userManifest['active'] = (bool)$userManifest['active'];
    $userManifest['level'] = (int)$userManifest['level'];
    $userManifest['extraData'] = json_decode($userManifest['extraData'], true);
    $userManifest['extraData']['regDate'] = date('F j, Y', $userManifest['extraData']['regEpoch']);
    $userManifest['addons'] = json_decode($userManifest['addons']);

    if ($aRemovePassword) {
      unset($userManifest['password']);
    }
    return $userManifest;
  }

  /********************************************************************************************************************
  * Assigns an Add-on slug to a user
  ********************************************************************************************************************/
  public function assignAddonToUser($aUsername, $aSlug) {
    global $gmDatabase;

    $query = "SELECT `username`, `addons` FROM `user` WHERE `username` = ?s";
    $userManifest = $gmDatabase->query('row', $query, $aUsername);

    if (!$userManifest) {
      return null;
    }

    $userAddons = json_decode($userManifest['addons']);

    if (!in_array($aSlug, $userAddons)) {
      $userAddons[] = $aSlug;
      $userAddons = json_encode($userAddons, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

      // Insert the new manifest data into the database
      $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
      $gmDatabase->query('normal', $query, array('addons' => $userAddons), $userManifest['username']);
    }

    return true;
  }

  /********************************************************************************************************************
  * Removes an Add-on slug from a user
  ********************************************************************************************************************/
  public function removeAddonFromUser($aUsername, $aSlug) {
    global $gmDatabase;

    $query = "SELECT `username`, `addons` FROM `user` WHERE `username` = ?s";
    $userManifest = $gmDatabase->query('row', $query, $aUsername);

    if (!$userManifest) {
      return null;
    }

    $userAddons = json_decode($userManifest['addons']);

    if (!in_array($aSlug, $userAddons)) {
      return null;
    }

    unset($userAddons[array_search($aSlug, $userAddons)]);

    $userAddons = json_encode(array_values($userAddons), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Insert the updated manifest data into the database
    $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
    $rv = $gmDatabase->query('normal', $query, array('addons' => $userAddons), $userManifest['username']);

    return true;
  }

  /********************************************************************************************************************
  * Find a user by Add-on Slug
  ********************************************************************************************************************/
  public function findUserAddon($aSlug) {
    global $gmDatabase;

    $query = "SELECT `username` FROM `user` WHERE `addons` LIKE '%\"{$aSlug}\"%'";
    $usernames = $gmDatabase->query('col', $query);

    if (!$usernames) {
      return null;
    }

    if (count($usernames) > 1) {
      gfError('Unable to determine a single user for slug ' . $aSlug);
    }

    return $usernames[0];
  }

  /********************************************************************************************************************
  * Performs authentication
  ********************************************************************************************************************/
  public function authenticate($aLogout = null) {
    global $gaRuntime;
    global $gmDatabase;

    // Get Username and Password from HTTP Basic Authentication 
    $strUsername = gfSuperVar('server', 'PHP_AUTH_USER');
    $strPassword = gfSuperVar('server', 'PHP_AUTH_PW');

    // Check for the existance of username and password as well as the special 'logout' user
    if (!$strUsername || $strUsername == 'logout' || !$strPassword ) {
      gfBasicAuthPrompt();
    }

    // This will handle a logout situation using a dirty javascript trick
    // It will not work without javascript or on IE but then again neither will the PANEL
    if ($aLogout) {
      $url = 'https://logout:logout@' . $gaRuntime['currentDomain'] . '/panel/logout/';
      gfHeader('html');
      die(
        '<html><head><script>' .
        'var xmlHttp = new XMLHttpRequest();' .
        'xmlHttp.open( "GET", "' . $url . '", false );' .
        'xmlHttp.send( null );' .
        'window.location = "/panel/";' .
        '</script></head><body>' .
        '<p>Logging out...</p>' .
        '<p>If you are not redirected you also are not logged out. Enable JavaScript or stop using IE/Edge!<br>' .
        'Additionally, you can just close the browser or clear private data.</p>' .
        '</body></html>'
      );
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Query SQL for a user row
    $userManifest = $this->getSingleUser($strUsername);

    // If nothing from SQL or the user isn't active or the password doesn't match
    // then reprompt until the user cancels
    if (!$userManifest || !password_verify($strPassword, $userManifest['password'])) {
      $userManifest = null;
      gfBasicAuthPrompt();
    }

    // Not validated then send to validation page
    if ($userManifest['extraData']['verification']) {
      gfRedirect(URI_VERIFY);
    }

    // Deal with inactive users.. If inactive prompt forever
    if (!$userManifest['active']) {
      $userManifest = null;
      gfBasicAuthPrompt();
    }

    // Levels 1 and 2 need to add their email and displayName so force them
    if ($userManifest['level'] < 3 && $gaRuntime['qPath'] != '/panel/account/') {
      if (!$userManifest['email'] || !$userManifest['displayName']) {
        gfRedirect('/panel/account/');
      }
    }

    // We don't need the password anymore at this point so kill it
    unset($userManifest['password']);

    // ----------------------------------------------------------------------------------------------------------------

    // Assign the userManifest to the softwareState
    $gaRuntime['authentication'] = $userManifest;

    return true;
  }

  /********************************************************************************************************************
  * Send a verification email
  ********************************************************************************************************************/
  private function sendVerificationEmail($aEmail, $aValidationCode) {
    global $gaRuntime;

    if (!gfSuperVar('var', $aEmail)) {
      gfError('Unable to send verification email because it is null');
    }

    ini_set("sendmail_from", "addons@palemoon.org");

    $arraySendMailHeaders = array(
      'From' => 'Phoebus Account Registration <addons@palemoon.org>',
      'Reply-To' => 'addons@palemoon.org',
      'X-Mailer' => SOFTWARE_NAME . '/' . SOFTWARE_VERSION,
    );

    $strSubDomain = $gaRuntime['debugMode'] ? 'addons-dev.' : 'addons.';

    $strSendMailBody = 'Your verification code is: ' . $aValidationCode . NEW_LINE . NEW_LINE .
                       'You can verify and/or activate your account by navigating to https://' .
                       $strSubDomain . 'palemoon.org/panel/verification/';

    $sendmail = mail($this->postData['email'],
                     'Phoebus Account Activation Verification',
                     $strSendMailBody,
                     $arraySendMailHeaders);
    return $sendmail;
  }

  /********************************************************************************************************************
  * Check email address
  ********************************************************************************************************************/
  private function checkEmailAgainstBlacklist($aEmailAddress) {
    // E-mail blacklist $emailBlacklistDB
    require_once(DATABASES['emailBlacklist']);
    
    // Check passed email address against blacklist
    foreach($emailBlacklistDB as $_value) {
      if (fnmatch($_value, $aEmailAddress)) {
        return true;
      }
    }

    return null;
  }

}
// ====================================================================================================================

?>