<?
need_user();

$table_name = 'extensions';
$values = array();
$data = json_decode($HTTP_RAW_POST_DATA);
$rows = array();
$values = array();
$pbx_values=array(); 

if ($data && !is_array($data)) $data = array($data);
    foreach ($data as $row) {
        $values = array();
        foreach ($row as $key=>$value)
            if ($key == 'group_name') $group = ($value==null)?'null':$value;
            else {
                if ($key == 'id') $extension_id = $value;
	        if (!in_array($key ,array('forward','forward_busy','forward_noanswer','noanswer_timeout')))
                $values[$key]="'$value'"; 
		else {
                $pbx_values[$key]="'$value'"; 
    		/*$sql="update pbx_settings set value = '$value' where extension_id = $extension_id and param = '$key'";
    		print_r(q	uery ($sql));
		/*$sql= "insert into pbx_settings (extension_id,param,value) values (select $extension_id,'$key' where not exists (select 1 from pbx_settings where extension_id = $extension_id and param = '$key'))";
    		query ($sql);
		*/
		};
            }
        $rows[] = $values;
    }

if ($pbx_values) 
	foreach ($pbx_values as $pbx_key=>$pbx_value) {
    		$sql="update pbx_settings set value = $pbx_value where extension_id = $extension_id and param = '$pbx_key'";
    		query ($sql);
    		$sql="insert into pbx_settings (extension_id,param,value) select $extension_id,'$pbx_key', $pbx_value from dual where not exists (select 1 from pbx_settings where extension_id = $extension_id and param = '$pbx_key' and value = $pbx_value)";
    		query ($sql);
}

$id_name = 'extension_id';
if ($group) $need_out = false; 
include ("update.php");


if (!$group) return;

$sql=
<<<EOD
	SELECT group_member_id,g.group_id FROM group_members gm 
	left join groups g on g.group = '$group'  
	where gm.extension_id = '$extension_id'			
EOD;

$rows = array();

$result = compact_array(query_to_array($sql));
if(!is_array($result['data']))  echo out(array('success'=>false,'message'=>$result));
$row = $result['data'][0]; 

if ($row) {
    $id_name = 'group_member_id';
    $rows[] = array('id'=>$row[0],'group_id'=>$row[1]);
    if ($group!='null') { 
        $action  = 'update_group_members';
        include ("update.php");
    }
    else {
        $action  = 'destroy_group_members';
        include ("destroy.php");
    }	
}
else {
    $rows[] = array('extension_id'=>"'$extension_id'",'group_id'=>" (SELECT group_id FROM groups WHERE groups.group = '$group') ");
    $action  = 'create_group_members';
    include ("create.php");
}
?>