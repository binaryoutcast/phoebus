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
  private $postData;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database module is required to be included in the global scope');
    }

    $this->postData = array(
      'username'      => funcUnifiedVariable('post', 'username'),
      'password'      => funcUnifiedVariable('post', 'password'),
      'active'        => (bool)funcUnifiedVariable('post', 'active'),
      'level'         => (int)funcUnifiedVariable('post', 'level'),
      'displayName'   => funcUnifiedVariable('post', 'displayName'),
      'email'         => funcUnifiedVariable('post', 'email'),
      'verification'  => funcUnifiedVariable('post', 'verification'),
    );
  }

  /********************************************************************************************************************
  * Register an account
  ********************************************************************************************************************/
  public function registerUser() {
    $regex = '/[^a-z0-9_\-]/';

    $this->postData['active'] = false;
    $this->postData['level'] = 1;
    unset($this->postData['verification']);

    $username = preg_replace($regex, '', $this->postData['username']);
    if (!$this->postData['username'] ||
        strlen($this->postData['username']) < 3 ||
        strlen($this->postData['username']) > 32 ||
        $this->postData['username'] !== $username) {
      funcError('You did not specify a valid username.</li>' .
                '<li>Usernames must be 3+ chars not exceeding 32 chars.</li>' .
                '<li>Please use only lower case letters, numbers, and/or underscore (_) or dash (-)');
    }

    if (!$this->postData['password'] ||
        strlen($this->postData['password']) < 8 ||
        strlen($this->postData['password']) > 64 ) {
      funcError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
    }

    $this->postData['password'] = password_hash($this->postData['password'], PASSWORD_BCRYPT);

    if (!$this->postData['email']) {
      funcError('You did not specify a valid email address. You will not be able to activate your account without one');
    }

    if (!$this->postData['displayName']) {
      funcError('You did not specify a display name.');
    }

    $query = "SELECT `username`, `email` FROM `user` WHERE `username` = ?s OR `email` = ?s";
    (bool)$isUsernameOrEmailExisting = $GLOBALS['moduleDatabase']->query('rows',
                                                                         $query,
                                                                         $this->postData['username'],
                                                                         $this->postData['email']);

    $isEmailBlacklisted = $this->checkEmailAgainstBlacklist($this->postData['email']);
    if ($isUsernameOrEmailExisting || $isEmailBlacklisted) {
      funcError('Your username or e-mail address is not available. Please select another.</li>' .
                '<li>You may only have one account per valid e-mail address.');
    }

    $code = $this->generateCode($this->postData['username'], $this->postData['email']);

    $extraData = array(
      'regEpoch' => time(),
      'verification' => $code
    );

    $this->postData['extraData'] = json_encode($extraData, 320);
    $this->postData['addons'] = '[]';

    $query = "INSERT INTO `user` SET ?u";
    $boolQueryRV = $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData);

    if (!$boolQueryRV) {
      return null;
    }

    $boolSendMailRV = $this->sendVerificationEmail($this->postData['email'], $extraData['verification']);

    if (!$boolSendMailRV) {
      return null;
    }

    return true;
  }

  /********************************************************************************************************************
  * Verify an account
  ********************************************************************************************************************/
  public function verifyUser() {
    if (!$this->postData['username'] || !$this->postData['verification']) {
      funcError('You must provide a username and verification code!');
    }

    $userManifest = $this->getSingleUser($this->postData['username'], true);

    if (!$userManifest) {
      funcError('You must provide a valid registered username');
    }

    if (!$userManifest['extraData']['verification']) {
      funcError('This account has already been verified.');
    }

    if ($userManifest['extraData']['verification'] != $this->postData['verification']) {
      funcError('The verification code is incorrect. Please try again!');
    }

    $userManifest['extraData']['verification'] = null;

    unset($userManifest['extraData']['regDate']);

    $insertManifest = array(
      'active' => true,
      'extraData' => json_encode($userManifest['extraData'], 320)
    );

    $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
    $boolQueryRV = $GLOBALS['moduleDatabase']->query('normal', $query, $insertManifest, $userManifest['username']);

    if (!$boolQueryRV) {
      return null;
    }

    return true;
  }

  /********************************************************************************************************************
  * Update a user manifest
  ********************************************************************************************************************/
  public function updateUserManifest($aUserManifest) {
    unset($aUserManifest['addons']);

    if (!$this->postData['username']) {
      funcError('Username was not found in POST');
    }

    if ($this->postData['username'] != $aUserManifest['username']) {
      funcError('POST Slug does not match GET/Manifest Slug');
    }

    if (!in_array($this->postData['level'], [1, 2, 3, 4, 5])) {
      $this->postData['level'] = $aUserManifest['level'];
    }

    // Hackers are a superstitious cowardly lot
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      unset($this->postData['active']);
      unset($this->postData['level']);
      unset($this->postData['username']);

      // User code path for dealing with email addresses
      if ($aUserManifest['email'] != $this->postData['email']) {
        (bool)$isExistingEmail = $GLOBALS['moduleDatabase']->query('rows',
                                                                   "SELECT `username`, `email`
                                                                    FROM `user`
                                                                    WHERE `email` = ?s",
                                                                    $this->postData['email']);
        $isEmailBlacklisted = $this->checkEmailAgainstBlacklist($this->postData['email']);
        if ($isExistingEmail || $isEmailBlacklisted) {
          funcError('Your email address is not available. Please select another.');
        }

        $this->postData['extraData'] = $aUserManifest['extraData'];
        unset($this->postData['extraData']['regDate']);

        $code = $this->generateCode($aUserManifest['username'], $this->postData['email']);
        $this->postData['extraData']['verification'] = $code;

        $this->sendVerificationEmail($this->postData['email'], $code);
        $this->postData['extraData'] = json_encode($this->postData['extraData'], 320);
      }
    }
    else {
      if ($GLOBALS['arraySoftwareState']['authentication']['level'] != 5 &&
          $this->postData['level'] >= $GLOBALS['arraySoftwareState']['authentication']['level']) {
        switch ($GLOBALS['arraySoftwareState']['authentication']['username']) {
          case $this->postData['username']:
            if ($this->postData['level'] <= $GLOBALS['arraySoftwareState']['authentication']['level']) {
              break;
            }
          default:
            funcError('Seriously, did you think manipulating user levels was going to work? I\'m disappointed!');
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
        funcError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
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
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData, $aUserManifest['username']);

    return true;
  }

  /********************************************************************************************************************
  * Gets all users at or below the requesting user level
  ********************************************************************************************************************/
  public function getUsers() {
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      funcError('I have no idea how you managed to get here but seriously you need to piss off...');
    }

    $query = "SELECT * FROM `user` WHERE ?i = 5 OR `level` < ?i OR `username` = ?s";
    $allUsers = $GLOBALS['moduleDatabase']->query('rows',
                                                  $query,
                                                  $GLOBALS['arraySoftwareState']['authentication']['level'],
                                                  $GLOBALS['arraySoftwareState']['authentication']['level'],
                                                  $GLOBALS['arraySoftwareState']['authentication']['username']);

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
    $query = "SELECT * FROM `user` WHERE `username` = ?s";
    $userManifest = $GLOBALS['moduleDatabase']->query('row', $query, $aUserName);

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
  * Gets all users at or below the requesting user level
  ********************************************************************************************************************/
  public function assignAddonToUser($aUsername, $aSlug) {
    $query = "SELECT `username`, `addons` FROM `user` WHERE `username` = ?s";
    $userManifest = $GLOBALS['moduleDatabase']->query('row', $query, $aUsername);

    if (!$userManifest) {
      return null;
    }

    $userAddons = json_decode($userManifest['addons']);

    if (!in_array($aSlug, $userAddons)) {
      $userAddons[] = $aSlug;
      $userAddons = json_encode($userAddons, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

      // Insert the new manifest data into the database
      $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
      $GLOBALS['moduleDatabase']->query('normal', $query, array('addons' => $userAddons), $userManifest['username']);
    }

    return true;
  }

  /********************************************************************************************************************
  * Performs authentication
  ********************************************************************************************************************/
  public function authenticate($aLogout = null) {
    // Get Username and Password from HTTP Basic Authentication 
    $strUsername = funcUnifiedVariable('server', 'PHP_AUTH_USER');
    $strPassword = funcUnifiedVariable('server', 'PHP_AUTH_PW');

    // Check for the existance of username and password as well as the special 'logout' user
    if (!$strUsername || $strUsername == 'logout' || !$strPassword ) {
      $this->promptCredentials();
    }
    // This will handle a logout situation using a dirty javascript trick
    // It will not work without javascript or on IE but then again neither will the PANEL
    if ($aLogout) {
      $url = 'https://logout:logout@' . $GLOBALS['arraySoftwareState']['currentDomain'] . '/panel/logout/';
      funcSendHeader('html');
      die(
        '<html><head><script>' .
        'var xmlHttp = new XMLHttpRequest();' .
        'xmlHttp.open( "GET", "' . $url . '", false );' .
        'xmlHttp.send( null );' .
        'window.location = "/panel/";' .
        '</script></head><body>' .
        '<p>Logging out...</p>' .
        '<p>If you are not redirected you also are not logged out. Enable Javascript or stop using IE/Edge!<br>' .
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
      $this->promptCredentials();
    }

    // Not validated then send to validation page
    if ($userManifest['extraData']['verification']) {
      funcRedirect(URI_VERIFY);
    }

    // Deal with inactive users.. If inactive prompt forever
    if (!$userManifest['active']) {
      $userManifest = null;
      $this->promptCredentials();
    }

    // Levels 1 and 2 need to add their email and displayName so force them
    if ($userManifest['level'] < 3 && $GLOBALS['arraySoftwareState']['requestPath'] != '/panel/account/') {
      if (!$userManifest['email'] || !$userManifest['displayName']) {
        funcRedirect('/panel/account/');
      }
    }

    // We don't need the password anymore at this point so kill it
    unset($userManifest['password']);

    // ----------------------------------------------------------------------------------------------------------------

    // Assign the userManifest to the softwareState
    $GLOBALS['arraySoftwareState']['authentication'] = $userManifest;

    return true;
  }

  /********************************************************************************************************************
  * Prompts for credentals or shows 401
  ********************************************************************************************************************/
  private function promptCredentials() {
    header('WWW-Authenticate: Basic realm="' . SOFTWARE_NAME . '"');
    header('HTTP/1.0 401 Unauthorized');   
    funcError('You need to enter a valid username and password.');
    exit();
  }

  /********************************************************************************************************************
  * Generates a verification code
  ********************************************************************************************************************/
  private function generateCode($aUsername, $aEmail) {
    $secretFile = ROOT_PATH . DATASTORE_RELPATH . '.phoebus/code';
    $secret = funcUnifiedVariable('var', @file_get_contents(secretFile)) ?? time();
    $code = hash('sha256', time() . $aUsername . $aEmail . $secret);
    return $code;
  }

  /********************************************************************************************************************
  * Send a verification email
  ********************************************************************************************************************/
  private function sendVerificationEmail($aEmail, $aValidationCode) {
    if (!funcUnifiedVariable('var', $aEmail)) {
      funcError('Unable to send verification email because it is null');
    }

    ini_set("sendmail_from", "phoebus@addons.palemoon.org");

    $arraySendMailHeaders = array(
      'From' => 'Phoebus Account Registration <phoebus@addons.palemoon.org>',
      'Reply-To' => 'phoebus@addons.palemoon.org',
      'X-Mailer' => SOFTWARE_NAME . '/' . SOFTWARE_VERSION,
    );

    $strSubDomain = $GLOBALS['arraySoftwareState']['debugMode'] ? 'addons-dev.' : 'addons.';

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
    // E-mail blacklist
    $arrayEmailBlacklist = array(
      '*.*.*.*@*.*',
      '*@*.*.*.*',
      '*@*.33m.co',
      '*@*.33mail.com',
      '*@*.anonbox.net',
      '*@*.dropmail.me',
      '*@*.dynu.com',
      '*@*.e4ward.com',
      '*@*.mailexpire.com',
      '*@*.otherinbox.com',
      '*@*minutemail.com',
      '*@0-mail.com',
      '*@0815.ru',
      '*@0clickemail.com',
      '*@0sg.net',
      '*@0wnd.net',
      '*@0wnd.org',
      '*@10minutemail.com',
      '*@1shivom.com',
      '*@20mail.it',
      '*@20minutemail.com',
      '*@2prong.com',
      '*@33mail.com',
      '*@3d-painting.com',
      '*@4warding.com',
      '*@4warding.net',
      '*@4warding.org',
      '*@60minutemail.com',
      '*@675hosting.com',
      '*@675hosting.net',
      '*@675hosting.org',
      '*@6url.com',
      '*@75hosting.com',
      '*@75hosting.net',
      '*@75hosting.org',
      '*@7tags.com',
      '*@99pubblicita.com',
      '*@9ox.net',
      '*@a-bc.net',
      '*@afrobacon.com',
      '*@ajaxapp.net',
      '*@alivance.com',
      '*@amilegit.com',
      '*@amiri.net',
      '*@amiriindustries.com',
      '*@anonbox.net',
      '*@anonmails.de',
      '*@anonymbox.com',
      '*@antichef.com',
      '*@antichef.net',
      '*@antispam.de',
      '*@aol.com',
      '*@armyspy.com',
      '*@awsoo.com',
      '*@baxomale.ht.cx',
      '*@beefmilk.com',
      '*@bigprofessor.so',
      '*@binkmail.com',
      '*@bio-muesli.net',
      '*@bit-degree.com',
      '*@bobmail.info',
      '*@bodhi.lawlita.com',
      '*@bofthew.com',
      '*@brefmail.com',
      '*@broadbandninja.com',
      '*@bsnow.net',
      '*@bugmenot.com',
      '*@bumpymail.com',
      '*@byom.de',
      '*@casualdx.com',
      '*@centermail.com',
      '*@centermail.net',
      '*@chammy.info',
      '*@chogmail.com',
      '*@choicemail1.com',
      '*@cock.li',
      '*@cool.fr.nf',
      '*@correo.blogos.net',
      '*@cosmorph.com',
      '*@courriel.fr.nf',
      '*@courrieltemporaire.com',
      '*@creazionisa.com',
      '*@crymail2.com',
      '*@cubiclink.com',
      '*@curryworld.de',
      '*@cust.in',
      '*@cuvox.de',
      '*@dacoolest.com',
      '*@dandikmail.com',
      '*@danet.in',
      '*@darkstone.com',
      '*@dayrep.com',
      '*@deadaddress.com',
      '*@deadspam.com',
      '*@despam.it',
      '*@despammed.com',
      '*@devnullmail.com',
      '*@dfgh.net',
      '*@digitalsanctuary.com',
      '*@discardmail.com',
      '*@discardmail.de',
      '*@disposableaddress.com',
      '*@disposeamail.com',
      '*@disposemail.com',
      '*@dispostable.com',
      '*@dm.w3internet.co.uk',
      '*@docsis.ru',
      '*@dodgeit.com',
      '*@dodgit.com',
      '*@dodgit.org',
      '*@donemail.ru',
      '*@dontreg.com',
      '*@dontsendmespam.de',
      '*@drdrb.com',
      '*@dropmail.me',
      '*@dumoac.net',
      '*@dump-email.info',
      '*@dumpandjunk.com',
      '*@dumpmail.de',
      '*@dumpyemail.com',
      '*@e4ward.com',
      '*@einrot.com',
      '*@email60.com',
      '*@emaildienst.de',
      '*@emailgo.de',
      '*@emailias.com',
      '*@emailigo.de',
      '*@emailinfive.com',
      '*@emailmiser.com',
      '*@emailna.co',
      '*@emailo.pro',
      '*@emailsensei.com',
      '*@emailtemporario.com.br',
      '*@emailto.de',
      '*@emailwarden.com',
      '*@emailx.at.hm',
      '*@emailxfer.com',
      '*@emz.net',
      '*@enterto.com',
      '*@ephemail.net',
      '*@etranquil.com',
      '*@etranquil.net',
      '*@etranquil.org',
      '*@explodemail.com',
      '*@eyepaste.com',
      '*@fake-box.com',
      '*@fakeinbox.com',
      '*@fakeinformation.com',
      '*@fastacura.com',
      '*@fastchevy.com',
      '*@fastchrysler.com',
      '*@fastkawasaki.com',
      '*@fastmazda.com',
      '*@fastmitsubishi.com',
      '*@fastnissan.com',
      '*@fastsubaru.com',
      '*@fastsuzuki.com',
      '*@fasttoyota.com',
      '*@fastyamaha.com',
      '*@fidelium10.com',
      '*@filzmail.com',
      '*@fizmail.com',
      '*@fleckens.hu',
      '*@fr33mail.info',
      '*@frapmail.com',
      '*@front14.org',
      '*@fux0ringduh.com',
      '*@fxprix.com',
      '*@garliclife.com',
      '*@get1mail.com',
      '*@get2mail.fr',
      '*@getairmail.com',
      '*@getonemail.com',
      '*@getonemail.net',
      '*@ghosttexter.de',
      '*@girlsundertheinfluence.com',
      '*@gishpuppy.com',
      '*@gowikibooks.com',
      '*@gowikicampus.com',
      '*@gowikicars.com',
      '*@gowikifilms.com',
      '*@gowikigames.com',
      '*@gowikimusic.com',
      '*@gowikinetwork.com',
      '*@gowikitravel.com',
      '*@gowikitv.com',
      '*@grandmamail.com',
      '*@great-host.in',
      '*@greensloth.com',
      '*@grr.la',
      '*@gsrv.co.uk',
      '*@guccibagshere.com',
      '*@guerillamail.*',
      '*@guerrillamail.*',
      '*@guerrillamailblock.com',
      '*@gustr.com',
      '*@h.mintemail.com',
      '*@h8s.org',
      '*@haltospam.com',
      '*@happymail.guru',
      '*@hatespam.org',
      '*@hidemail.de',
      '*@hochsitze.com',
      '*@hotpop.com',
      '*@hubii-network.com',
      '*@hulapla.de',
      '*@hurify1.com',
      '*@hushmail.com',
      '*@ieatspam.*',
      '*@ieatspam.eu',
      '*@ieatspam.info',
      '*@ihateyoualot.info',
      '*@iheartspam.org',
      '*@imails.info',
      '*@imgof.com',
      '*@iname.com',
      '*@inboxalias.com',
      '*@inboxclean.com',
      '*@inboxclean.org',
      '*@incognitomail.*',
      '*@incognitomail.com',
      '*@incognitomail.net',
      '*@incognitomail.org',
      '*@insorg-mail.info',
      '*@ipoo.org',
      '*@irish2me.com',
      '*@itis0k.org',
      '*@iwi.net',
      '*@jetable.*',
      '*@jetable.com',
      '*@jetable.fr.nf',
      '*@jetable.net',
      '*@jetable.org',
      '*@jnxjn.com',
      '*@jourrapide.com',
      '*@junk1e.com',
      '*@kasmail.com',
      '*@kaspop.com',
      '*@keemail.me',
      '*@keepmymail.com',
      '*@killmail.com',
      '*@killmail.net',
      '*@kir.ch.tc',
      '*@klassmaster.com',
      '*@klassmaster.net',
      '*@klzlk.com',
      '*@kopqi.com',
      '*@koszmail.pl',
      '*@kulturbetrieb.info',
      '*@kurzepost.de',
      '*@l0real.net',
      '*@lackmail.net',
      '*@larjem.com',
      '*@letthemeatspam.com',
      '*@lhsdv.com',
      '*@lifebyfood.com',
      '*@link2mail.net',
      '*@litedrop.com',
      '*@loketa.com',
      '*@lol.ovpn.to',
      '*@lookugly.com',
      '*@lopl.co.cc',
      '*@lortemail.dk',
      '*@lr78.com',
      '*@m-drugs.com',
      '*@m4ilweb.info',
      '*@maboard.com',
      '*@mail-temporaire.fr',
      '*@mail.by',
      '*@mail.mezimages.net',
      '*@mail.ru',
      '*@mail1click.com',
      '*@mail2rss.org',
      '*@mail333.com',
      '*@mail4trash.com',
      '*@mailbidon.com',
      '*@mailblocks.com',
      '*@mailcatch.com',
      '*@maildrop.cc',
      '*@maildx.com',
      '*@maileater.com',
      '*@mailexpire.com',
      '*@mailforspam.com',
      '*@mailfreeonline.com',
      '*@mailin8r.com',
      '*@mailinater.com',
      '*@mailinator.com',
      '*@mailinator.net',
      '*@mailinator*.*',
      '*@mailinator2.com',
      '*@mailincubator.com',
      '*@mailismagic.com',
      '*@mailme.ir',
      '*@mailme.lv',
      '*@mailmetrash.com',
      '*@mailmoat.com',
      '*@mailna.biz',
      '*@mailna.co',
      '*@mailna.in',
      '*@mailna.me',
      '*@mailnator.com',
      '*@mailnesia.com',
      '*@mailnull.com',
      '*@mailshell.com',
      '*@mailsiphon.com',
      '*@mailslite.com',
      '*@mailtothis.com',
      '*@mailzilla.com',
      '*@mailzilla.org',
      '*@mamber.net',
      '*@mbx.cc',
      '*@mega.zik.dj',
      '*@meinspamschutz.de',
      '*@meltmail.com',
      '*@messagebeamer.de',
      '*@mierdamail.com',
      '*@mintemail.com',
      '*@moburl.com',
      '*@mohmal.com',
      '*@mohmal.im',
      '*@mohmal.in',
      '*@mohmal.tech',
      '*@moncourrier.fr.nf',
      '*@monemail.fr.nf',
      '*@monmail.fr.nf',
      '*@monumentmail.com',
      '*@mozej.com',
      '*@msa.minsmail.com',
      '*@mt2009.com',
      '*@mvrht.net',
      '*@mx0.wwwnew.eu',
      '*@mycleaninbox.net',
      '*@mypartyclip.de',
      '*@myphantomemail.com',
      '*@myspaceinc.com',
      '*@myspaceinc.net',
      '*@myspaceinc.org',
      '*@myspacepimpedup.com',
      '*@myspamless.com',
      '*@mytrashmail.com',
      '*@neomailbox.com',
      '*@nepwk.com',
      '*@nervmich.net',
      '*@nervtmich.net',
      '*@netmails.com',
      '*@netmails.net',
      '*@netzidiot.de',
      '*@neverbox.com',
      '*@nickrizos.com',
      '*@no-spam.ws',
      '*@nobulk.com',
      '*@noclickemail.com',
      '*@nogmailspam.info',
      '*@nomail.xl.cx',
      '*@nomail2me.com',
      '*@nomorespamemails.com',
      '*@nospam.ze.tc',
      '*@nospam4.us',
      '*@nospamfor.us',
      '*@nospamthanks.info',
      '*@notmailinator.com',
      '*@notsharingmy.info',
      '*@nowmymail.com',
      '*@nurfuerspam.de',
      '*@nus.edu.sg',
      '*@nwldx.com',
      '*@nwytg.com',
      '*@o3enzyme.com',
      '*@objectmail.com',
      '*@obobbo.com',
      '*@oneoffemail.com',
      '*@onewaymail.com',
      '*@online.ms',
      '*@oopi.org',
      '*@opayq.com',
      '*@opentrash.com',
      '*@ordinaryamerican.net',
      '*@otherinbox.com',
      '*@ourklips.com',
      '*@outlawspam.com',
      '*@ovpn.to',
      '*@owlpic.com',
      '*@pancakemail.com',
      '*@pay-mon.com',
      '*@pimpedupmyspace.com',
      '*@pjjkp.com',
      '*@pokemail.net',
      '*@politikerclub.de',
      '*@poly-swarm.com',
      '*@poofy.org',
      '*@pookmail.com',
      '*@privacy.net',
      '*@proxymail.eu',
      '*@prtnx.com',
      '*@punkass.com',
      '*@PutThisInYourSpamDatabase.com',
      '*@pwrby.com',
      '*@qq.com',
      '*@quickinbox.com',
      '*@qwertymail.ru',
      '*@qwertynet.biz',
      '*@qwertynet.org',
      '*@qwertynet.ru',
      '*@rcpt.at',
      '*@re-gister.com',
      '*@recode.me',
      '*@recursor.net',
      '*@regbypass.com',
      '*@regbypass.comsafe-mail.net',
      '*@rejectmail.com',
      '*@rhyta.com',
      '*@rklips.com',
      '*@rmqkr.net',
      '*@rppkn.com',
      '*@rtrtr.com',
      '*@rxbuy-pills.info',
      '*@s0ny.net',
      '*@safemail.net',
      '*@safersignup.de',
      '*@safetymail.info',
      '*@safetypost.de',
      '*@sandelf.de',
      '*@saynotospams.com',
      '*@scalpnet.ru',
      '*@scramble.io',
      '*@selfdestructingmail.com',
      '*@sendspamhere.com',
      '*@SendSpamHere.com',
      '*@sevmail.ru',
      '*@sfamo.com',
      '*@sharklasers.com',
      '*@shiftmail.com',
      '*@shinnemo.com',
      '*@shitmail.me',
      '*@shortmail.net',
      '*@sibmail.com',
      '*@sina.com',
      '*@skeefmail.com',
      '*@slaskpost.se',
      '*@slopsbox.com',
      '*@smellfear.com',
      '*@snakemail.com',
      '*@sneakemail.com',
      '*@sofimail.com',
      '*@sofort-mail.de',
      '*@sogetthis.com',
      '*@soodonims.com',
      '*@spam.la',
      '*@spam.su',
      '*@spam4.me',
      '*@spamavert.com',
      '*@spambob.*',
      '*@spambob.com',
      '*@spambob.net',
      '*@spambob.org',
      '*@spambog.*',
      '*@spambog.com',
      '*@spambog.de',
      '*@spambog.ru',
      '*@spambooger.com',
      '*@spambox.com',
      '*@spambox.info',
      '*@spambox.irishspringrealty.com',
      '*@spambox.us',
      '*@spamcannon.com',
      '*@spamcannon.net',
      '*@spamcero.com',
      '*@spamcon.org',
      '*@spamcorptastic.com',
      '*@spamcowboy.com',
      '*@spamcowboy.net',
      '*@spamcowboy.org',
      '*@spamday.com',
      '*@spameater.org',
      '*@spamex.com',
      '*@spamfree24.*',
      '*@spamfree24.com',
      '*@spamfree24.de',
      '*@spamfree24.eu',
      '*@spamfree24.info',
      '*@spamfree24.net',
      '*@spamfree24.org',
      '*@spamgourmet.*',
      '*@spamgourmet.com',
      '*@spamgourmet.net',
      '*@spamgourmet.org',
      '*@spamherelots.com',
      '*@SpamHereLots.com',
      '*@spamhereplease.com',
      '*@SpamHerePlease.com',
      '*@spamhole.com',
      '*@spamify.com',
      '*@spaminator.de',
      '*@spamkill.info',
      '*@spaml.com',
      '*@spaml.de',
      '*@spammotel.com',
      '*@spamobox.com',
      '*@spamoff.de',
      '*@spamslicer.com',
      '*@spamspot.com',
      '*@spamthis.co.uk',
      '*@spamthisplease.com',
      '*@spamtrail.com',
      '*@speed.1s.fr',
      '*@streetwisemail.com',
      '*@supergreatmail.com',
      '*@supermailer.jp',
      '*@superrito.com',
      '*@suremail.info',
      '*@tafmail.com',
      '*@taylorventuresllc.com',
      '*@teewars.org',
      '*@teleworm.com',
      '*@teleworm.us',
      '*@tempalias.com',
      '*@tempe-mail.com',
      '*@tempemail.biz',
      '*@tempemail.com',
      '*@tempemail.net',
      '*@TempEMail.net',
      '*@tempinbox.co.uk',
      '*@tempinbox.com',
      '*@tempmail.it',
      '*@tempmail2.com',
      '*@tempomail.fr',
      '*@temporarily.de',
      '*@temporarioemail.com.br',
      '*@temporaryemail.net',
      '*@temporaryforwarding.com',
      '*@temporaryinbox.com',
      '*@thanksnospam.info',
      '*@thankyou2010.com',
      '*@thisisnotmyrealemail.*',
      '*@thisisnotmyrealemail.com',
      '*@throwawayemailaddress.*',
      '*@throwawayemailaddress.com',
      '*@tilien.com',
      '*@tmailinator.com',
      '*@topikt.com',
      '*@tradermail.info',
      '*@trash-amil.com',
      '*@trash-mail.at',
      '*@trash-mail.com',
      '*@trash-mail.de',
      '*@trash-me.com',
      '*@trash2009.com',
      '*@trashemail.de',
      '*@trashmail.at',
      '*@trashmail.com',
      '*@trashmail.de',
      '*@trashmail.me',
      '*@trashmail.net',
      '*@trashmail.org',
      '*@trashmail.ws',
      '*@trashmailer.com',
      '*@trashymail.com',
      '*@trashymail.net',
      '*@travala10.com',
      '*@trbvm.com',
      '*@trbvn.com',
      '*@trillianpro.com',
      '*@trimsj.com',
      '*@tryalert.com',
      '*@turual.com',
      '*@twinmail.de',
      '*@tyldd.com',
      '*@uggsrock.com',
      '*@upliftnow.com',
      '*@uplipht.com',
      '*@venompen.com',
      '*@veryrealemail.com',
      '*@viditag.com',
      '*@viewcastmedia.com',
      '*@viewcastmedia.net',
      '*@viewcastmedia.org',
      '*@vpslists.com',
      '*@walkmail.net',
      '*@webm4il.info',
      '*@wegwerfadresse.de',
      '*@wegwerfemail.de',
      '*@wegwerfmail.*',
      '*@wegwerfmail.de',
      '*@wegwerfmail.net',
      '*@wegwerfmail.org',
      '*@wetrainbayarea.com',
      '*@wetrainbayarea.org',
      '*@wh4f.org',
      '*@whyspam.me',
      '*@willselfdestruct.com',
      '*@winemaven.info',
      '*@wronghead.com',
      '*@wuzup.net',
      '*@wuzupmail.net',
      '*@www.e4ward.com',
      '*@www.gishpuppy.com',
      '*@www.mailinator.com',
      '*@wwwnew.eu',
      '*@x.bigpurses.org',
      '*@xagloo.com',
      '*@xemaps.com',
      '*@xents.com',
      '*@xmaily.com',
      '*@xoxy.net',
      '*@xoxy.uk',
      '*@yep.it',
      '*@yk20.com',
      '*@yogamaven.com',
      '*@yopmail.*',
      '*@you-spam.com',
      '*@ypmail.webarnak.fr.eu.org',
      '*@yuurok.com',
      '*@zehnminutenmail.de',
      '*@zep-hyr.com',
      '*@zippiex.com',
      '*@zippymail.info',
      '*@zoaxe.com',
      '*@zoemail.org',
      '*disposable*'
    );

    // Check passed email address against blacklist
    foreach($arrayEmailBlacklist as $_value) {
      if (fnmatch($_value, $aEmailAddress)) {
        return true;
      }
    }

    return null;
  }

}
// ====================================================================================================================

?>