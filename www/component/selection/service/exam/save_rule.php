<?php 
class service_exam_save_rule extends Service {
	
	public function getRequiredRights() { return array("manage_exam_rules"); }
	
	public function documentation() { echo "Save an eligibility rule for written exams"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>id</code>: rule's ID, or -1 for a new one</li>";
		echo "<li><code>parent</code>: parent rule's ID, or null for a root rule</li>";
		echo "<li><code>program</code>: program id or null for a new rule</li>";
		echo "<li><code>expected</code>: minimum total grade expected</li>";
		echo "<li><code>topics</code>: list of ExamEligibilityRuleTopic defining the subject or extract with it's coefficient</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "<code>id</code> the ID of the rule";
	}
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		$parent = $input["parent"];
		$program = @$input["program"];
		$topics = $input["topics"];
		
		SQLQuery::startTransaction();
		if ($id > 0) {
			SQLQuery::create()->updateByKey("ExamEligibilityRule", $id, array("expected"=>$input["expected"]));
		} else
			$id = SQLQuery::create()->insert("ExamEligibilityRule", array("parent"=>$parent,"expected"=>$input["expected"],"program"=>$program));
		
		$rows = SQLQuery::create()->select("ExamEligibilityRuleTopic")->whereValue("ExamEligibilityRuleTopic", "rule", $id)->execute();
		if (count($rows) > 0)
			SQLQuery::create()->removeRows("ExamEligibilityRuleTopic", $rows);
		$to_insert = array();
		foreach ($topics as $t) {
			if ($t["subject"] == "") $t["subject"] = null;
			if ($t["extract"] == "") $t["extract"] = null;
			if ($t["subject"] == null && $t["extract"] == null) {
				PNApplication::error("Invalid value");
				return;
			}
			$coef = floatval($t["coefficient"]);
			if ($coef <= 0) {
				PNApplication::error("Invalid coefficient");
				return;
			}
			array_push($to_insert, array(
				"rule"=>$id,
				"subject"=>$t["subject"] <> null ? $t["subject"] : null,
				"extract"=>$t["subject"] <> null ? null : $t["extract"],
				"coefficient"=>$coef
			));
		}
		SQLQuery::create()->insertMultiple("ExamEligibilityRuleTopic", $to_insert);
		
		if (PNApplication::$instance->selection->hasExamResults()) {
			PNApplication::$instance->selection->applyExamEligibilityRules();
		}
		
		if (PNApplication::hasErrors())
			return;
		SQLQuery::commitTransaction();
		echo "{id:".$id."}";
	}
	
}
?>