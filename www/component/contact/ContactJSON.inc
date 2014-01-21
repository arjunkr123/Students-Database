<?php 
class ContactJSON {

	public static function PostalAddressFromID($id) {
		$q = SQLQuery::create()->select("Postal_address")->where_value("Postal_address", "id", $id);
		self::PostalAddressSQL($q);
		$row = $q->execute_single_row();
		if ($row == null) return null;
		return self::PostalAddress($q, $row);
	}
	
	/**
	 * Prepares a SQL query to retrieve a PostalAddress, to be used with the method PostalAddress
	 * @param SQLQuery $q the query to update
	 */
	public static function PostalAddressSQL(&$q) {
		$alias = $q->get_table_alias("Postal_address");
		$area_alias = $q->get_table_alias("Geographic_area");
		if ($area_alias == null) {
			$area_alias = $q->table_id();
			$q->join($alias, "Geographic_area", array("geographic_area"=>"id"), $area_alias);
		}
		require_once("component/geography/GeographyJSON.inc");
		GeographyJSON::GeographicAreaTextSQL($q);
		$q
			->field($alias,"id","address_id")
			->field($alias,"country","country_id")
			->field($alias,"street","street")
			->field($alias,"street_number","street_number")
			->field($alias,"building","building")
			->field($alias,"unit","unit")
			->field($alias,"additional","additional")
			->field($alias,"address_type","address_type")
			;
	}
	
	/**
	 * Generate a JSON PostalAddress object 
	 * @param SQLQuery $q the query used
	 * @param array $row the row corresponding to the postal address
	 * @return string the PostalAddress object in JSON
	 */
	public static function PostalAddress($q, $row) {
		require_once("component/geography/GeographyJSON.inc");
		return
			"{".
				"id:".$row["address_id"].
				",country_id:".$row["country_id"].
				",geographic_area:".GeographyJSON.GeographicAreaText($q, $row).
				",street:".json_encode(@$row["street"]).
				",street_number:".json_encode(@$row["street_number"]).
				",building:".json_encode(@$row["building"]).
				",unit:".json_encode(@$row["unit"]).
				",additional:".json_encode(@$row["additional"]).
				",address_type:".json_encode(@$row["address_type"]).
			"}"
			;
	}

	/**
	 * Convert an array coming from a JSON structure, into an array ready to use with database (insert or update)
	 * @param array $json the JSON structure decoded into an associative array
	 * @return array the associative array with column name from the database
	 */
	public static function PostalAddress2DB($json) {
		return array(
			"id"=>$json["id"],
			"country"=>@$json["country_id"],
			"geographic_area"=>(isset($json["geographic_area"]) && isset($json["geographic_area"]["id"]) ? $json["geographic_area"]["id"] : null),
			"street"=>@$json["street"],
			"street_number"=>@$json["street_number"],
			"building"=>@$json["building"],
			"unit"=>@$json["unit"],
			"additional"=>@$json["additional"],
			"address_type"=>@$json["address_type"] 
		);
	}
	
	/**
	 * Prepares a SQL query to retrieve a Contact, to be used with the method Contact
	 * @param SQLQuery $q the query to update
	 */
	public static function ContactSQL(&$q) {
		$alias = $q->get_table_alias("Contact");
		$q
			->field($alias,"id","contact_id")
			->field($alias,"type","contact_type")
			->field($alias,"sub_type","contact_sub_type")
			->field($alias,"contact","contact_text")
			;
	}
	
	/**
	 * Generate a JSON Contact object 
	 * @param SQLQuery $q the query used
	 * @param array $row the row corresponding to the contact
	 * @return string the Contact object in JSON
	 */
	public static function Contact($q, $row) {
		return
			"{".
				"id:".$row["contact_id"].
				",type:".json_encode($row["contact_type"]).
				",sub_type:".json_encode(@$row["contact_sub_type"]).
				",contact:".json_encode(@$row["contact_text"]).
			"}"
			;
	}

}
?>