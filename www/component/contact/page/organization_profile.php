<?php 
class page_organization_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute(){
		$id = $_GET["organization"];
		
		require_once("contact.inc");
		require_once("address.inc");
		
		if ($id <> -1) {
			$org = SQLQuery::create()->select("Organization")->where_value("Organization","id",$id)->execute_single_row();
			$creator = $org["creator"];
		} else {
			$creator = $_GET["creator"];
		}
		$all_types = SQLQuery::create()->select("Organization_type")->where_value("Organization_type", "creator", $creator)->execute();
		
		$org_structure = "{";
		if ($id <> -1) {
			$org_structure .= "id:".$org["id"];
			$org_structure .= ",name:".json_encode($org["name"]);
			$org_types = SQLQuery::create()->select("Organization_types")->where_value("Organization_types", "organization", $id)->execute();
			$org_structure .= ",types:[";
			$first = true;
			foreach ($org_types as $t) {
				if ($first) $first = false; else $org_structure .= ",";
				$org_structure .= $t["type"];
			}
			$org_structure .= "]";
			$org_structure .= ",contacts:".contacts_structure("organization", $id);
			$org_structure .= ",addresses:".addresses_structure("organization", $id);
			$points = SQLQuery::create()
				->select("Contact_point")
				->where_value("Contact_point", "organization", $id)
				->field("Contact_point", "designation")
				->join("Contact_point", "People", array("people"=>"id"))
				->field("People", "id", "people_id")
				->field("People", "first_name")
				->field("People", "last_name")
				->execute();
			$org_structure .= ",points:[";
			$first = true;
			foreach ($points as $p) {
				if ($first) $first = false; else $org_structure .= ",";
				$org_structure .= "{designation:".json_encode($p["designation"]).",people_id:".$p["people_id"].",first_name:".json_encode($p["first_name"]).",last_name:".json_encode($p["last_name"])."}";
			}
			$org_structure .= "]";
		} else {
			$org_structure .= "id:-1";
			$org_structure .= ",name:''";
			$org_structure .= ",types:[]";
			$org_structure .= ",contacts:[]";
			$org_structure .= ",addresses:[]";
			$org_structure .= ",points:[]";
		}
		$org_structure .= ",creator:".json_encode($creator);
		$org_structure .= ",existing_types:[";
		$first = true;
		foreach ($all_types as $t) {
			if ($first) $first = false; else $org_structure .= ",";
			$org_structure .= "{id:".$t["id"].",name:".json_encode($t["name"])."}";
		}
		$org_structure .= "]";
		$org_structure .= "}";
		$this->add_javascript("/static/contact/organization.js");
		$container_id = $this->generate_id();
		$this->onload("window.organization = new organization('$container_id',$org_structure,true);");
		echo "<div id='$container_id' style='margin:5px'></div>";
		?>
<!-- 		<table>
			<th style = "height:100px">
				<span  id = 'organization_title'></span>
			</th>
			<tr>
				<td style ='vertical-align:top;'>
					<span id='type'></span>
				</td>
				<td style ='vertical-align:top;'>
					<span  id='address'></span>
					<span  id='contact'></span>
				</td>
			</tr>
		</table> -->
		<?php
// 		$q = SQLQuery::create()->select("Organization")
// 				->field("id")
// 				->where("id = ".$id."");
// 		$exist = $q->execute();
// 		if(isset($exist[0]["id"])){
// 			require_once("contact.inc");
// 			contact($this,"organization","contact",$id);
// 			require_once("address.inc");
// 			address($this,"organization","address",$id);
// 		}
// 		require_once("organization_profile.inc");
// 		organization_profile($this,$id,"type","organization_title");
	}
	
}