<?php

class REST {
    public $_request = array();
    public $_method = "GET";
    public $_content_type = "application/json";
    private $_code = 200;
    
    public function __construct(){
        $this->getUrlParam();
    }
    
    public function get_referer(){
        return $_SERVER['HTTP_REFERER'];
    }
    
    private function get_status_message(){
        $status = array(
            100 => 'Continue',
            101 => 'Switching Protocols'
            ... //TODO view site
        );
        return ($status[$this->_code])? $status[$this->_code] : $status[500];
    }
    
    public function get_request_method(){
        return $_SERVER['REQUEST_METHOD'];
    }
    
    public function get_method(){
        return $this->_method;
    }
    
    public function response($data, $status, $connection){
        $this->_code = ($status)? $status : 200;
        $this->set_headers();
        echo $data;
        mysqli_close($connection);
        exit;
    }
    
    private function getUrlParam(){
        switch( $this->get_request_method() ){
            case "POST":
              if($_POST){
                  $this->_request = $this->cleanInputs($_POST);
              } else {
                  $data = json_decode(file_get_contents("php://input"), true);
                  $this->_request = $this->cleanInputs($data['data']);
              }
              break;  
            case "GET":
              $this->_request = $this->cleanInputs($_GET);
              break;
            default:
              $this->response('', 406);
              break;
        }
        
        $method = $this->_request["_method"];
        $this->_method = (!empty($method))? strtoupper($this->_request["_method"]) : "GET";
    }
    
    private function cleanInputs($data){
        $clean_input = array();
        
        if( is_array($data) ){
            foreach($data as $k => $v){
                if($k == 'notice'){
                    $clean_input[$k] = $v === "true" ? 1 : 0;
                } else {
                    $clean_input[$k] = $this->cleanInputs($v);
                }
            }
        } else {
            if(get_magic_quotes_gpc()){
                $data = trim(stripslashes($data));
            }
            $data = strip_tags($data);
            $clean_input = trim($data);
        }
        
        return $clean_input;
    }
    
    private function set_headers(){
        header("HTTP/1.1 ".$this->_code." ".$this->get_status_message());
        header("Content-Type:".$this->_content_type);
    }
}
?>
























