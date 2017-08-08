<#1>
<?php

include_once './Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php';
ilDBUpdateNewObjectType::addRBACTemplate(
	'sess', 
	'il_sess_participant', 
	'Session participant template', 
	[
		ilDBUpdateNewObjectType::getCustomRBACOperationId('visible'),
		ilDBUpdateNewObjectType::getCustomRBACOperationId('read')
	]
);
?>
<#2>
<?php

// add new role entry for each session
$query = 'SELECT obd.obj_id,ref_id,owner  FROM object_data obd '.
	'join object_reference obr on obd.obj_id = obr.obj_id'.' '.
	'where type = '.$ilDB->quote('sess','text');
$res = $ilDB->query($query);
while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
{
	// add role entry
	$id = $ilDB->nextId("object_data");
	$q = "INSERT INTO object_data ".
		"(obj_id,type,title,description,owner,create_date,last_update) ".
		"VALUES ".
		"(".
		 $ilDB->quote($id, "integer").",".
		 $ilDB->quote('role', "text").",".
		 $ilDB->quote('il_sess_participant_'.$row->ref_id, "text").",".
		 $ilDB->quote('Participant of session obj_no.'.$row->obj_id, "text").",".
		 $ilDB->quote($row->owner, "integer").",".
		 $ilDB->now().",".
		 $ilDB->now().")";

	$ilDB->manipulate($q);
	
	// add role data
	$rd = 'INSERT INTO role_data (role_id) VALUES ('.$id.')';
	$ilDB->manipulate($rd);
	
	// assign to session
	$fa = 'INSERT INTO rbac_fa (rol_id,parent,assign,protected,blocked ) VALUES('.
		$ilDB->quote($id,'integer').', '.
		$ilDB->quote($row->ref_id,'integer').', '.
		$ilDB->quote('y','text').', '.
		$ilDB->quote('n','text').', '.
		$ilDB->quote(0,'integer').' '.
		')';

	$ilDB->manipulate($fa);
	
	// assign template permissions
	$temp = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) VALUES('.
		$ilDB->quote($id,'integer').', '.
		$ilDB->quote('sess','text').', '.
		$ilDB->quote(2,'integer').', '.
		$ilDB->quote($row->ref_id,'integer').') ';
	$ilDB->manipulate($temp);
	
	// assign template permissions
	$temp = 'INSERT INTO rbac_templates (rol_id,type,ops_id,parent) VALUES('.
		$ilDB->quote($id,'integer').', '.
		$ilDB->quote('sess','text').', '.
		$ilDB->quote(3,'integer').', '.
		$ilDB->quote($row->ref_id,'integer').') ';
	$ilDB->manipulate($temp);
	
	// assign permission
	$pa = 'INSERT INTO rbac_pa (rol_id,ops_id,ref_id) VALUES('.
		$ilDB->quote($id,'integer').', '.
		$ilDB->quote(serialize([2,3]),'text').', '.
		$ilDB->quote($row->ref_id,'integer').')';
	$ilDB->manipulate($pa);
	
	// assign users
	$users = 'SELECT usr_id from event_participants WHERE event_id = '.$ilDB->quote($row->obj_id,'integer');
	$user_res = $ilDB->query($users);
	while($user_row = $user_res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
	{
		$ua = 'INSERT INTO rbac_ua (usr_id,rol_id) VALUES('.
			$ilDB->quote($user_row->usr_id,'integer').', '.
			$ilDB->quote($id,'integer').')';
		$ilDB->manipulate($ua);
	}
		
}
?>
<#3>
<?php
$id = $ilDB->nextId("object_data");
$q = "INSERT INTO object_data ".
	"(obj_id,type,title,description,owner,create_date,last_update) ".
	"VALUES ".
	"(".
	 $ilDB->quote($id, "integer").",".
	 $ilDB->quote('rolt', "text").",".
	 $ilDB->quote('il_sess_status_closed', "text").",".
	 $ilDB->quote('Closed session template','text').', '.
	 $ilDB->quote(0, "integer").",".
	 $ilDB->now().",".
	 $ilDB->now().")";

$ilDB->manipulate($q);

$query = "INSERT INTO rbac_fa VALUES (".$ilDB->quote($id).", 8, 'n', 'n', 0)";
$ilDB->manipulate($query);
	
?>
<#4>
<?php
$id = $ilDB->nextId('didactic_tpl_settings');
$query = 'INSERT INTO didactic_tpl_settings (id,enabled,type,title, description,info,auto_generated,exclusive_tpl) values( '.
	$ilDB->quote($id, 'integer').', '.
	$ilDB->quote(1,'integer').', '.
	$ilDB->quote(1,'integer').', '.
	$ilDB->quote('sess_closed','text').', '.
	$ilDB->quote('sess_closed_info','text').', '.
	$ilDB->quote('','text').', '.
	$ilDB->quote(1,'integer').', '.
	$ilDB->quote(0,'integer').' '.
	')';
$ilDB->manipulate($query);

$query = 'INSERT INTO didactic_tpl_sa (id, obj_type) values( '.
	$ilDB->quote($id, 'integer').', '.
	$ilDB->quote('sess','text').
	')';
$ilDB->manipulate($query);


$aid = $ilDB->nextId('didactic_tpl_a');
$query = 'INSERT INTO didactic_tpl_a (id, tpl_id, type_id) values( '.
	$ilDB->quote($aid, 'integer').', '.
	$ilDB->quote($id, 'integer').', '.
	$ilDB->quote(1,'integer').
	')';
$ilDB->manipulate($query);

$query = 'select obj_id from object_data where type = '.$ilDB->quote('rolt','text').' and title = '.$ilDB->quote('il_sess_status_closed','text');
$res = $ilDB->query($query);
while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
{
	$closed_id = $row->obj_id;
}

$query = 'INSERT INTO didactic_tpl_alp (action_id, filter_type, template_type, template_id) values( '.
	$ilDB->quote($aid, 'integer').', '.
	$ilDB->quote(3, 'integer').', '.
	$ilDB->quote(2,'integer').', '.
	$ilDB->quote($closed_id,'integer').
	')';
$ilDB->manipulate($query);


$fid = $ilDB->nextId('didactic_tpl_fp');
$query = 'INSERT INTO didactic_tpl_fp (pattern_id, pattern_type, pattern_sub_type, pattern, parent_id, parent_type ) values( '.
	$ilDB->quote($fid, 'integer').', '.
	$ilDB->quote(1, 'integer').', '.
	$ilDB->quote(1,'integer').', '.
	$ilDB->quote('.*','text').', '.
	$ilDB->quote($aid,'integer').', '.
	$ilDB->quote('action','text').
	')';
$ilDB->manipulate($query);

?>
<#5>
<?php
//
?>
<#6>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#7>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#8>
<?php

$sessions = [];

$query = 'select obd.obj_id, title, od.description from object_data obd left join object_description od on od.obj_id = obd.obj_id  where type = '.$ilDB->quote('sess','text');
$res = $ilDB->query($query);
while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
{
	$tmp['obj_id'] = $row->obj_id;
	$tmp['title'] = $row->title;
	$tmp['description'] = $row->description;
	
	$sessions[] = $tmp;
}

foreach($sessions as $idx => $sess_info)
{
	$meta_id = $ilDB->nextId('il_meta_general');
	$insert = 'INSERT INTO il_meta_general (meta_general_id, rbac_id, obj_id, obj_type, general_structure, title, title_language, coverage, coverage_language) '.
		'VALUES( '.
		$ilDB->quote($meta_id,'integer').', '.
		$ilDB->quote($sess_info['obj_id'],'integer').', '.
		$ilDB->quote($sess_info['obj_id'],'integer').', '.
		$ilDB->quote('sess','text').', '.
		$ilDB->quote('Hierarchical','text').', '.
		$ilDB->quote($sess_info['title'],'text').', '.
		$ilDB->quote('en','text').', '.
		$ilDB->quote('', 'text').', '.
		$ilDB->quote('en','text').' '.
		')';
		
	$ilDB->manipulate($insert);
	
	$meta_des_id = $ilDB->nextId('il_meta_description');
	$insert = 'INSERT INTO il_meta_description (meta_description_id, rbac_id, obj_id, obj_type, parent_type, parent_id, description, description_language) '.
		'VALUES( '.
		$ilDB->quote($meta_id,'integer').', '.
		$ilDB->quote($sess_info['obj_id'],'integer').', '.
		$ilDB->quote($sess_info['obj_id'],'integer').', '.
		$ilDB->quote('sess','text').', '.
		$ilDB->quote('meta_general','text').', '.
		$ilDB->quote($meta_id,'integer').', '.
		$ilDB->quote($sess_info['description'],'text').', '.
		$ilDB->quote('en','text').' '.
		')';
	$ilDB->manipulate($insert);
}
?>
<#9>
<?php

if(!$ilDB->tableExists('adv_md_record_scope'))
{
	$ilDB->createTable('adv_md_record_scope', array(
		'scope_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'record_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'ref_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
		)
	));
	$ilDB->addPrimaryKey('adv_md_record_scope', ['scope_id']);
	$ilDB->createSequence('adv_md_record_scope');
}
?>
<#10>
<?php

if( !$ilDB->tableExists('adv_md_values_extlink') )
{
	$ilDB->createTable('adv_md_values_extlink', array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'sub_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true,
			'default' => "-"
		),
		'sub_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'field_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'value' => array(
			'type' => 'text',
			'length' => 500,
			'notnull' => false
		),
		'title' => array(
			'type' => 'text',
			'length' => 500,
			'notnull' => false
		),
		'disabled' => [
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0
		]
		
	));
		
	$ilDB->addPrimaryKey('adv_md_values_extlink', array('obj_id', 'sub_type', 'sub_id', 'field_id'));
}
?>

