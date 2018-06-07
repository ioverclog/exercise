<?php
class dbObj {
    var $servername = 'localhost';
    var $username = 'root';
    var $password = '1111';
    var $dbname = 'exercise';
    var $conn;
    
    function getConnstring() {
        $con = mysqli_connect($this->servername, $this->username, $this->password, $this->dbname) or die("Connect failed: " . mysqli_connect_error());
        mysqli_query($con, 'set names utf8');
        
        if(mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        } else {
            $this->conn = $con;
        }
        return $this->conn;
    }
}
?>
