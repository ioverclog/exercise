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
                $subRegions = array('subRegion'=>$data['address3'], 'detailRegion'=>$data['detailAddress'].'<br />'.$data['startTime'], 'info' => array($member));)
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
            )
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    //INSERT
    
}








