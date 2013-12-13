<?php
	$join_address_column = null;
	$join_value = $id;
	if($type == "people"){
		$table_address = "People_address";
		$join_address_column = "people";
	}
		->field($table_address,"address","address_id")
		->field("Postal_address","country","country")
		->field("Postal_address","geographic_area","geographic_area")
		->field("Postal_address","street","street")
		->field("Postal_address","street_number","street_number")
		->field("Postal_address","building","building")
		->field("Postal_address","unit","unit")
		->field("Postal_address","additional","additional")
		->field("Postal_address","address_type","address_type")
		->join($table_address,"Postal_address",array("address"=>"id"))
		->where($table_address.".".$join_address_column." = '".SQLQuery::escape($join_value)."'");
	$addresses = $q_addresses->bypass_security()->execute();
	
			$q = SQLQuery::create()->select("People");
			$q->where_value("People","id",$id);
			DataModel::get()->getTable("People")->filterReadAccess($q, "People");
			$rows = $q->execute();
			if (count($rows) == 0) {
				PNApplication::error("Cannot access to this People");
				return;
			}
				
			$q = SQLQuery::create()->select("People");
			$q->where_value("People","id",$id);
			$rows = $q->execute();
			$rows = DataModel::get()->getTable("People")->filterRemoveAccess($rows);
			$rights['remove'] = count($rows) == 1;
				
			$q = SQLQuery::create()->select("People");
			$q->where_value("People","id",$id);
			$rows = $q->execute();
			$rows = DataModel::get()->getTable("People")->filterWriteAccess($rows);
			$rights['edit'] = count($rows) == 1;
				
			$rights['add'] = $rights['remove'];
		} else {
			$rights['remove'] = $rights['add'] = $rights['edit'] = true;
		}
	}