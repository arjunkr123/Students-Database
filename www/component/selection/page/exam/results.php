<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_exam_results extends SelectionPage {
	public function getRequiredRights() { return array("can_access_selection_data"); }
	public function executeSelectionPage(){
		theme::css($this, "section.css");
		$this->requireJavascript("jquery.min.js");
		$this->requireJavascript("address_text.js");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->requireJavascript("data_list.js");
	?>
<style>
	table>tbody>tr>td {
		text-align: center;
	}
	table>tbody>tr.clickable_row:hover{
	background-color: #FFF0D0;
	background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
	}
	
	table>tbody>tr.selectedRow{
	background-color: #FFF0D0;
	background: linear-gradient(to bottom, #FFF0D0 0%, orange 100%);
	}
</style>
			
<!-- main structure of the exam results page -->
<div style="width:100%;height:100%;display:flex;flex-direction:column">
	<div class="page_title" style="flex:none">
		<?php if (PNApplication::$instance->user_management->hasRight("edit_exam_results")) { ?>
		<div style='float:right;font-size:10pt;'>
		<button class='action red' onclick='resetAll();'>
			Reset all results
		</button>
		</div>
		<?php } ?>
		<div style='float:right;font-size:10pt;'>
		<button class='action' onclick='exportResults(this);'>
			<img src='/static/excel/excel_16.png'/>
			Export results for analysis
		</button>
		<?php if (PNApplication::$instance->user_management->hasRight("edit_exam_results")) { ?>
		<br/><button class='action' onclick="popupFrame(null,'Manual import from Excel','/dynamic/selection/page/exam/manual_import_results',null,null,null,function(frame,popup){popup.onclose=function(){location.reload();}});">
			<img src='<?php echo theme::$icons_16["_import"];?>'/>
			Manual import from Excel
		</button>
		<?php } ?>
		</div>
		<img src='/static/transcripts/transcript_32.png'/>
		Written Exam Results
	</div>
	<div style="flex: 1 1 auto;display:flex;flex-direction:row;overflow:auto;">
		<div style="flex:none;width:50%;">
			<div style="padding:5px;padding-right:0px;">
				<div id="sessions_list" title='Exam sessions' icon="/static/calendar/calendar_16.png" css="soft">
			      	<?php 
			      	$campaign_id = PNApplication::$instance->selection->getCampaignId();
					$q = SQLQuery::create()->select("ExamCenter")
						->field("ExamCenter","name")
						->field("ExamCenter","id","center_id")
						->field("CalendarEvent","start")
						->field("CalendarEvent","end")
						->field("ExamCenterRoom","name","room_name")
						->field("ExamCenterRoom","id","room_id")
						->countOneField("Applicant","applicant_id","applicants")
						->join("ExamCenter","ExamSession",array("id"=>"exam_center"))
						->whereNotNull("ExamSession","event")
						->join("ExamSession","Applicant",array("event"=>"exam_session"))
						->field("ExamSession","event","session_id")
						->join("Applicant","ExamCenterRoom",array("exam_center_room"=>"id"))
						->whereNotNull("ExamCenterRoom", "id")
						->expression("SUM(case when `Applicant_$campaign_id`.`exam_attendance` IS NOT NULL then 1 else 0 end)", "nb_attendance_set")
						->expression("SUM(case when `Applicant_$campaign_id`.`exam_attendance` = 'Yes' then 1 else 0 end)", "nb_attendees")
						->expression("SUM(case when `Applicant_$campaign_id`.`exam_passer` IS NOT NULL AND `Applicant_$campaign_id`.`exam_attendance` = 'Yes' then 1 else 0 end)", "nb_results_entered")
						->expression("SUM(`Applicant_$campaign_id`.`exam_passer`)", "nb_passers")
						;
					PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
					$exam_sessions=$q->groupBy("ExamSession","event")->groupBy("ExamCenterRoom","id")->execute();
					?>
					<table class="grid" id="table_exam_results" style="width: 100%">
						<thead>
							<tr>
							      <th>Exam Session</th>
							      <th>Room</th>
							      <th>Applicants</th>
							      <th>Status</th>					      
							</tr>
						</thead>
						<tbody>
					<?php
					$exam_center_id=null;
					foreach($exam_sessions as $exam_session){
						$session_name=date("d M Y",$exam_session['start'])." (".date("h:ia",$exam_session['start'])." to ".date("h:ia",$exam_session['end']).")";
						$session_date_id = $this->generateID();
						if ($exam_center_id<>$exam_session['center_id']){ // Group for a same exam center
							$exam_center_id=$exam_session['center_id'];
							$center_row_id = $this->generateID();
							 ?>
							<tr class="exam_center_row" >
								<th colspan="4" id='<?php echo $center_row_id; ?>'><?php echo $exam_session['name'];?></th>
						    </tr><?php } //end of if statement ?> 
							<tr class="clickable_row" style="cursor: pointer" session_id="<?php echo $exam_session['session_id'];?>" room_id="<?php echo $exam_session['room_id'];?>" exam_center_id="<?php echo $exam_center_id;?>" center_row_id='<?php echo $center_row_id; ?>' date_id='<?php echo $session_date_id; ?>' > 
								<td id='<?php echo $session_date_id;?>'><?php echo $session_name; ?></td>
								<td><?php echo $exam_session['room_name']; ?></td>
								<td><?php echo $exam_session['applicants']; ?></td>
								<td><?php
								if ($exam_session["nb_attendance_set"] == 0) {
									echo "<span style='color:red'>No attendance set</span>";
								} else {
									if ($exam_session["nb_attendance_set"] < $exam_session['applicants']) {
										echo "<span style='color:orange'>Attendance missing for ".($exam_session['applicants'] - $exam_session["nb_attendance_set"])."</span>";
									} else {
										echo "<span style='color:green'>Attendance set for all</span>";
									}
									echo " (".$exam_session["nb_attendees"]." present)";
									echo ",<br/>";
									if ($exam_session["nb_results_entered"] == 0) {
										echo "<span style='color:red'>No result yet</span>";
									} else {
										if ($exam_session["nb_results_entered"] < $exam_session["nb_attendees"]) {
											echo "<span style='color:orange'>Results missing for ".($exam_session["nb_attendees"] - $exam_session["nb_results_entered"])."</span>";
										} else {
											echo "<span style='color:green'>All results entered</span>";
										}
										echo ", ";
										echo $exam_session["nb_passers"]." passer".($exam_session["nb_passers"]>1?"s":"");
									}
								} 
								?></td>
							</tr>
						<?php } // end of foreach statement ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!--List of applicants-->
		<div style="flex:none;width:50%;align-self:stretch;display:flex;flex-direction:column;">
			<div style="padding:5px;padding-right:0px;display:flex;flex-direction:column;flex:1 1 auto">		
				<div id="session_applicants" title='Applicants for selected session' icon="/static/selection/applicant/applicants_16.png" css="soft" fill_height='true' style='flex:1 1 auto;'>
					<div id="session_applicants_list" style="display:none"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="page_footer" style="flex:none">
		<?php if (PNApplication::$instance->user_management->hasRight("edit_exam_results")) { ?>
		<button id="edit_results_button" class="action" disabled="disabled" onclick="editResults();">Edit Results</button>
		<button id="remove_results_button" class="action red" disabled="disabled" onclick="resetSession();">Reset results</button>
		<?php } ?>
		<button id="export_session_results_button" class="action" disabled="disabled" onclick="exportSession(this);">Export results</button>
	</div>
</div>
<script type='text/javascript'>
/* global variable containing data about selected items */
var selected={};

/*
 * Create data list for showing applicants attached to an exam session
 */
function createDataList(campaign_id)
{
	new data_list(
		"session_applicants_list",
		"Applicant", campaign_id,
		[
			"Selection.ID",
			"Personal Information.First Name",
			"Personal Information.Last Name",
			"Personal Information.Gender",
			"Personal Information.Age",
			"Selection.Exam Attendance"
		],
		[{category:"Selection",name:"Exam Session",force:true,data:{values:[-1]}}],
		-1,
		"Personal Information.Last Name", true,
		function(list) {
			window.dl = list;
			list.grid.makeScrollable();

			var export_sunvote = document.createElement("BUTTON");
			export_sunvote.className = "flat";
			export_sunvote.innerHTML = "<img src='/static/selection/exam/sunvote_16.png'/> Export applicants list for Clickers";
			export_sunvote.onclick = function() {
				postToDownload("/dynamic/selection/service/exam/export_exam_session_applicants_to_sunvote", {session:selected["session_id"],room:selected["room_id"]});
			};
			list.addHeader(export_sunvote);
		}
	);
}

function initResults(){
	// Update the exam session info and applicants list boxes
	$("tr.clickable_row").click(function(){
		// display selected row 
		$(this).addClass("selectedRow");
		$(this).siblings().removeClass("selectedRow");
	      
		// get the exam session's data for the selected row
		selected["exam_center_id"]=this.getAttribute("exam_center_id");
		selected["session_id"]=this.getAttribute("session_id");
		selected["room_id"]=this.getAttribute("room_id");
		selected["exam_center_name"]=document.getElementById(this.getAttribute("center_row_id")).innerHTML;
		selected["session_date"]=document.getElementById(this.getAttribute("date_id")).innerHTML;
	      
		// update applicants list
		updateApplicantsList();

		document.getElementById("session_applicants_list").style.display = selected["session_id"] != null ? "" : "none";
		<?php if (PNApplication::$instance->user_management->hasRight("edit_exam_results")) { ?>
		document.getElementById('edit_results_button').disabled = selected["session_id"] != null ? "" : "disabled";
		document.getElementById('remove_results_button').disabled = selected["session_id"] != null ? "" : "disabled";
		<?php } ?>
		document.getElementById('export_session_results_button').disabled = selected["session_id"] != null ? "" : "disabled";
	});
}

function updateApplicantsList() {
	/* Check if the data list is already initialized */
	if (!window.dl) {
		setTimeout(function() { updateApplicantsList(); },10);
		return;
	}
	window.dl.resetFilters(true);
	window.dl.addFilter({category:"Selection",name:"Exam Session",force:true,data:{values:[selected["session_id"]]}});
	window.dl.addFilter({category:"Selection",name:"Exam Center Room",force:true,data:{values:[selected["room_id"]]}});
	window.dl.reloadData();
}

function editResults() {
	/* Check if an exam session row is selected */ 
	if(selected["session_id"] != null) {
		/* open a new window pop up for results edition */
		window.top.popupFrame(
			"/static/transcripts/grades_16.png",
			"Exam Session Results",
			"/dynamic/selection/page/exam/edit_results?session="+selected["session_id"]+"&room="+selected["room_id"],
			null,
			95, 95,
			function(frame, pop) {
				pop.onclose = function() { location.reload(); };
			}
		);
	}
}

function serviceReset(session_id, reset_attendance) {
	var locker = lockScreen(null,"Removing results of applicants...");
	service.json("selection","exam/reset_results",{session:session_id, attendance:reset_attendance},function(res) {
		unlockScreen(locker);
		location.reload();
	});
}

function resetSession() {
	if(!selected["session_id"]) return;
	require("popup_window.js",function() {
		var div = document.createElement("DIV");
		div.innerHTML = "<table><tr><td><img src='"+theme.icons_32.warning+"'/></td><td>You are going to remove the written exams results for applicants from the session done on <b>"+selected["session_date"]+"</b> in center <b>"+selected["exam_center_name"]+"</b> !<br/>Do you want also to reset the attendance ?</td></tr></table>";
		var p = new popup_window("Reset Written Exam Results",null,div);
		p.addIconTextButton(null,"Reset results AND attendance",'all',function() {
			serviceReset(null,true);
			p.close();
		});
		p.addIconTextButton(null,"Reset only the results, keep the attendance",'results_only',function() {
			serviceReset(null,false);
			p.close();
		});
		p.addCancelButton();
		p.show();
	});
}

function resetAll() {
	require("popup_window.js",function() {
		var div = document.createElement("DIV");
		div.innerHTML = "<table><tr><td><img src='"+theme.icons_32.warning+"'/></td><td>You are going to remove the written exams results for <b>all applicants</b> !<br/>Do you want also to reset the attendance ?</td></tr></table>";
		var p = new popup_window("Reset Written Exam Results",null,div);
		p.addIconTextButton(null,"Reset results AND attendance",'all',function() {
			serviceReset(null,true);
			p.close();
		});
		p.addIconTextButton(null,"Reset only the results, keep the attendance",'results_only',function() {
			serviceReset(null,false);
			p.close();
		});
		p.addCancelButton();
		p.show();
	});
}

function launchExport(all, session_id) {
	var locker = lockScreen();
	setLockScreenContentProgress(locker, 100, "Generating Excel file...", null, function(span, pb, sub) {
		service.json("application","create_temp_data",{value:'0'},function(res) {
			var temp_data_id = res.id;
			postToDownload("/dynamic/selection/service/exam/export_results",{progress_id:temp_data_id,all:all,session_id:session_id ? session_id : null},true);
			var refresh = function() {
				service.json("application","get_temp_data",{id:temp_data_id},function(res) {
					if (res.value == 'done' || res.value === null || isNaN(parseInt(res.value))) {
						unlockScreen(locker);
						return;
					}
					pb.setPosition(parseInt(res.value));
					refresh();
				});
			};
			refresh();
		});
	});
}

function exportResults(button) {
	require("context_menu.js",function() {
		var menu = new context_menu();
		menu.addTitleItem(null, "Export results from ALL exam centers for analysis");
		menu.addIconItem(null, "Including applicants who cheated or partially attended", function() {
			launchExport(true);
		});
		menu.addIconItem(null, "Only from applicants who fully attended and didn't cheat", function() {
			launchExport(false);
		});
		menu.showBelowElement(button);
	});
}

function exportSession(button) {
	if(!selected["session_id"]) return;
	require("context_menu.js",function() {
		var menu = new context_menu();
		menu.addTitleItem(null, "Export results from the selected exam session");
		menu.addIconItem(null, "Including applicants who cheated or partially attended", function() {
			launchExport(true, selected["session_id"]);
		});
		menu.addIconItem(null, "Only from applicants who fully attended and didn't cheat", function() {
			launchExport(false, selected["session_id"]);
		});
		menu.showBelowElement(button);
	});
}

sectionFromHTML('sessions_list');
sectionFromHTML('session_applicants');
createDataList(<?php echo $this->component->getCampaignId();?>);
initResults();

</script>
	<?php
	}
}
?>