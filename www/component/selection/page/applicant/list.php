<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_applicant_list extends SelectionPage {
	
	public function getRequiredRights() { return array("see_applicant_info"); }
	
	/**
	 * Create a data_list of applicants
	 * The property of datalist can be given by a post "input" variable. This variable can be set using prepare_applicants_list.js script
	 */
	public function executeSelectionPage() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_list();");
		$this->addStylesheet("/static/selection/applicant/applicant_list.css");
		$container_id = $this->generateID();
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : array();
		$campaign_id = @$_GET["campaign"];
		if ($campaign_id == null) $campaign_id = PNApplication::$instance->selection->getCampaignId();
		
		$profile_page = null;
		$can_edit_applicants = PNApplication::$instance->user_management->hasRight("edit_applicants");
		$can_create = $can_edit_applicants;
		$can_assign_to_is = $can_edit_applicants;
		$can_assign_to_exam = $can_edit_applicants;
		$can_assign_to_interview = $can_edit_applicants;
		$can_exclude = $can_edit_applicants;
		$can_remove = $can_edit_applicants;
		$is_final = false;
		$final_with_programs = false;
		$filters = "";
		$forced_fields = array();
		if (isset($_GET["type"])) {
			switch ($_GET["type"]) {
				case "exam_passers":
					$can_create = false;
					$can_remove = false;
					$can_assign_to_is = false;
					$can_assign_to_exam = false;
					$filters = "filters.push({category:'Selection',name:'Eligible for Interview',force:true,data:{values:[1]}});\n";
					break;
				case "interview_passers":
					$can_assign_to_is = false;
					$can_assign_to_exam = false;
					$can_assign_to_interview = false;
					$can_create = false;
					$can_remove = false;
					$filters = "filters.push({category:'Selection',name:'Eligible for Social Investigation',force:true,data:{values:[1]}});\n";
					break;
				case "si":
					$can_assign_to_is = false;
					$can_assign_to_exam = false;
					$can_assign_to_interview = false;
					$can_create = false;
					$can_remove = false;
					$filters = "filters.push({category:'Selection',name:'Eligible for Social Investigation',force:true,data:{values:[1]}});\n";
					$profile_page = "Social Investigation";
					break;
				case "final":
					$can_assign_to_is = false;
					$can_assign_to_exam = false;
					$can_assign_to_interview = false;
					$can_exclude = false;
					$can_create = false;
					$can_remove = false;
					$final_with_programs = count($this->component->getPrograms()) > 1;
					$filters = "filters.push({category:'Selection',name:'Eligible for Social Investigation',force:true,data:{values:[1]}});\n";
					$filters .= "filters.push({category:'Selection',name:'Social Investigation Grade',force:true,data:{values:['NOT_NULL']}});\n";
					$filters .= "filters.push({category:'Selection',name:'Excluded',force:true,data:{values:[0]}});\n";
					array_push($forced_fields, array("Selection","Final Decision of PN"));
					if ($final_with_programs)
						array_push($forced_fields, array("Selection","Selected for program"));
					array_push($forced_fields, array("Selection","Final Decision of Applicant"));
					array_push($forced_fields, array("Selection","Reason of Applicant to decline"));
					if ($final_with_programs) {
						$batch = array();
						foreach ($this->component->getPrograms() as $program)
							if ($program["batch"] <> null)
								$batch[$program["batch"]] = PNApplication::$instance->curriculum->getBatch($program["batch"]);
					} else {
						$batch_id = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign","id",$this->component->getCampaignId())->field("batch")->executeSingleValue();
						if ($batch_id <> null)
							$batch = PNApplication::$instance->curriculum->getBatch($batch_id);
					}
					$is_final = true;
					break;
			}
		}
		?>
		<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div id='list_container' style="flex:1 1 auto"></div>
			<?php if (PNApplication::$instance->user_management->hasRight("edit_applicants")) {?>
			<div class='page_footer' style="flex:none;">
				<span id='nb_selected'>0 applicant selected</span>:
				<?php
				$nb_can_assign = 0;
				if ($can_assign_to_is) $nb_can_assign++;
				if ($can_assign_to_exam) $nb_can_assign++;
				if ($can_assign_to_interview) $nb_can_assign++;
				if ($nb_can_assign > 1) echo "Assign to ";
				?>
				<?php if ($can_assign_to_is) {?> 
				<button class='action' id='button_assign_is' disabled='disabled' onclick='assignIS(this);'><?php if ($nb_can_assign == 1) echo "Assign to ";?>Information Session</button>
				<?php }?>
				<?php if ($can_assign_to_exam) {?> 
				<button class='action' id='button_assign_exam_center' disabled='disabled' onclick='assignExamCenter(this);'><?php if ($nb_can_assign == 1) echo "Assign to ";?>Exam Center</button>
				<?php }?>
				<?php if ($can_assign_to_interview) {?> 
				<button class='action' id='button_assign_interview_center' disabled='disabled' onclick='assignInterviewCenter(this);'><?php if ($nb_can_assign == 1) echo "Assign to ";?>Interview Center</button>
				<?php }?>
				<?php if ($can_exclude) { ?> 
				<button class='action red' id='button_exclude' disabled='disabled' onclick="excludeStudents();">Exclude from the process</button>
				<?php }?>
				<?php if ($can_remove) { ?> 
				<button class='action red' id='button_remove' disabled='disabled' onclick="removeStudents();">Definitely Remove</button>
				<?php }?>
				<?php if ($is_final) { ?>
				<span style='white-space: nowrap'>
				<b><i>PN Decision:</i></b>
				<?php if ($final_with_programs) {
					foreach ($this->component->getPrograms() as $program)
						echo "<button class='action green' id='button_pn_selected_".$program["id"]."' disabled='disabled' onclick='PNSelected(".$program["id"].");'>Selected for ".toHTML($program["name"])."</button>";
					foreach ($this->component->getPrograms() as $program)
						echo "<button class='action' id='button_pn_waiting_list_".$program["id"]."' disabled='disabled' onclick='PNWaitingList(".$program["id"].");'>Waiting List for ".toHTML($program["name"])."</button>";
				} else { ?>
				<button class='action green' id='button_pn_selected' disabled='disabled' onclick='PNSelected();'>Selected!</button>
				<button class='action' id='button_pn_waiting_list' disabled='disabled' onclick='PNWaitingList();'>Waiting List</button>
				<?php } ?>
				<button class='action red' id='button_pn_no' disabled='disabled' onclick='PNNo();'>No</button>
				<button class='action grey' id='button_pn_remove' disabled='disabled' onclick='PNRemove();'>Remove Decision</button>
				</span>
				<span style='white-space: nowrap'>
				<b><i>Applicant decision:</i></b>
				<button class='action green' id='button_app_yes' disabled='disabled' onclick='ApplicantYes();'>Yes</button>
				<button class='action red' id='button_app_no' disabled='disabled' onclick='ApplicantNo();'>No</button>
				<button class='action grey' id='button_app_remove' disabled='disabled' onclick='ApplicantRemove();'>Remove Decision</button>
				</span>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
		<script type='text/javascript'>
		var dl;
		var filters = <?php if (isset($input["filters"])) echo json_encode($input["filters"]); else echo "[]"; ?>;
		<?php echo $filters; ?>
		function init_list() {
			dl = new data_list(
				'list_container',
				'Applicant', <?php echo $campaign_id;?>,
				[
					'Selection.ID',
					'Personal Information.First Name',
					'Personal Information.Last Name',
					'Personal Information.Gender',
					'Personal Information.Birth Date',
					'Personal Information.Address.0',
					'Personal Information.Address.1',
					'Selection.Information Session',
					'Selection.Exam Center',
					'Selection.Interview Center'
				],
				filters,
				<?php echo isset($_GET["all"]) ? "-1" : "100"; ?>,
				'Personal Information.Last Name',
				true,
				function (list) {
					 // make the info available to be able to change the color
					list.alwaysGetField('Selection','Excluded');
					list.setRowClassProvider(function(sent_fields, received_values) {
						var index;
						for (index = 0; index < sent_fields.length; ++index)
							if (sent_fields[index].name == "Excluded" && sent_fields[index].category == "Selection") break;
						if (received_values[index].v == 1)
							return "excluded_applicant"; // excluded in red
						return null;
					});
					list.grid.makeScrollable();
					var get_creation_data = function() {
						var data = {
							sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>},
							prefilled_data:[]
						};
						var filters = list.getFilters();
						for (var i = 0; i < filters.length; ++i) {
							if (filters[i].category == "Selection") {
								if (filters[i].name == "Information Session") {
									if (filters[i].data.values.length == 1 && filters[i].data.values[0] != 'NULL' && filters[i].data.values != 'NOT_NULL')
										data.prefilled_data.push({table:"Applicant",data:"Information Session",value:filters[i].data.values[0]});
								}
							}
						}
						return data;
					};
					list.addTitle("/static/selection/applicant/applicants_16.png", <?php if (isset($input["title"])) echo json_encode($input["title"]); else echo "'Applicants'";?>);

					<?php if (PNApplication::$instance->user_management->hasRight("edit_applicants") && $can_create) {?>
					var create_applicant = document.createElement("BUTTON");
					create_applicant.className = "flat";
					create_applicant.innerHTML = "<img src='"+theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add)+"' style='vertical-align:bottom'/> Create Applicant";
					create_applicant.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('New Applicant', theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add), "");
							var frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=applicant&not_from_existing=true&ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(create_applicant);

					var import_applicants = document.createElement("BUTTON");
					import_applicants.className = "flat";
					import_applicants.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Applicants";
					import_applicants.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('Import Applicants', theme.icons_16._import, "");
							var frame = p.setContentFrame("/dynamic/selection/page/applicant/popup_import?ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(import_applicants);
					<?php } ?>

					<?php if ($is_final && PNApplication::$instance->user_management->hasRight("manage_selection_campaign")) {
						if ($final_with_programs) {
							foreach ($this->component->getPrograms() as $program) {
								if ($program["batch"] == null) { ?>
									var create_batch = document.createElement("BUTTON");
									create_batch.className = "flat";
									create_batch.style.fontWeight = "bold";
									create_batch.innerHTML = "<img src='/static/curriculum/batch_16.png'/> Create a Batch with list of "+<?php echo json_encode(toHTML($program["name"])); ?>;
									create_batch.onclick = function() {
										popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "Create Batch from Selection", "/dynamic/selection/page/create_batch?ondone=reloadPage&program=<?php echo $program["id"];?>", null, null, null, function(frame,popup) {
											frame.reloadPage = function() {
												location.reload();
											};
										});
									};
									list.addHeader(create_batch);
									<?php	
								} else { ?>
									var update_batch = document.createElement("BUTTON");
									update_batch.className = "flat";
									update_batch.style.fontWeight = "bold";
									update_batch.innerHTML = "<img src='/static/curriculum/batch_16.png'/> Update Batch <?php echo toHTML($batch[$program["batch"]]["name"]); ?> with "+<?php echo json_encode(toHTML($program["name"])); ?>;
									update_batch.onclick = function() {
										popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "Update Batch from Selection", "/dynamic/selection/page/update_batch_confirm?batch=<?php echo $program["batch"];?>&program=<?php echo $program["id"];?>", null, null, null, function(frame,popup) {
											frame.confirmed = function() {
												var locker = lockScreen(null, "Updating batch of students...");
												service.json("selection","update_batch",{batch:<?php echo $program["batch"];?>,program:<?php echo $program["id"];?>},function(res) {
													unlockScreen(locker);
												});
											};
										});
									};
									list.addHeader(update_batch);
									var unlink_batch = document.createElement("BUTTON");
									unlink_batch.className = "flat";
									unlink_batch.style.fontWeight = "bold";
									unlink_batch.innerHTML = "<img src='"+theme.icons_16.unlink+"'/> Unlink "+<?php echo json_encode(toHTML($program["name"])); ?>+" with Batch <?php echo toHTML($batch[$program["batch"]]["name"]); ?>";
									unlink_batch.onclick = function() {
										confirmDialog("Are you sure you want to unlink this selection process with this Batch ?<br/>All the applicants who were already included in the batch will be removed from the batch.",function(yes) {
											if (!yes) return;
											service.json("selection","unlink_batch",{program:<?php echo $program["id"];?>},function(res) {
												if (res) location.reload();
											});
										});
									};
									list.addHeader(unlink_batch);
									<?php 
								}
							}
						} else {
							if ($batch_id <> null) {?>
								var update_batch = document.createElement("BUTTON");
								update_batch.className = "flat";
								update_batch.style.fontWeight = "bold";
								update_batch.innerHTML = "<img src='/static/curriculum/batch_16.png'/> Update Batch <?php echo toHTML($batch["name"]); ?> with final list";
								update_batch.onclick = function() {
									popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "Update Batch from Selection", "/dynamic/selection/page/update_batch_confirm?batch=<?php echo $batch_id;?>", null, null, null, function(frame,popup) {
										frame.confirmed = function() {
											var locker = lockScreen(null, "Updating batch of students...");
											service.json("selection","update_batch",{batch:<?php echo $batch_id;?>},function(res) {
												unlockScreen(locker);
											});
										};
									});
								};
								list.addHeader(update_batch);
								var unlink_batch = document.createElement("BUTTON");
								unlink_batch.className = "flat";
								unlink_batch.style.fontWeight = "bold";
								unlink_batch.innerHTML = "<img src='"+theme.icons_16.unlink+"'/> Unlink this selection with Batch <?php echo toHTML($batch["name"]); ?>";
								unlink_batch.onclick = function() {
									confirmDialog("Are you sure you want to unlink this selection process with this Batch ?<br/>All the applicants who were already included in the batch will be removed from the batch.",function(yes) {
										if (!yes) return;
										service.json("selection","unlink_batch",{},function(res) {
											if (res) location.reload();
										});
									});
								};
								list.addHeader(unlink_batch);
							<?php } else { ?>
								var create_batch = document.createElement("BUTTON");
								create_batch.className = "flat";
								create_batch.style.fontWeight = "bold";
								create_batch.innerHTML = "<img src='/static/curriculum/batch_16.png'/> Create a Batch of students with final list";
								create_batch.onclick = function() {
									popupFrame(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add), "Create Batch from Selection", "/dynamic/selection/page/create_batch?ondone=reloadPage", null, null, null, function(frame,popup) {
										frame.reloadPage = function() {
											location.reload();
										};
									});
								};
								list.addHeader(create_batch);
							<?php }
						} 
					} ?>

					<?php if (PNApplication::$instance->user_management->hasRight("edit_applicants")) { ?>
					list.grid.setSelectable(true);
					list.grid.onselect = selectionChanged;
					<?php } ?>

					var has_forced_filter = false;
					for (var i = 0; i < filters.length; ++i)
						if (filters[i].force) { has_forced_filter = true; break; }
						else {
							var or = filters[i].or;
							while (or) { if (or.force) { has_forced_filter = true; break; } or = or.or; }
							if (has_forced_filter) break;
						}
					if (!has_forced_filter) {
						var predefined_filters = document.createElement("BUTTON");
						predefined_filters.className = "flat";
						predefined_filters.innerHTML = "<img src='/static/data_model/filter.gif'/> Pre-defined filters";
						predefined_filters.onclick = function() {
							var t=this;
							require("context_menu.js",function() {
								var menu = new context_menu();
								menu.addIconItem(null, "Show all applicants (remove all filters)", function() {
									list.resetFilters();
									list.reloadData();
								});
								menu.addIconItem(null, "Show only those still in the process (not excluded)", function() {
									list.removeFiltersOn('Selection','Excluded');
									list.removeFiltersOn('Selection','Exclusion Reason');
									list.addFilter({category:'Selection',name:'Excluded',data:{values:[0]}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only those excluded from the process", function() {
									list.removeFiltersOn('Selection','Excluded');
									list.removeFiltersOn('Selection','Exclusion Reason');
									list.addFilter({category:'Selection',name:'Excluded',data:{values:[1]}});
									list.reloadData();
								});
								menu.addSubMenuItem(null, "Show only those excluded because of", function(sub_menu, onready) {
									var f = list.getField("Selection", "Exclusion Reason");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val, function(ev, val) {
											list.removeFiltersOn('Selection','Excluded');
											list.removeFiltersOn('Selection','Exclusion Reason');
											list.addFilter({category:'Selection',name:'Exclusion Reason',data:{values:[val]}});
											list.reloadData();
										}, val);
									}
									onready();
								});
								menu.addSubMenuItem(null, "Show only those coming from a specific Information Session", function(sub_menu, onready) {
									var f = list.getField("Selection", "Information Session");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.removeFiltersOn('Selection','Information Session');
											list.addFilter({category:'Selection',name:'Information Session',data:{values:[val]}});
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addIconItem(null, "Show only those who are not assigned to any Information Session", function() {
									list.removeFiltersOn('Selection','Information Session');
									list.addFilter({category:'Selection',name:'Information Session',data:{values:["NULL"]}});
									list.reloadData();
								});
								menu.addSubMenuItem(null, "Show only those from a psecific Exam Center", function(sub_menu, onready) {
									var f = list.getField("Selection", "Exam Center");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.removeFiltersOn('Selection','Exam Center');
											list.addFilter({category:'Selection',name:'Exam Center',data:{values:[val]}});
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addIconItem(null, "Show only those who are not assigned to any Exam Center", function() {
									list.removeFiltersOn('Selection','Exam Center');
									list.addFilter({category:'Selection',name:'Exam Center',data:{values:["NULL"]}});
									list.reloadData();
								});
								menu.addSubMenuItem(null, "Show only those from a specific High School", function(sub_menu, onready) {
									var f = list.getField("Selection", "High School");
									for (var i = 0; i < f.filter_config.list.length; ++i) {
										var val = f.filter_config.list[i];
										sub_menu.addIconItem(null, val.name, function(ev, val) {
											list.removeFiltersOn('Selection','High School');
											list.addFilter({category:'Selection',name:'High School',data:[val]});
											list.reloadData();
										}, val.id);
									}
									onready();
								});
								menu.addIconItem(null, "Show only those who are not assigned to any High School", function() {
									list.removeFiltersOn('Selection','High School');
									list.addFilter({category:'Selection',name:'High School',data:["NULL"]});
									list.reloadData();
								});
								menu.addSubMenuItem(null, "Show only those followed by a specific NGO", function(sub_menu, onready) {
									var f = list.getField("Selection", "Following NGO");
									for (var i = 0; i < f.filter_config.list.length; ++i) {
										var val = f.filter_config.list[i];
										sub_menu.addIconItem(null, val.name, function(ev, val) {
											list.removeFiltersOn('Selection','Following NGO');
											list.addFilter({category:'Selection',name:'Following NGO',data:[val]});
											list.reloadData();
										}, val.id);
									}
									onready();
								});
								menu.addIconItem(null, "Show only those who are not followed by any NGO", function() {
									list.removeFiltersOn('Selection','Following NGO');
									list.addFilter({category:'Selection',name:'Following NGO',data:["NULL"]});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only if eligible for Interview (exam passers)", function() {
									list.removeFiltersOn('Selection','Eligible for Interview');
									list.addFilter({category:'Selection',name:'Eligible for Interview',data:{values:[1]}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only absent during exam", function() {
									list.removeFiltersOn('Selection','Exam Attendance');
									list.addFilter({category:'Selection',name:'Exam Attendance',data:{values:['No']}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only if eligible for Social Investigation (interview passers)", function() {
									list.removeFiltersOn('Selection','Eligible for Social Investigation');
									list.addFilter({category:'Selection',name:'Eligible for Social Investigation',data:{values:[1]}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only absent during interview", function() {
									list.removeFiltersOn('Selection','Exam Attendance');
									list.addFilter({category:'Selection',name:'Interview Attendance',data:{values:[0]}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only boys", function() {
									list.removeFiltersOn('Personal Information','Gender');
									list.addFilter({category:'Personal Information',name:'Gender',data:{values:['M']}});
									list.reloadData();
								});
								menu.addIconItem(null, "Show only girls", function() {
									list.removeFiltersOn('Personal Information','Gender');
									list.addFilter({category:'Personal Information',name:'Gender',data:{values:['F']}});
									list.reloadData();
								});
								menu.showBelowElement(t);
							});
						};
						list.addHeader(predefined_filters);
					}

					<?php if (@$_GET["type"] == "si") {?>
					var div = document.createElement("DIV");
					div.style.marginLeft = "5px";
					div.innerHTML = "Show only applicants ";
					var select = document.createElement("SELECT");
					var o;
					o = document.createElement("OPTION");
					o.value = 'all';
					o.text = "eligible for social investigation";
					select.add(o);
					o = document.createElement("OPTION");
					o.value = 'to_be_visited';
					o.text = "who have to be visited";
					select.add(o);
					o = document.createElement("OPTION");
					o.value = 'to_be_reviewed';
					o.text = "who have been visited, to be reviewed";
					select.add(o);
					o = document.createElement("OPTION");
					o.value = 'passers';
					o.text = "who passed the social investigation";
					select.add(o);
					div.appendChild(select);
					list.addHeader(div);
					select.onchange = function() {
						switch (this.value) {
						case "all":
							list.resetFilters();
							list.reloadData();
							break;
						case "to_be_visited":
							list.resetFilters();
							list.addFilter({
								category:'Selection',name:'Social Investigation Visits',
								data:{type:'null'},
								or: {
									category:'Selection',name:'Social Investigation Visits',
									data:{type:'>0',element_data:{type:'more_equals',value:parseSQLDate(dateToSQL(new Date()),true).getTime()/1000}}
								}
							});
							list.reloadData();
							break;
						case "to_be_reviewed":
							list.resetFilters();
							list.addFilter({category:'Selection',name:'Social Investigation Grade',data:{values:["NULL"]}});
							list.addFilter({category:'Selection',name:'Social Investigation Visits',data:{type:'not_null'}});
							list.addFilter({category:'Selection',name:'Social Investigation Visits',data:{type:'none',element_data:{type:'more_equals',value:parseSQLDate(dateToSQL(new Date()),true).getTime()/1000}}});
							list.reloadData();
							break;
						case "passers":
							var f = list.getField("Selection","Final Decision of PN");
							if (!list.isShown(f))
								list.showField(f,false,null,true);
							f = list.getField("Selection","Final Decision of Applicant");
							if (!list.isShown(f))
								list.showField(f,false,null,true);
							f = list.getField("Selection","Reason of Applicant to decline");
							if (!list.isShown(f))
								list.showField(f,false,null,true);
							list.resetFilters();
							list.addFilter({category:'Selection',name:'Social Investigation Grade',data:{values:["Priority 1 (A+)", "Priority 2 (A)", "Priority 3 (A-)", "Priority 4 (B+)", "Priority 5 (B)"]}});
							list.reloadData();
							break;
						}
					};
					<?php } ?>

					list.makeRowsClickable(function(row){
						window.top.popupFrame('/static/selection/applicant/applicant_16.png', 'Applicant', "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id)<?php if ($profile_page<>null) echo "+'&page=".urlencode($profile_page)."'";?>, {sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>}}, 95, 95); 
					});

					<?php 
					foreach ($forced_fields as $ff)
						echo "list.showField(list.getField(".json_encode($ff[0]).",".json_encode($ff[1])."),true,null,true);";
					?>
				}
			);
		}
		function reload_list() {
			dl.reloadData();
		};
		function selectionChanged(indexes, rows_ids) {
			document.getElementById('nb_selected').innerHTML = indexes.length+" applicant"+(indexes.length>1?"s":"")+" selected";
			<?php if ($can_assign_to_is) { ?>
			document.getElementById('button_assign_is').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			<?php if ($can_assign_to_exam) { ?>
			document.getElementById('button_assign_exam_center').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			<?php if ($can_assign_to_interview) { ?>
			document.getElementById('button_assign_interview_center').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			<?php if ($can_exclude) { ?>
			document.getElementById('button_exclude').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			<?php if ($can_remove) { ?>
			document.getElementById('button_remove').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			<?php if ($is_final) { ?>
			<?php if ($final_with_programs) {
				foreach ($this->component->getPrograms() as $program) {
					echo "document.getElementById('button_pn_selected_".$program["id"]."').disabled = indexes.length > 0 ? '' : 'disabled';";
					echo "document.getElementById('button_pn_waiting_list_".$program["id"]."').disabled = indexes.length > 0 ? '' : 'disabled';";
				}
			} else { ?>
			document.getElementById('button_pn_selected').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_pn_waiting_list').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
			document.getElementById('button_pn_no').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_pn_remove').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_app_yes').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_app_no').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_app_remove').disabled = indexes.length > 0 ? "" : "disabled";
			<?php } ?>
		}
		function getSelectedApplicantsIds() {
			var applicants_rows = dl.grid.getSelectionByRowId();
			var applicants_ids = [];
			for (var i = 0; i < applicants_rows.length; ++i)
				applicants_ids.push(dl.getTableKeyForRow("Applicant", applicants_rows[i]));
			return applicants_ids;
		}
		function assignIS(button) {
			require("assign_is.js", function() {
				assign_is(button, getSelectedApplicantsIds(), function() {
					reload_list();
				});
			});
		}
		function assignExamCenter(button) {
			popupFrame("/static/selection/exam/exam_center_16.png", "Assign applicants to an exam center", "/dynamic/selection/page/exam/assign_applicants_to_center?ondone=refreshList", {applicants:getSelectedApplicantsIds()}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		function assignInterviewCenter(button) {
			popupFrame("/static/selection/interview/interview_16.png", "Assign applicants to an interview center", "/dynamic/selection/page/interview/assign_applicants_to_center?ondone=refreshList", {applicants:getSelectedApplicantsIds()}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		function excludeStudents() {
			popupFrame(null, "Exclude applicants from the selection process", "/dynamic/selection/page/applicant/exclude?ondone=refreshList", {applicants:getSelectedApplicantsIds()}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		function removeStudents() {
			var applicants_ids = getSelectedApplicantsIds();
			confirmDialog(
				"You are requesting to definitely remove "+(applicants_ids.length > 1 ? applicants_ids.length+" applicants" : "an applicant")+" from the database.<br/>"+
				"This must be done only if it was a mistake when creating "+(applicants_ids.length > 1 ? "it" : "them")+".<br/>"+
				"Else you should use the functionality to exclude from the process.<br/>"+
				"<br/>"+
				"Do you confirm you want to definitely remove from the database ?",
				function(yes) {
					if (!yes) return;
					service.json("selection","applicant/remove",{applicants:applicants_ids},function(res) {
						if (res) reload_list();
					});
				}
			);
		}
		function PNSelected(program_id) {
			var locker = lockScreen();
			var cells = [{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'final_decision',value:'Selected'}]
			}];
			if (program_id) cells[0].values.push({column:'program',value:program_id});
			service.json("data_model","save_cells",{cells:cells},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		function PNWaitingList(program_id) {
			var locker = lockScreen();
			var cells = [{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'final_decision',value:'Waiting List'}]
			}];
			if (program_id) cells[0].values.push({column:'program',value:program_id});
			service.json("data_model","save_cells",{cells:cells},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		function PNNo() {
			var locker = lockScreen();
			service.json("data_model","save_cells",{cells:[{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'final_decision',value:'Rejected'},{column:'program',value:null}]
			}]},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		function PNRemove() {
			var locker = lockScreen();
			service.json("data_model","save_cells",{cells:[{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'final_decision',value:null},{column:'program',value:null}]
			}]},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		function ApplicantYes() {
			var locker = lockScreen();
			service.json("data_model","save_cells",{cells:[{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'applicant_decision',value:'Will come'}]
			}]},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		function ApplicantNo() {
			popupFrame(null, "Applicants declined", "/dynamic/selection/page/applicant/declined?ondone=refreshList", {applicants:getSelectedApplicantsIds()}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		function ApplicantRemove() {
			var locker = lockScreen();
			service.json("data_model","save_cells",{cells:[{
				table: 'Applicant',
				sub_model: <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys: getSelectedApplicantsIds(),
				values: [{column:'applicant_decision',value:null},{column:'applicant_not_coming_reason',value:null}]
			}]},function(res) {
				reload_list();
				unlockScreen(locker);
			});
		}
		</script>
		<?php 
	}
}
?>