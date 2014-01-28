<?php 
require_once("component/people/PeoplePlugin.inc");
class DisciplinePeoplePlugin implements PeoplePlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$alias = $q->get_table_alias("Student");
		if ($alias == null) {
			$alias = $q->table_id();
			$people_alias = $q->get_table_alias("People");
			$q->join($people_alias, "Student", array("id"=>"people"), $alias);
		}
		if ($q->get_field_alias("Student", "batch") <> "student_batch")
			$q->field($alias, "batch", "student_batch");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["student_batch"] == null) return array(); // not a student
		return array(
			"discipline"=>array("/static/discipline/discipline.png", "Discipline", "/dynamic/discipline/page/home?people=".$people_id, 50), // TODO page
		);
	}
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) { return array(); }
	
	
	public function getCreatePeopleSupportedTypes() {}
	public function isCreatePeopleAllowed($people_type) {}
	public function getCreatePeoplePages($people_types) {}
	public function createPeople($people_id, $people_types, $input, &$create_data) {}
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {}
	
	public function getPeopleImportDataDisplay() {}
	public function importPeopleData($input, $people_id) {}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) { return null; }
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
	
}
?>