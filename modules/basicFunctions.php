<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Function: funcError | =================================================

// This function simply relays an error message and dies
function funcError($_value) {
  ob_get_clean();
  header('Content-Type: text/html', false);   
  print(file_get_contents('./skin/default/template-header.xhtml'));
  print('<h2>' . $GLOBALS['strProductName'] . ' ' . $GLOBALS['strApplicationVersion'] . '</h2>');
  print('<p class="pulseText" style="text-decoration: blink;"><strong>Fatal Error</strong></p>');
  print('<ul><li>' . $_value . '</li></ul>');
  print(file_get_contents('./skin/default/template-footer.xhtml'));
  
  // We are done here
  exit();
}

// ============================================================================

// == | Function: funcHTTPGetValue |===========================================

// This function gets HTTP GET arguments and performs /very/ basic filtering
// or returns a predictable null
function funcHTTPGetValue($_value) {
  if (!isset($_GET[$_value]) || $_GET[$_value] === '' ||
    $_GET[$_value] === null || empty($_GET[$_value])) {
    return null;
  }
  else {  
    $_finalValue =
      preg_replace('/[^-a-zA-Z0-9_\-\/\{\}\@\.\%\s]/', '', $_GET[$_value]);
    return $_finalValue;
  }
}

// ============================================================================

// == | Function: funcCheckVar | ==============================================

// This function is good for truely knowing if an /existing/ variable has
// a useable value or returns a predictable null
function funcCheckVar($_value) {
  if ($_value === '' || $_value === 'none' || $_value === null || empty($_value)) {
    return null;
  }
  else {
    return $_value;
  }
}

// ============================================================================

// == | funcSendHeader | ======================================================

// This function allows easy sending of common header types
function funcSendHeader($_value) {
  $_arrayHeaders = array(
    '404' => 'HTTP/1.0 404 Not Found',
    '501' => 'HTTP/1.0 501 Not Implemented',
    'html' => 'Content-Type: text/html',
    'text' => 'Content-Type: text/plain',
    'xml' => 'Content-Type: text/xml',
    'css' => 'Content-Type: text/css',
    'phoebus' => 'X-Phoebus: https://github.com/Pale-Moon-Addons-Team/phoebus/',
  );
  
  if (array_key_exists($_value, $_arrayHeaders)) {
    header($_arrayHeaders['phoebus']);
    header($_arrayHeaders[$_value]);
    
    if ($_value == '404') {
      // We are done here
      exit();
    }
  }
  else {
    // Fallback to text
    header($_arrayHeaders['text']);
  }
}

// ============================================================================

// == | Function: funcRedirect |===============================================

// This function sends a redirect header
function funcRedirect($_strURL) {
	header('Location: ' . $_strURL , true, 302);
  
  // We are done here
  exit();
}

// ============================================================================

// == | Function: funcRealArray | =============================================

// Sometimes classes and crap can send array-like objects instead of true
// arrays.. This uses a quick and dirty method of converting them to real arrays
// using json encode/decode.. IF the input is not an object or array OR json
// fails it will return null (we hope)
function funcRealArray($_value, $_isMD = true) {
  if (is_object($_value) || is_array($_value)) {
    if ($_isMD == true) {
      $_result = json_decode(json_encode($_value), true);
    }
    else {
      $_result = json_decode(json_encode($_value));
    }
  }
  else {
    return null;
  }
  
  return $_result;
}

// ============================================================================

// == | Function: funcCheckUserAgent |=========================================

// YEAH well.. bite me!
function funcCheckUserAgent() {
  if (startsWith(strtolower($_SERVER['HTTP_USER_AGENT']), 'wget/') ||
    startsWith(strtolower($_SERVER['HTTP_USER_AGENT']), 'curl/')) {
    funcSendHeader('404');
  }
}

// ============================================================================

// == | Function: funcPrintVar |===============================================

// Does something I manually typed too many time...
function funcPrintVar($_var) {
  ob_get_clean();
  funcSendHeader('text');
  var_export($_var);
  die();
}

// ============================================================================

// == | Functions: startsWith, endsWith, contains |============================

// These functions are stolen.. They may be suboptimal but are very useful
function startsWith($haystack, $needle) {
   $length = strlen($needle);
   return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
    return true;
  }

  return (substr($haystack, -$length) === $needle);
}

function contains($haystack, $needle) {
  if (strpos($haystack, $needle) > -1) {
    return true;
  }
  else {
    return false;
  }
}

// ============================================================================

?>