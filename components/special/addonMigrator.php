<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

const ADMIN_JSON_FILE = ROOT_PATH . '/../adminsql.json';

const REAL_DATASTORE_PATH = ROOT_PATH . '/../datastore/addons/';

const LIVE_ROOT_PATH = ROOT_PATH . '/../addons.palemoon.org/';
const ADDON_SHADOW_PATH = LIVE_ROOT_PATH . '.obj/shadow/addons/';

// Include modules
$arrayIncludes = ['database', 'mozillaRDF'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleMozillaRDF = new classMozillaRDF();

// ====================================================================================================================

// == | Functions | ===================================================================================================

function funcMigratorReadFileFromZip($_archive, $_file, $_checkExistance = null) {
  $file = funcUnifiedVariable('var', @file_get_contents('zip://' . ROOT_PATH . $_archive . "#" . $_file));

  if (!$file) {
    return null;
  }

  if ($_checkExistance) {
    unset($file);
    return true;
  }

  return $file;
}

// --------------------------------------------------------------------------------------------------------------------

function funcAddons() {
  $arrayAddons = $GLOBALS['arrayCategoriesDB'];
  $arrayFinalAddons = array();

  // Assign the unlisted add-ons to a category so they get processed
  $arrayAddons['unlisted'] = array('phoebus-dev', 'cookieviewer');

  // This will get rid of the title and externals keys from the category array
  foreach ($arrayAddons as $_key => $_value) {
    foreach ($_value as $_key2 => $_value2) {
      if ($_key2 == 'title' || $_key2 == 'externals') {
        unset($arrayAddons[$_key]['title'], $arrayAddons[$_key]['externals']);
      }
    }

    if (empty($arrayAddons[$_key])) {
      unset($arrayAddons[$_key]);
    }
  }

  // This is where every extension and theme is processed and prepared for SQL storage
  foreach ($arrayAddons as $_key => $_value) {
    foreach($_value as $_key2 => $_value2) {
      $_temp = null;
      $_manifestFile = null;
      $_contentFile = null;
      $_licenseFile = null;
      $_shadowFile = null;

      // --------------------------------------------------------------------------------------------------------------

      // Check if phoebus.manifest and the shadow file exist and read the shadow file only
      if (file_exists(REAL_DATASTORE_PATH . $_value2 . '/phoebus.manifest') &&
          file_exists(ADDON_SHADOW_PATH . $_value2 . '.json')) {
        $_manifestFile = true;
        $_shadowFile = json_decode(file_get_contents(ADDON_SHADOW_PATH . $_value2 . '.json'), true) ?? null;
      }

      if (!$_manifestFile || !$_shadowFile) {
        print(
          'Warning - Skipping Add-on ' . $_value2 .
          ' - Manifest: ' . (bool)$_manifestFile . ' - Shadow: ' . (bool)$_shadowFile .
          NEW_LINE
        );
        continue;
      }

      $_temp = $_shadowFile;
      $_temp['active'] = true;
      $_temp['reviewed'] = true;
      $_temp['category'] = $_key;

      // --------------------------------------------------------------------------------------------------------------

      // If phoebus.content exists read it
      if (file_exists(REAL_DATASTORE_PATH . $_value2 . '/phoebus.content')) {
        $_contentFile = file_get_contents(REAL_DATASTORE_PATH . $_value2 . '/phoebus.content');
        $_temp['content'] = $_contentFile;
      }

       // If phoebus.license exists read it
      if (file_exists(REAL_DATASTORE_PATH . $_value2 . '/phoebus.license')) {
        $_licenseFile = file_get_contents(REAL_DATASTORE_PATH . $_value2 . '/phoebus.license');
      }

      // Set content to the contents of phoebus.content
      $_temp['content'] = $_contentFile;

      // Set licenseText to the contents of phoebus.license
      $_temp['licenseText'] = $_licenseFile;

      // --------------------------------------------------------------------------------------------------------------

      // SQL does not like a field called "release" so we will use "releaseXPI"
      $_temp['releaseXPI'] = $_temp['release'];

      // Read the install.rdf from the release xpi file
      $_installManifest =
        funcMigratorReadFileFromZip(DATASTORE_RELPATH . 'addons/' . $_value2 . '/' . $_temp['releaseXPI'], 'install.rdf');

      if (!$_installManifest) {
        print('Warning - Skipping Add-on ' . $_value2 . 'Release XPI file cannot be read' . NEW_LINE);
        continue;
      }

      // Parse install.rdf
      $_installManifest = $GLOBALS['moduleMozillaRDF']->parseInstallManifest($_installManifest);

      if (!is_array($_installManifest)) {
        print('Warning - Skipping Add-on ' . $_value2 . 'Release XPI install manifest cannot be read' . NEW_LINE);
        continue;
      }

      // If install.rdf has a description then use it else use whatever we have in the shadow file
      // NOTE: description will become manditory for submission to the Add-ons Site
      if (array_key_exists('description', $_installManifest)) {
        $_temp['description'] = $_installManifest['description']['en-US'];
      }
      else {
        $_temp['description'] = $_temp['shortDescription'];
      }

      // --------------------------------------------------------------------------------------------------------------

      // We don't use "prettyDate" anymore and "date" is assigned dynamically so remove it
      foreach($_temp['xpinstall'] as $_key3 => $_value3) {
        $_temp['xpinstall'][$_key3]['epoch'] = $_temp['xpinstall'][$_key3]['mtime'];
        unset(
          $_temp['xpinstall'][$_key3]['mtime'],
          $_temp['xpinstall'][$_key3]['date'],
          $_temp['xpinstall'][$_key3]['prettyDate']
        );
      }

      // --------------------------------------------------------------------------------------------------------------
     
      // These all are items either obsoleted or dynamically set and don't belong in SQL
      unset(
        $_temp['shortDescription'],
        $_temp['longDescription'],
        $_temp['release'],
        $_temp['basePath'],
        $_temp['baseURL'],
        $_temp['licenseName'],
        $_temp['licenseDefault'],
        $_temp['icon'],
        $_temp['preview'],
        $_temp['hasPreview'],
        $_temp['phoebus']
      );

      $arrayFinalAddons[] = $_temp;
      print('Processed ' . ucfirst($_temp['type']) . ': ' . $_value2 . NEW_LINE);
    }
  }

  return $arrayFinalAddons;
  exit();
}

// --------------------------------------------------------------------------------------------------------------------

function funcExternals() {
  $arrayExternals = array();
  $arrayFinalExternals = array();

  // We want to build a category array of ONLY externals
  foreach ($GLOBALS['arrayCategoriesDB'] as $_key => $_value) {
    if ($_key == 'themes') {
      continue;
    }

    $arrayExternals[$_key] = $_value['externals'];
  }

  // This is where every applicable external is processed and prepared for SQL storage
  foreach ($arrayExternals as $_key => $_value) {
    foreach ($_value as $_key2 => $_value2) {
      // We don't like AMO..
      if (contains($_value2['url'], 'addons.mozilla.org')) {
        continue;
      }

      // The rest of an add-on manifest that an external doesn't have
      $_temp = array(
        'releaseXPI' => null,
        'active' => true,
        'reviewed' => true,
        'tags' => null,
        'creator' => null,
        'content' => null,
        'homepageURL' => null,
        'repository' => null,
        'supportURL' => null,
        'supportEmail' => null,
        'license' => null,
        'licenseURL' => null,
        'licenseText' => null,
        'xpinstall' => null
      );

      // Assign relevant values to the temp array
      $_temp['slug'] = $_key2;
      $_temp['id'] = $_key2 . '@' . $_value2['id'];
      $_temp['type'] = 'external';
      $_temp['name'] = ucfirst($_value2['name']);
      $_temp['description'] = $_value2['shortDescription'];
      $_temp['url'] = $_value2['url'];
      $_temp['category'] = $_key;
      
      $arrayFinalExternals[] = $_temp;
      print('Processed External: ' . $_key2 . NEW_LINE);
    }
  }

  return $arrayFinalExternals;
}

// --------------------------------------------------------------------------------------------------------------------

function funcLangPacks() {
  $arrayGlobLangPacks = glob('./datastore/langpacks/palemoon/*.xpi');
  $arrayFinalLangPacks = array();

  foreach ($arrayGlobLangPacks as $_value) {
    $_installManifest = funcMigratorReadFileFromZip('/' . $_value, 'install.rdf');
    $_installManifest = $GLOBALS['moduleMozillaRDF']->parseInstallManifest($_installManifest);

    $_slug = strtolower('pm-' . str_replace('@palemoon.org', '', $_installManifest['id']));
    $_xpiName = basename($_slug . '-' . $_installManifest['version'] . '.xpi');
    $_tags =
      strtolower(str_replace(array( '(', ')' ), '', $_installManifest['name']['en-US'])) . ' lang langpack';

    $_temp = array(
      'id' => $_installManifest['id'],
      'name' => str_replace(' Language Pack', '', $_installManifest['name']['en-US']),
      'slug' => $_slug,
      'description' => 'Pale Moon Language Pack',
      'creator' => 'Moonchild Productions',
      'type' => 'langpack',
      'license' => 'mpl-2.0',
      'url' => '/addon/' . $_slug . '/',
      'tags' => $_tags,
      'category' => 'language-packs',
      'homepageURL' => 'https://crowdin.com/project/pale-moon',
      'reviewed' => 1,
      'active' => 1,
      'releaseXPI' => $_xpiName,
      'xpinstall' => array(
        $_xpiName => array(
          'version' => $_installManifest['version'],
          'hash' => hash_file('sha256', $_value),
          'epoch' => 1528761600,
          'targetApplication' => $_installManifest['targetApplication']
        ),
      ),
    );

    if (!file_exists('./datastore/addons/' . $_slug)) {
      print('Creating directory ./datastore/addons/' . $_slug . "\n");
      mkdir('./datastore/addons/' . $_slug);
    }

    if (!file_exists('./datastore/addons/' . $_slug . '/' . $_xpiName) ||
        !file_exists('./datastore/addons/' . $_slug . '/icon.png')) {
      print('Copying XPI File ' . $_xpiName . "\n");
      copy($_value, './datastore/addons/' . $_slug . '/' . $_xpiName);
      copy(
        './datastore/langpacks/icons/' .
        str_replace(array('langpack-', '@palemoon.org'), '', $_installManifest['id']) .
        '.png', './datastore/addons/' . $_slug . '/icon.png'
      );
    }

    $arrayFinalLangPacks[] = $_temp;
    print('Processed Langpack: ' . $_slug . NEW_LINE);
  }

  return $arrayFinalLangPacks;
}

// --------------------------------------------------------------------------------------------------------------------

function funcUsers() {
  $arrayGlobJSON = glob('../.vsftpd/users/*.json');
  $arrayAdmins = json_decode(file_get_contents(ADMIN_JSON_FILE), true);
  $arrayFinalUsers = array();
  $time = time();

  // Process Administrators
  // "addons-team" is not actually an administrator but it is stored in this json so..
  foreach ($arrayAdmins as $_value) {
    $_temp = $_value;
    $_temp['addons'] = json_encode($_temp['addons'], 320);
    $extraData = array(
      'regEpoch' => $time,
      'verification' => null
    );
    $_temp['extraData'] = json_encode($extraData, 320);
    $arrayFinalUsers[] = $_temp;
    print('Processed Administrator: ' . $_temp['username'] . NEW_LINE);
  }

  // Process Users
  foreach ($arrayGlobJSON as $_value) {
    // We don't want to use the FTP admin passwords
    if (contains($_value, 'admin.json')) {
      continue;
    }

    // Decode the user json file
    $_json = json_decode(file_get_contents($_value), true) ?? null;
    
    if (!$_json) {
      print(
        'Warning: Skipping User ' . str_replace('.json', '', basename($_value)) .
        ' - Something is wrong with the JSON file' . NEW_LINE
      );
      continue;
    }

    // Do I have to explain what this does? Seriously...
    $_temp = array();
    $_temp['username'] = $_json['account']['username'];
    $_temp['password'] = password_hash($_json['account']['password'], PASSWORD_BCRYPT);
    $_temp['active'] = true;
    $_temp['level'] = 1;

    // Users with five or more add-ons get Level 2 status
    if (count($_json['addons']) >= 5 && !in_array($_temp['username'], ['srazzano', 'riiis'])) {
      $_temp['level'] = 2;
    }

    $_temp['addons'] = json_encode($_json['addons'], 320);
    $extraData = array(
      'regEpoch' => $time,
      'verification' => null
    );
    $_temp['extraData'] = json_encode($extraData, 320);
    $arrayFinalUsers[] = $_temp;
    print('Processed User: ' . $_temp['username'] . NEW_LINE);
  }

  return $arrayFinalUsers;
}

// --------------------------------------------------------------------------------------------------------------------

function funcSQL($_arrayAddons, $_arrayUsers) {
  $moduleDatabase = &$GLOBALS['moduleDatabase'];

  if ($_arrayAddons) {
    // Purge the tables
    $moduleDatabase->query('normal', "DELETE FROM addon");
    $moduleDatabase->query('normal', "DELETE FROM client");
    foreach ($_arrayAddons as $_value) {
      $_addon = $_value;
      $_client = array('addonID' => $_addon['id']);

      // Build the client table denoting which applications a release xpi happens to support
      if ($_addon['xpinstall']) {
        $_targetApplication = array_keys($_addon['xpinstall'][$_addon['releaseXPI']]['targetApplication']);

        $_client['toolkit']     = in_array(TARGET_APPLICATION_ID['toolkit'], $_targetApplication) ? true : false;
        $_client['palemoon']    = in_array(TARGET_APPLICATION_ID['palemoon'], $_targetApplication) ? true : false;
        $_client['basilisk']    = in_array(TARGET_APPLICATION_ID['basilisk'], $_targetApplication) ? true : false;
        $_client['ambassador']  = in_array(TARGET_APPLICATION_ID['ambassador'], $_targetApplication) ? true : false;
        $_client['borealis']    = in_array(TARGET_APPLICATION_ID['borealis'], $_targetApplication) ? true : false;
        $_client['interlink']   = in_array(TARGET_APPLICATION_ID['interlink'], $_targetApplication) ? true : false;
        
        // XPInstall needs to be stored as JSON so encode it
        $_addon['xpinstall'] = json_encode($_addon['xpinstall'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      }
      else {
        // Externals support Pale Moon
        $_client['palemoon'] = 1;
      }

      // Insert add-on and client data into SQL
      $moduleDatabase->query('normal', "INSERT INTO ?n SET ?u", 'addon', $_addon);
      $moduleDatabase->query('normal', "INSERT INTO ?n SET ?u", 'client', $_client);
      print('Inserted ' . ucfirst($_value['type']) . ': ' . $_value['slug'] . NEW_LINE);
    }
  }

  if ($_arrayUsers) {
    // Purge the table
    $moduleDatabase->query('normal', "DELETE FROM user");
    foreach ($_arrayUsers as $_key => $_value) {
      $moduleDatabase->query('normal', "INSERT INTO ?n SET ?u", 'user', $_value);
      print('Inserted User: ' . $_value['username'] . NEW_LINE);
    }
  }
}

// ====================================================================================================================

// == | Main | ========================================================================================================

funcSendHeader('html');

// Include old category array - $arrayCategoriesDB
require_once(LIVE_ROOT_PATH . '/db/categories.php');
print(file_get_contents('./components/special/skin/default/template-header.xhtml'));
print('<h2>Manifest to SQL Migration</h2>');
print('<textarea style="width: 99%; resize: none;" name="content" rows="30" readonly>');

print('== | Processing Phase' . NEW_LINE);

$arrayAddons = funcAddons();
$arrayExternals = funcExternals();
$arrayLangPacks = funcLangPacks();

$arrayAddonsFinal = array_merge($arrayAddons, $arrayExternals, $arrayLangPacks);

$arrayUsers = funcUsers();

print(NEW_LINE . '== | SQL Insertion Phase' . NEW_LINE);

funcSQL($arrayAddonsFinal, $arrayUsers);

print('</textarea>');
print(file_get_contents('./components/special/skin/default/template-footer.xhtml'));

// ====================================================================================================================

?>