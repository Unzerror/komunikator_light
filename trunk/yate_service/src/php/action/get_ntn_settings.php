<?
if(!$_SESSION['user']) {
    echo (out(array("success"=>false,"message"=>"User is undefined"))); exit;} 

$total =  compact_array(query_to_array("SELECT count(*) FROM ntn_settings"));
if(!is_array($total["data"]))  echo out(array("success"=>false,"message"=>$total));
    
$sql=
<<<EOD
	SELECT 
	s.ntn_setting_id as id,
        s.param,
	s.value,
	s.description
	FROM ntn_settings s 
EOD;

$data =  compact_array(query_to_array($sql.get_sql_order_limit()));

if(!is_array($data["data"]))  echo out(array("success"=>false,"message"=>$data));
    
$obj=array("success"=>true);
$obj["total"] = $total['data'][0][0]; 
$obj["data"] = $data['data']; 
echo out($obj);
?>