<?php 
class service_exam_unassign_supervisor_from_session extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	public function documentation() {
	}
	public function inputDocumentation() {
		?>
		<?php
	}
	public function outputDocumentation() {
		?>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$session_id = $input["session_id"];
		$staff_id = @$input["staff_id"];
		$custom_id = @$input["custom_id"];
		SQLQuery::startTransaction();
		try {
			if($staff_id <> null){
				//Unassign a staff
				SQLQuery::create()
					->removeKeys("ExamSessionSupervisor", array(array("session" => $session_id, "staff" => $staff_id)));
			}
			if($custom_id <> null)
				//Unassign a custom supervisor
				SQLQuery::create()->removeKey("ExamSessionSupervisorCustom",$custom_id);
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::hasErrors()){
			SQLQuery::rollbackTransaction();
			echo "false";
		} else {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>