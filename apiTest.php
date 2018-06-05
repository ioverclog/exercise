<?php

require_once("./connection.php");
require_once("./Rest.inc.php");

class API extends REST {
    public $db = array();
    public $connection = array();
    public $param;
    public $getParam;
    
    public function __construct(){
        parent::__construct();
        
        $db = new dbObj();
        $this->connection = $db->getConnstring();
    }
    
    public function get_exercise( $id = 0 )
    {
        $query = "SELECT * FROM regionName";
        $result = mysqli_query($this->connection, $query);
        while($row = mysqli_fetch_assoc($result))
        {
            $regionName[] = $row;
        }
        
        $query = "SELECT * FROM exercise
                  INNER JOIN regionInfo ON exercise.regionKey = regionInfo.regionId
                  INNER JOIN userInfo ON exercise.userKey = userInfo.userId";
                  
        if($id != 0){
            $query.=" WHERE exercise.id=".$id." LIMIT 1";
        }
        
        $response = array();
        $result = mysqli_query($this->connection, $query);
        while($row = mysqli_fetch_assoc($result))
        {
            $response[]=$row;
        }
        
        $stuff = array();
        foreach($regionName as $key => $regionItem){
            //Top Data
            $member = array('name'=>'jo-yong-chan', 'tel'=>'010-3232-3232');
            $region = array('title'=>'goyang-bodoso', 'info'=>array($member));
            $topData = array(array('title'=>'title', 'region'=>array($region)));
            
            //Region Data
            $regionData = array();
            foreach($response as $key => $data){
                $member = array('name'=>$data['userName'], 'tel'=>array($data['tel']));
                $subRegions = array('subRegion'=>$data['address3'], 'detailRegion'=>$data['detailAddress'].'<br />'.$data['startTime'], 'info' => array($member));
                $regionData[] = array('region'=>$data['address2'], 'subRegions'=>array($subRegions));
            }
            
            //end
            $stuff[$regionItem['name']] = array('topData'=>$topData, 'regionData'=>$regionData);
        }
        
        $this->response(json_encode($stuff), 200, $this->connection);
    }
    //DELETE
    public function delete_exercise( $id )
    {
        $response = array();
        $connection = $this->connection;
        $userinfoResult = '';
        $regioninfoResult = '';
        
        $query = "SELECT * FROM exercise WHERE id=".$id;
        $result = mysqli_query($connection, $query);
        $row = mysqli_fetch_assoc($result);
        
        $regionKey = $row['regionKey'];
        $userKey = $row['userKey'];
        
        ///////////////// DELETE USER
        $query = "SELECT * FROM exercise WHERE userKey=".$userKey;
        $result = mysqli_query($connection, $query);
        $rows = array();
        while($row = mysqli_fetch_assoc($result))
        {
            $rows[]=$row;
        }
        //if 1 user is in many place, dont deleted
        if( count($rows) == 1 ) {
            $query = "DELETE FROM userinfo WHERE userId=".$userKey;
            $result = mysqli_query($this->connection, $query);
            if( $result ){
                $userinfoResult = 'userinfo';
            }
        }
        
        //////////////// DELETE REGION (if delete region first, become dont select of user)
        $query = "DELETE FROM regioninfo WHERE regionId=".$regionKey;
        $result = mysqli_query($connection, $query);
        if($result) {
            $regioninfoResult = 'regioninfo';
        }
        
        if( $userinfoResult != '' || $regioninfoResult != '' ) {
            $response=array(
                'status' => 1,
                'status_message' => $userinfoResult.' '.$regioninfoResult.' Deleted Successfully'
            );
        } else {
            $response=array(
                'status' => 0,
                'status_message' => 'Fail Deleted.'
            );
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    //INSERT
    public function insert_exercise()
    {
        $insertedKeyArray = Array();
        
        $connection = $this->connection;
        
        $param = $this->_request;
        $address1 = $param['address1'];
        $address2 = $param['address2'];
        $address3 = $param['address3'];
        $detailAddress = $param['detailAddress'];
        $startTime = $param['startTime'];
        $userName = $param['userName'];
        $tel = $param['tel'];
        $notice = $param['notice'];
        
        //0) insert RegionInfo
        $query = "INSERT INTO regionInfo(address1, address2, address3, detailAddress, startTime) VALUES ('$address1', '$address2', '$address3', '$detailAddress', '$startTime');";
        $result = mysqli_query($connection, $query);
        if($result){
            $regionKey = mysqli_insert_id($connection);
            array_push($insertKeyArray, $regionKey);
        } else {
            die("Connection failed in regionInfo table: " . mysqli_error($connection));
        }
        
        //1) insert UserInfo
        $query = "INSERT IGNORE INTO userinfo (userName, tel) VALUES ('$userName', '$tel');";
        $resutl = mysqli_query($connection, $query);
        if($result){
            $userKey = mysqli_insert_id($connection);
            if($userKey == '0' || $userKey == 0){
                //var_dump('$last_id_region :: 00');
                $query = "SELECT userId FROM userinfo WHERE tel='$tel'";
                $result = mysqli_query($connection, $query) or die("Connection failed in SELECT userId : " . mysqli_error($connection));
                if($result){
                    while($row = mysqli_fetch_assoc($result))
                    {
                        $userKey = $row["userId"];
                    }
                }
            }
            array_push($insertedKeyArray, $userKey);
        } else {
            die("Connection failed in userInfo table: " . mysqli_error($connection));
        }
        
        //3) insert Exercise
        $query_exercise = "INSERT INTO exercise(regionKey, userKey, notice) VALUE ('$insertedKeyArray[0]', '$insertedKeyArray[1]', '$notice');";
        $result = mysqli_query($connection, $query_exercise) or die("Connection failed in exercise table: " . mysqli_error($connection));
        if($result){
            $response = array(
                'status' => 1,
                'status_message' => 'Address Added Successfully.'
            );
        } else {
            $response = array(
                'status' => 0,
                'status_message' => 'Address Addition Failed.'
            );
        }
        
        $this->response(json_encode($response), 200, $connection);
    }
    //PUT
    public function edit_exercise( $id )
    {
        $connection = $this->connection;
        $regionInfoResult = '';
        $userInfoResutl = '';
        
        $param = $this->_request;
        $address1 = $param['address1'];
        $address2 = $param['address2'];
        $address3 = $param['address3'];
        $detailAddress = $param['detailAddress'];
        $startTime = $param['startTime'];
        $userName = $param['userName'];
        $tel = $param['tel'];
        
        $query="SELECT * FROM exercise WHERE id=".$id." LIMIT 1";
        $result = mysqli_query($connection, $query);
        $row = mysqli_fetch_assoc($result);
        
        $regionKey = $row["regionKey"];
        $userKey = $row["userKey"];
        
        $query="UPDATE regionInfo SET";
        $query.=", address1='$address1'";
        $query.=", address2='$address2'";
        $query.=", address3='$address3'";
        $query.=", detailAddress='$detailAddress'";
        $query.=", startTime='$startTime'";
        $query.=" WHERE regionId = $regionKey";
        $result = mysqli_query($connection, $query);
        if( $result ) {
            $regionInfoResult = 'regionInfo';
        }
        
        // 3) update userinfo with userKey
        $query="UPDATE userInfo SET";
        $query="userName='$userName'";
        $query=", tel='$tel'";
        $query=" WHERE userId = $userKey";
        $result = mysqli_query($connection, $query);
        if( $result ){
            $userInfoResult = 'userInfo';
        }
        
        if( $regionInfoResult != '' || $userInfoResult != '' ){
            $response = array(
                'status' => 1,
                'status_message' => $regionInfoResult.' '.$userInfoResult.' Updated Successfully.'
            );
        } else {
            $response = array(
                'status' => 0,
                'status_message' => 'Updated Failed'
            );
        }
        
        $this->response(json_encode($response), 200, $connection);
    }
    
    public function processApi(){
        |
    }
    
    
}








