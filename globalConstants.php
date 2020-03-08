<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Define basic constants for the software
const SOFTWARE_NAME       = 'Phoebus';
const SOFTWARE_VERSION    = '2.1.0a1';
const DATASTORE_RELPATH   = '/datastore/';
const OBJ_RELPATH         = '/.obj/';
const COMPONENTS_RELPATH  = '/components/';
const DATABASES_RELPATH   = '/databases/';
const MODULES_RELPATH     = '/modules/';
const LIB_RELPATH         = '/libraries/';
const NEW_LINE            = "\n";

// Define components
const COMPONENTS = array(
  'aus'             => ROOT_PATH . COMPONENTS_RELPATH . 'aus/addonUpdateService.php',
  'discover'        => ROOT_PATH . COMPONENTS_RELPATH . 'discover/discoverPane.php',
  'download'        => ROOT_PATH . COMPONENTS_RELPATH . 'download/addonDownload.php',
  'integration'     => ROOT_PATH . COMPONENTS_RELPATH . 'api/amIntegration.php',
  'panel'           => ROOT_PATH . COMPONENTS_RELPATH . 'panel/phoebusPanel.php',
  'site'            => ROOT_PATH . COMPONENTS_RELPATH . 'site/addonSite.php',
  'special'         => ROOT_PATH . COMPONENTS_RELPATH . 'special/specialComponent.php'
);

// Define modules
const MODULES = array(
  'account'         => ROOT_PATH . MODULES_RELPATH . 'classAccount.php',
  'database'        => ROOT_PATH . MODULES_RELPATH . 'classDatabase.php',
  'generateContent' => ROOT_PATH . MODULES_RELPATH . 'classGenerateContent.php',
  'mozillaRDF'      => ROOT_PATH . MODULES_RELPATH . 'classMozillaRDF.php',
  'persona'         => ROOT_PATH . MODULES_RELPATH . 'classPersona.php',
  'oldReadManifest' => ROOT_PATH . MODULES_RELPATH . 'classOldReadManifest.php',
  'readManifest'    => ROOT_PATH . MODULES_RELPATH . 'classReadManifest.php',
  'tap'             => ROOT_PATH . MODULES_RELPATH . 'classTap.php',
  'writeManifest'   => ROOT_PATH . MODULES_RELPATH . 'classWriteManifest.php',
  'vc'              => ROOT_PATH . MODULES_RELPATH . 'nsIVersionComparator.php',
);

// Define databases
const DATABASES = array(
  'emailBlacklist'  => ROOT_PATH . DATABASES_RELPATH . 'emailBlacklist.php',
  'searchPlugins'   => ROOT_PATH . DATABASES_RELPATH . 'searchPlugins.php',
);

// Define libraries
const LIBRARIES = array(
  'smarty'          => ROOT_PATH . LIB_RELPATH . 'smarty/libs/Smarty.class.php',
  'safeMySQL'       => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php',
  'rdfParser'       => ROOT_PATH . LIB_RELPATH . 'librdf/rdf_parser.php',
);

/* Known Application IDs
 * Application IDs are normally in the form of a {GUID} or user@host ID.
 *
 * Firefox:          {ec8030f7-c20a-464f-9b0e-13a3a9e97384}
 * Thunderbird:      {3550f703-e582-4d05-9a08-453d09bdfdc6}
 * SeaMonkey:        {92650c4d-4b8e-4d2a-b7eb-24ecf4f6b63a}
 * Fennec (Android): {aa3c5121-dab2-40e2-81ca-7ea25febc110}
 * Fennec (XUL):     {a23983c0-fd0e-11dc-95ff-0800200c9a66}
 * Sunbird:          {718e30fb-e89b-41dd-9da7-e25a45638b28}
 * Instantbird:      {33cb9019-c295-46dd-be21-8c4936574bee}
 * Adblock Browser:  {55aba3ac-94d3-41a8-9e25-5c21fe874539} */
const TOOLKIT_ID    = 'toolkit@mozilla.org';
const TOOLKIT_ALTID = 'toolkit@palemoon.org';
const TOOLKIT_BIT   = 1;

// Define application metadata
const TARGET_APPLICATION = array(
  'palemoon' => array(
    'enabled'       => true,
    'id'            => '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}',
    'bit'           => 2,
    'name'          => 'Pale Moon',
    'siteTitle'     => 'Pale Moon - Add-ons',
    'domain'        => array('live' => 'addons.palemoon.org', 'dev' => 'addons-dev.palemoon.org'),
    'features'      => ['https', 'extensions', 'extensions-cat', 'themes',
                        'personas', 'language-packs', 'search-plugins']
  ),
  'basilisk' => array(
    'enabled'       => true,
    'id'            => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
    'bit'           => 4,
    'name'          => 'Basilisk',
    'siteTitle'     => 'Basilisk: add-ons',
    'domain'        => array('live' => 'addons.basilisk-browser.org', 'dev' => null),
    'features'      => ['https', 'extensions', 'themes', 'personas', 'search-plugins']
  ),
  'ambassador' => array(
    'enabled'       => false,
    'id'            => '{4523665a-317f-4a66-9376-3763d1ad1978}',
    'bit'           => 8,
    'name'          => 'Ambassador',
    'siteTitle'     => 'Add-ons - Ambassador',
    'domain'        => array('live' => 'ab-addons.thereisonlyxul.org', 'dev' => null),
    'features'      => ['extensions', 'themes', 'disable-xpinstall']
  ),
  'borealis' => array(
    'enabled'       => false,
    'id'            => '{a3210b97-8e8a-4737-9aa0-aa0e607640b9}',
    'bit'           => 16,
    'name'          => 'Borealis',
    'siteTitle'     => 'Borealis Add-ons - Binary Outcast',
    'domain'        => array('live' => 'borealis-addons.binaryoutcast.com', 'dev' => null),
    'features'      => ['extensions', 'search-plugins']
  ),
  'interlink' => array(
    'enabled'       => true,
    'id'            => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
    'bit'           => 32,
    'name'          => 'Interlink',
    'siteTitle'     => 'Interlink Add-ons - Binary Outcast',
    'domain'        => array('live' => 'interlink-addons.binaryoutcast.com', 'dev' => null),
    'features'      => ['extensions', 'themes', 'search-plugins', 'disable-xpinstall']
  ),
);

const EXTENSION_CATEGORY_SLUGS = array(
  'alerts-and-updates'        => 'Alerts &amp; Updates',
  'appearance'                => 'Appearance',
  'bookmarks-and-tabs'        => 'Bookmarks &amp; Tabs',
  'download-management'       => 'Download Management',
  'feeds-news-and-blogging'   => 'Feeds, News, &amp; Blogging',
  'privacy-and-security'      => 'Privacy &amp; Security',
  'search-tools'              => 'Search Tools',
  'social-and-communication'  => 'Social &amp; Communication',
  'tools-and-utilities'       => 'Tools &amp; Utilities',
  'web-development'           => 'Web Development',
  'other'                     => 'Other'
);

const OTHER_CATEGORY_SLUGS = array(
  'themes'                    => 'Themes',
  'personas'                  => 'Personas',
  'search-plugins'            => 'Search Plugins',
  'language-packs'            => 'Language Packs',
);

const LICENSES = array(
  'Apache-2.0'                => 'Apache License 2.0',
  'Apache-1.1'                => 'Apache License 1.1',
  'BSD-3-Clause'              => 'BSD 3-Clause',
  'BSD-2-Clause'              => 'BSD 2-Clause',
  'GPL-3.0'                   => 'GNU General Public License 3.0',
  'GPL-2.0'                   => 'GNU General Public License 2.0',
  'LGPL-3.0'                  => 'GNU Lesser General Public License 3.0',
  'LGPL-2.1'                  => 'GNU Lesser General Public License 2.1',
  'AGPL-3.0'                  => 'GNU Affero General Public License v3',
  'MIT'                       => 'MIT License',
  'MPL-2.0'                   => 'Mozilla Public License 2.0',
  'MPL-1.1'                   => 'Mozilla Public License 1.1',
  'Custom'                    => 'Custom License',
  'PD'                        => 'Public Domain',
  'COPYRIGHT'                 => ''
);

?>