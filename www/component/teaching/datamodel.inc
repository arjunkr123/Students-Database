<?php 
/* @var $model DataModel */

$model->addTable("SubjectTeaching")
	->addPrimaryKey("id")
	->addForeignKey("subject", "CurriculumSubject", true, false, true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	//->addInsertAccess("edit_curriculum", true) insert must be done through functionality, to avoid having an empty group
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$q = PNApplication::$instance->curriculum->getSubjectQuery($row["subject"]);
		PNApplication::$instance->curriculum->joinBatchPeriod($q, "CurriculumSubject", "period");
		PNApplication::$instance->curriculum->joinBatch($q, "BatchPeriod", "batch");
		$q->field("CurriculumSubject","name","subject_name");
		$q->field("BatchPeriod","name","period_name");
		$q->field("StudentBatch","name","batch_name");
		$r = $q->executeSingleRow();
		return "Groups and assigned teachers for subject ".toHTML($r["subject_name"])." in ".toHTML($r["period_name"])." of batch ".toHTML($r["batch_name"]);
	})
	;
$model->internalGetTable("CurriculumSubject")->addReadAccessFromForeignTable("SubjectTeaching");
$model->addTable("SubjectTeachingGroups")
	->addForeignKey("subject_teaching", "SubjectTeaching", true, false, true)
	->addForeignKey("group", "StudentsGroup", true, false, true)
	->setKey(array("subject_teaching", "group"))
	->addReadAccess("consult_curriculum", true)
	->addReadAccessFromForeignTable("SubjectTeaching")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

/* Teachers */

$model->addTable("TeacherDates")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, true)
	->addDate("start", false, false, "2004-01-01","end")
	->addDate("end", true, false, "start", null)
	->addReadAccess("consult_curriculum", true)
	->addReadFilter(function(&$q, $table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->onInsert(function($inserted){
		foreach ($inserted as $row) {
			$people_id = $row["fields_values"]["people"];
			$people = PNApplication::$instance->people->getPeople($people_id, true);
			$dates = SQLQuery::create()->bypassSecurity()->select("TeacherDates")->whereValue("TeacherDates","people",$people_id)->execute();
			if (count($dates) == 1) {
				PNApplication::$instance->news->post("students","curriculum",null,"activity","New teacher: <i>".toHTML($people["first_name"]." ".$people["last_name"])."</i>");
			} else {
				PNApplication::$instance->news->post("students","curriculum",null,"activity","New dates for teacher <i>".toHTML($people["first_name"]." ".$people["last_name"])."</i>: from ".$row["fields_values"]["start"].($row["fields_values"]["end"] <> null ? " to ".$row["fields_values"]["end"] : ""));
			}
		}
	})
	->onBeforeUpdate(function(&$keys_fields_values,$sm){
		foreach ($keys_fields_values as $updated) {
			$keys = $updated[0];
			$fields_values = $updated[1];
			foreach ($keys as $id) {
				$prev = SQLQuery::create()->bypassSecurity()->select("TeacherDates")->whereValue("TeacherDates","id",$id)->executeSingleRow();
				$people = PNApplication::$instance->people->getPeople($prev["people"], true);
				$message = "Update teaching dates of <i>".toHTML($people["first_name"]." ".$people["last_name"])."</i>: ";
				$first = true;
				if (isset($fields_values["start"]) && $fields_values["start"] <> $prev["start"]) {
					$first = false;
					$message .= "started on ".$fields_values["start"]." instead of ".$prev["start"];
				}
				if (isset($fields_values["end"]) && $fields_values["end"] <> $prev["end"]) {
					if ($first) $first = false; else $message .= ", ";
					if ($prev["end"] == null) $message .= "ended on ".$fields_values["end"];
					else if ($fields_values["end"] == null) $message .= "still teaching today (previously ended on ".$prev["end"].")";
					else $message .= "ended on ".$fields_values["end"]." instead of ".$prev["end"];
				}
				PNApplication::$instance->news->post("students","curriculum",null,"activity",$message);
			}
		}
	})
	;

$model->addTable("TeacherAssignment")
	->addForeignKey("subject_teaching", "SubjectTeaching", true, false, true, false)
	->addForeignKey("people", "People", true, false, true, false)
	->addInteger("hours", 16, 0, 2000, true, false)
	->addEnum("hours_type", array("Per week","Per period"), true)
	->setKey(array("subject_teaching","people"))
	->addReadAccess("consult_curriculum", true)
	->addReadFilter(function(&$q, $table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	// TODO news
	;
?>