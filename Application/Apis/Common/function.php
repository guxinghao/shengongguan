<?php
function getAdminName($id){
	$maps['admin_id']=$id;

	$admin=M("Admin")->where($maps)->find();

	return $admin['name'];
}

function replace($value){

	$value=explode("'", $value);

	return $value[1];
}

