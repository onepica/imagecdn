<?php

//NOTE: This file has been modified by OnePica since there were problems
//with the code provided by Highwinds (mostly the addition of the upload
//function). Be cautious of simply replacing this file with a new version.

class hwCDN {
  var $apiKey;   // API key
  var $data;     // Array of last result
  var $endpoint; // Target URL
  var $error;    // hwCDN errors
  var $password; // Password
  var $username; // Username
  var $xml;      // XML of last result

  public function __construct() {
    $this->endpoint = 'https://st-api.hwcdn.net/index.php';
  }

  // Set target URL endpoint
  public function setEndpoint($endpoint) {
    $this->endpoint = $endpoint;
    return true;
  }

  // Set API key
  public function setApiKey($key) {
    $this->apiKey = $key;
    return true;
  }

  // Set Username
  public function setUsername($username) {
    $this->username = $username;
    return true;
  }

  // Set Password
  public function setPassword($password) {
    $this->password = md5($password);
    return true;
  }

  // Generic error handling captures errors both from this class and from CDNWS calls
  private function setError($string = null) {
    $this->error = ($string == null) ? null : $string . ' ' . $this->error;
    return false;
  }

  // Generate API token from action, API key, username and password
  private function renderToken($action) {
    if ($action == '')           { return $this->setError('Could not render authentication token because action is not defined');   }
    if (!isset($this->apiKey))   { return $this->setError('Could not render authentication token because API key is not defined');  }
    if (!isset($this->username)) { return $this->setError('Could not render authentication token because username is not defined'); }
    if (!isset($this->password)) { return $this->setError('Could not render authentication token because password is not defined'); }

    $token = md5("action=$action&user=" . $this->username . "&key=".$this->apiKey."&password=".$this->password);

    return $token;
  }

  // XML to Array
  private function xmlToArray($xml) {
    $values = array();
    $index  = array();
    $array  = array();
    $parser = xml_parser_create();

    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE,   1);
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser, $xml, $values, $index);
    xml_parser_free($parser);

    $i = 0;

    $array = $this->struct_to_array($values, $i);

    return $array;
  }

  // XML to Array helper function
  private function struct_to_array($values, &$i) {
  	$child = array();
    if (isset($values[$i]['value'])) array_push($child, $values[$i]['value']);

    while ($i++ < count($values)) {
      switch ($values[$i]['type']) {
        case 'complete' :
          $name = $values[$i]['tag'];
          if (!empty($name)) {
            $child[$name] = ($values[$i]['value']) ? ($values[$i]['value']) : '';
            if(isset($values[$i]['attributes'])) {
              $child[$name] = $values[$i]['attributes'];
            }
          }
        break;

        case 'open' :
          $name = $values[$i]['tag'];
          $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
          $child[$name][$size] = $this->struct_to_array($values, $i);
        break;

        case 'close' :
          return $child;
        break;
      }
    }

    return $child;
  }

  // Function to execute an API Web Services command.
  // action     string    Abbreviated action to execute (MU, GC, etc)
  // params     array     Optional. Key=>Value pairs for standard parameters
  // fields     array     Optional. Kev=>Value pairs for field parameters. Note it is not
  //                      necessary to prepare the field1=$field&value1=$value syntax described
  //                      in the API documents, as this method will handle that for key=>value pairs
  public function execute($action, $params = array(), $fields = array()) {
    $this->setError();

    if (!$request['token']  = $this->renderToken($action)) {
      return ($this->error("Could not execute API call."));
    }

    $request['action'] = $action;
    $request['user']   = $this->username;

    foreach ($request as $k => $v) {
      $queryString[] = "$k=$v";
    }

    foreach ($params as $k => $v) {
      $queryString[] = "$k=$v";
    }

    $i = 1;

    foreach ($fields as $k => $v) {
      $queryString[] = "field$i=$k&value$i=$v";

      $i++;
    }

    $queryString  = join('&', $queryString);
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $this->endpoint);      // Target URL
    curl_setopt($ch, CURLOPT_POST, 1);                   // POST
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);         // Return result, instead of printing to stdout
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);  // Post fields
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $this->XML = curl_exec($ch);
    $this->data = $this->xmlToArray($this->XML);
    
    curl_close($ch);

    if ($this->data['result'] == 0) {
      return $this->setError($this->data['error'][0]['errmsg']);
    } else {
      return $this->data;
    }
  }
  
  public function upload($directory, $file) {
    $this->setError();
    $post = array();

    if (!$post['token'] = $this->renderToken('UF')) {
      return ($this->error("Could not execute API call."));
    }

    $post['action'] = 'UF';
    $post['user']   = $this->username;
    $post['directory'] = $directory;
    $post['Filedata'] = '@'.$file;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->endpoint);      // Target URL
    curl_setopt($ch, CURLOPT_POST, 1);                   // POST
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);         // Return result, instead of printing to stdout
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $this->XML = curl_exec($ch);
    $this->data = $this->xmlToArray($this->XML);
    curl_close($ch);

    if ($this->data['result'] == 0) {
      return $this->setError($this->data['error'][0]['errmsg']);
    } else {
      return $this->data;
    }
  }
}