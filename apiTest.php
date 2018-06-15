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
        
        $query = "SELECT *, GROUP_CONCAT( concat(userInfo.userName, '', userInfo.tel) SEPARATOR '<br />' ) as 'member' FROM exercise
                  INNER JOIN regionInfo ON exercise.regionKey = regionInfo.regionId
                  INNER JOIN userInfo ON exercise.userKey = userInfo.userId"
                  GROUP BY exercise.regionKey";
                  
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
        $topTitle = array('ㅇㅕㄴㄱㅗㅇㅈㅏㅇㄱㅐㅅㅓㄹ', 'title2');
        foreach($regionName as $key => $regionItem){
            //Top Data
            $member = array('name'=>'jo-yong-chan', 'tel'=>'010-3232-3232');
            $region = array('title'=>'goyang-bodoso', 'info'=>array($member));
            $topData = array(array('title'=>$topTitle[0], 'region'=>array($region)));
            
            //Region Data
            $regionData = array();
            foreach($response as $key => $data){
                if($data['address1'] == $regionItem['name']){
                    $subRegions = array('subRegion'=>$data['address3'], 'detailRegion'=>$data['detailAddress'].'<br />'.$data['startTime'], 'info' => array($data['member']));
                    $regionData[] = array('region'=>$data['address2'], 'subRegions'=>array($subRegions));
                }              
            }
            
            //end
            $stuff[$regionItem['name']] = array('topData'=>$topData, 'regionData'=>$regionData);
        }
        
        $regionArr = array();
        foreach($regionName as $key => $regionObj){
            $regionArr[] = $regionObj['name'];
        }
        $stuff['koreaRegionData'] = $regionArr;
        
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
        if($result === true){
            $regionKey = mysqli_insert_id($connection);
        } else {
            die("Connection failed in regionInfo table: " . mysqli_error($connection));
        }
        
        //1) insert UserInfo
        for($i=0;$i<count($userName);$i++)
        {
            $isMatch = false;
            $telCount = count($tel[$i]);
            for( $j=0; $j<count($telCount); $j++ ){
                $query = "SELECT * FROM userinfo where (tel LIKE '%".$tel[$i][$j]."%')";
                $result = mysqli_query($connection, $query);
                $row = mysqli_fetch_assoc($result);
                if( $row["userId"] ){
                    $rowArr = explode(",", $row["tel"]);
                    if( count($rowArr) < $telCount ){
                        $telValue = implode(',', $tel[$i]);
                        $query="UPDATE userinfo SET ";
                        $query.=" tel='$telValue'";
                        $query.=" WHERE userId = ".$row['userId'];
                        $result = mysqli_query($connection, $query) or die("Connection failed in userinfo update : " . mysqli_error($connection));
                    }
                    
                    array_push($insertedKeyArray, $row["userId"]);
                    $isMatch = true;
                    break;
                }
            }
            
            if( !$isMatch ) {
                $telephoneValue = implode(',', $tel[$i]);
                $query = "INSERT IGNORE INTO userinfo (userName, tel) VALUES ('$userName', '$tel');";
                $result = mysqli_query($connection, $query) or die("Connection failed in userInfo table: " . mysqli_error($connection));
                if($result){
                    $userKey = mysqli_insert_id($connection);
                    array_push($insertedKeyArray, $userKey);
                }
            }
        }
        
        //3) insert Exercise
        for( $k=0; $k<count($insertedKeyArray); $k++ ){
            $query_exercise = "INSERT INTO exercise(regionKey, userKey, notice) VALUES ('$regionKey', '$insertedKeyArray[$k]', '$notice');";
            $result = mysqli_query($connection, $query_exercise) or die("Connection failed in exercise table: " . mysqli_error($connection));
        
            if( $result ) {
                $exerciseResult = true;
            } else {
                $exerciseResult = false;
                break;
            }
        }
        
        if( $exerciseResult ){
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
        $userInfoResult = '';
        
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
        
        $query="UPDATE regionInfo SET ";
        $query.="address1='$address1'";
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
        $query="UPDATE userInfo SET ";
        $query.="userName='$userName'";
        $query.=", tel='$tel'";
        $query.=" WHERE userId = $userKey";
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
        switch( $this->get_method() ) {
            case 'GET':
                $getId = $this->_request["id"];
                if( !empty($getId) ){
                    $id = intval($getId);
                    $this->get_exercise($id);
                } else {
                    $this->get_exercise();
                }
                break;
            case 'POST':
                $this->insert_exercise();
                break;
            case 'DELETE':
                $deleteId = $this->_request["id"];
                if( !empty($deleteId) ) {
                    $id = intval($deleteId);
                    $this->delete_exercise($id);
                }
                break;
            case 'PUT':
                $editId = $this->_request["id"];
                if( !empty($editId) ){
                    $id = intval($editId);
                    $this->edit_exercise($id);
                }
                break;
            default:
                $this->response('', 405);
                break;
        }
    }
}

$api = new API;
$api->processApi ();








