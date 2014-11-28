<?php 
class service_picture_from_email extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		return "image/jpeg";
	}
	
	public function execute(&$component, $input) {
		$email = @$input["email"];
		if ($email == null && isset($_GET["email"])) $email = $_GET["email"];
		
		$people_id = SQLQuery::create()->bypassSecurity()
			->select("Contact")
			->whereValue("Contact","contact",$email)
			->whereValue("Contact","type","email")
			->join("Contact","PeopleContact",array("id"=>"contact"))
			->field("PeopleContact","people")
			->limit(0,1)
			->executeSingleValue();
		if ($people_id <> null) {
			$people = PNApplication::$instance->people->getPeople($people_id, true);
			if ($people["picture"] <> null) {
				$revision = PNApplication::$instance->storage->getRevision($people["picture"]);
				header("Location: /dynamic/storage/service/get?id=".$people["picture"]."&revision=".$revision);
				return;
			}
		}
		$i = strpos($email, "@");
		if ($i !== false) {
			$domain = substr($email, $i+1);
			if (strpos($email, "passerellesnumeriques.org") == strlen($email)-25) {
				require_once("component/google/lib_api/PNGoogleDirectory.inc");
				$google = new PNGoogleDirectory();
				$picture = $google->getProfilePicture($email);
				if ($picture <> null) {
					echo $picture;
					return;
				}
			}
		}
		// TODO ?
		$sex = "M";
		if ($people_id <> null && $people <> null) $sex = $people["sex"];
		header("Location: /static/people/default_".($sex == "F" ? "female" : "male").".jpg");
	}
	
}
?>