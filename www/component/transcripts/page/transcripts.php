<?php 
class page_transcripts extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function execute() {
		if (!isset($_GET["period"])) {
			echo "<div style='padding:5px'><div class='info_box'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> Please select a period, a specialization within a period, or a group of students</div></div>";
			return;
		}
		
		$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
		$period = PNApplication::$instance->curriculum->getBatchPeriod($_GET["period"], true);
		$spe = null;
		if (isset($_GET["specialization"]))
			$spe = PNApplication::$instance->curriculum->getSpecialization($_GET["specialization"]);
		
		$title = "Batch ".toHTML($batch["name"]).", Period ".toHTML($period["name"]);
		if ($spe <> null) $title .= ", Specialization ".toHTML($spe["name"]);
		
		if (isset($_GET["group"])) {
			$group = PNApplication::$instance->students_groups->getGroup($_GET["group"]);
			$groups = array($group);
		} else
			$groups = PNApplication::$instance->students_groups->getGroups(@$_GET["group_type"], $period["id"], $spe <> null ? $spe["id"] : null);
		if (count($groups) == 0) {
			$groups = PNApplication::$instance->students_groups->getGroups(1, $period["id"]);
			if (count($groups) == 0)
				echo "<div class='info_box'>No class in this period</div>";
			else
				echo "<div class='info_box'>Please select a specialization or a group of student</div>";
			return;
		}
		$groups_ids = array();
		foreach ($groups as $g) array_push($groups_ids, $g["id"]);
		
		$q = PNApplication::$instance->students_groups->getStudentsQueryForGroups($groups_ids);
		PNApplication::$instance->people->joinPeople($q, "StudentGroup", "people", false);
		$q->orderBy("People","last_name")->orderBy("People","first_name");
		$students = $q->execute();
		
		$published = SQLQuery::create()->select("PublishedTranscript")->whereValue("PublishedTranscript","period",$period["id"])->whereValue("PublishedTranscript","specialization",$spe <> null ? $spe["id"] : null)->execute();
		
		theme::css($this, "print.css");
?>
<style type='text/css'>
#transcripts_header {
	height:25px;
	background-color:white;
	border-bottom:1px solid #A0A0A0;
}
#published_list {
	height:25px;
	display: flex;
	flex-direction: row;
	align-items: flex-end;
	padding-left: 10px;
}
#published_list .menu_item {
	border: 1px solid #808080;
	border-bottom: 0px;
	border-top-left-radius: 3px;
	border-top-right-radius: 3px;
	padding: 3px;
	margin: 0px 5px;
	flex: none;
	cursor: pointer;
}
#published_list .menu_item:hover {
	background-color: #F0E0C0;
}
#published_list .menu_item.selected {
	background-color: #ff9933;
}
#students_list {
	background-color:white;
	border-top: 1px solid #A0A0A0;
	min-width: 175px;
}
.student {
	padding: 2px 1px;
	border-left: 5px solid #FFFFFF;
	cursor: default;
	margin-right: 5px;
	position:relative;
	white-space: nowrap;
}
.student:hover {
	background-color: #F0E0C0;
	border-left: 5px solid #C0B090;
}
.student:hover:after {
    position: absolute;
    display: inline-block;
    border-top: 10px solid transparent;
    border-left: 5px solid #F0E0C0;
    border-bottom: 10px solid transparent;
    border-left-color: #F0E0C0;
    right: -5px;
    top: 0px;
    content: '';
    z-index: 80;
}
.student.selected {
	background-color: #ff9933;
	border-left: 5px solid #CC6600;
}
.student.selected:after {
    position: absolute;
    display: inline-block;
    border-top: 10px solid transparent;
    border-left: 5px solid #ff9933;
    border-bottom: 10px solid transparent;
    border-left-color: #ff9933;
    right: -5px;
    top: 0px;
    content: '';
    z-index: 80;
}
</style>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none'>
		<img src='/static/transcripts/transcript_32.png'/>
		Published Transcripts
		<span style='margin-left:10px;font-size:12pt;font-style:italic;'>
		<?php echo $title;?>
		</span>
	</div>
	<div style='flex:1 1 auto;display:flex;flex-direction:row'>
		<div style='flex:none;display:flex;flex-direction:column;border-right: 1px solid #A0A0A0;'>
			<div style='flex:none;height:25px'></div>
			<div style='flex:1 1 auto;overflow-y:auto;' id='students_list'>
				<?php
				foreach ($students as $s)
					echo "<div class='student' onclick='selectStudent(".$s['people_id'].",this,".json_encode($s["last_name"]." ".$s["first_name"], JSON_HEX_APOS).");'>".toHTML($s["last_name"],null,"UTF-8")." ".toHTML($s["first_name"])."</div>";
				?>
			</div>
		</div>
		<div style='flex:1 1 auto;display:flex;flex-direction:column;'>
			<div id='transcripts_header' style='flex:none;'>
				<div style='float:right;height:100%;margin-right:5px;display:flex;flex-direction:row;align-items:center;'>
					<button id='print_button' disabled='disabled' onclick="printContent('transcript',null,'TOR_Batch_<?php echo $batch["name"];?>_<?php echo $period["name"];?>_'+selected_transcript_name+'_'+selected_student_name);"><img src='<?php echo theme::$icons_16["print"];?>'/> Print</button>
					<button id='print_all_button' disabled='disabled' onclick="printAll();"><img src='<?php echo theme::$icons_16["print"];?>'/> Print All</button>
				</div>
				<div id='published_list'>
				<?php 
				if (count($published) == 0)
					echo "<span style='font-style:italic;color:#C00000'><img src='".theme::$icons_10["error"]."' style='vertical-align:middle'/> There is no transcript published yet</span>";
				else foreach ($published as $p)
					echo "<span class='menu_item' onclick='selectTranscript(".$p["id"].",this,".json_encode($p["name"],JSON_HEX_APOS).");'>".toHTML($p['name'])."</span>";
				?>
				</div>
			</div>
			<div style='flex:1 1 auto;overflow:auto;text-align:center;'>
				<div id='transcript'>
				</div>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
var students_ids = [<?php 
$first = true;
foreach ($students as $s) {
	if ($first) $first = false; else echo ",";
	echo $s["people_id"];
}
?>];
var selected_student = null;
var selected_student_name = null;
var selected_transcript = null;
var selected_transcript_name = null;

function selectStudent(id,div,name) {
	var list = document.getElementById('students_list');
	for (var i = 0; i < list.childNodes.length; ++i)
		if (list.childNodes[i].nodeType == 1)
			removeClassName(list.childNodes[i], "selected");
	addClassName(div, "selected");
	selected_student = id;
	selected_student_name = name;
	refreshTranscript();
}

function selectTranscript(id,div,name) {
	var list = document.getElementById('published_list');
	for (var i = 0; i < list.childNodes.length; ++i)
		if (list.childNodes[i].nodeType == 1)
			removeClassName(list.childNodes[i], "selected");
	addClassName(div, "selected");
	selected_transcript = id;
	selected_transcript_name = name;
	refreshTranscript();
}

function buildPages(content) {
	if (content.offsetHeight < 780) {
		addClassName(content, "page_layout");
		return;
	}
	var knowledge = [];
	var page_header = null;
	var page_footer = null;
	var sections = [];
	for (var i = 0; i < content.childNodes.length; ++i) {
		if (content.childNodes[i].nodeType != 1) continue;
		if (hasClassName(content.childNodes[i], "page_layout_header"))
			page_header = content.childNodes[i];
		else if (hasClassName(content.childNodes[i], "page_layout_footer"))
			page_footer = content.childNodes[i];
		else if (hasClassName(content.childNodes[i], "page_layout_section"))
			sections.push(content.childNodes[i]);
	}
	var fixed = page_header ? getHeight(page_header,knowledge) : 0;
	fixed += page_footer ? getHeight(page_footer,knowledge) : 0;
	// first page
	var h = fixed;
	var i = 0;
	do {
		h += getHeight(sections[i],knowledge);
		i++;
	} while (i < sections.length && h+getHeight(sections[i],knowledge) < 760);
	content.style.position = "relative";
	var pages_numbers = [];
	var f = document.createElement("DIV");
	f.style.position = "absolute";
	f.style.bottom = "5px";
	f.style.right = "5px";
	content.appendChild(f);
	pages_numbers.push(f);
	addClassName(content, "page_layout");
	// next pages
	var next = content.nextSibling;
	while (i < sections.length) {
		var page = document.createElement("DIV");
		page.style.position = "relative";
		page.className = content.className;
		if (page_header) {
			var header = document.createElement("DIV");
			header.className = page_header.className;
			header.innerHTML = page_header.innerHTML;
			page.appendChild(header);
		}
		h = fixed;
		do {
			h += getHeight(sections[i],knowledge);
			sections[i] = page.appendChild(sections[i]);
			i++;
		} while (i < sections.length && h+getHeight(sections[i],knowledge) < 760);
		if (page_footer) {
			var footer = document.createElement("DIV");
			footer.className = page_footer.className;
			footer.innerHTML = page_footer.innerHTML;
			page.appendChild(footer);
		}
		f = document.createElement("DIV");
		f.style.position = "absolute";
		f.style.bottom = "5px";
		f.style.right = "5px";
		page.appendChild(f);
		pages_numbers.push(f);
		if (next) content.parentNode.insertBefore(page, next);
		else content.parentNode.appendChild(page);
	}
	for (var i = 0; i < pages_numbers.length; ++i) {
		pages_numbers[i].innerHTML = "Page "+(i+1)+" / "+(pages_numbers.length);
	}
}
function makePageLayout(container) {
	var pages = [];
	for (var i = 0; i < container.childNodes.length; ++i) {
		var page = container.childNodes[i];
		if (page.nodeType != 1) continue;
		if (page.nodeName == "DIV") pages.push(page);
	}
	for (var i = 0; i < pages.length; ++i)
		buildPages(pages[i]);
}

function refreshTranscript() {
	var transcript = document.getElementById('transcript');
	var print_button = document.getElementById('print_button');
	var print_all_button = document.getElementById('print_all_button');
	print_button.disabled = "disabled";
	print_all_button.disabled = selected_transcript == null ? "disabled" : "";
	if (selected_student == null) {
		if (selected_transcript == null)
			transcript.innerHTML = "<div style='padding:20px;font-style:italic'>Please select a transcript and a student</div>";
		else
			transcript.innerHTML = "<div style='padding:20px;font-style:italic'>Please select a student</div>";
		return;
	} else if (selected_transcript == null) {
		transcript.innerHTML = "<div style='padding:20px;font-style:italic'>Please select a transcript</div>";
		return;
	}
	print_button.disabled = "";
	var locker = lockScreen(null, "Reloading transcript");
	service.html("transcripts","generate_transcript",{id:selected_transcript,student:selected_student},document.getElementById('transcript'),function() {
		unlockScreen(locker);
		makePageLayout(document.getElementById('transcript'));
	});
}
refreshTranscript();

function printAll() {
	var container = document.createElement("DIV");
	container.style.textAlign = "left";
	container.style.backgroundColor = "white";
	container.style.width = "630px";
	container.style.position = "absolute";
	container.style.top = "-10000px";
	container.style.visibility = "hidden";
	container.style.height = "300px";
	container.style.overflow = "hidden";
	document.body.appendChild(container);
	var nb = students_ids.length;
	var locker = lockScreen(null, "Generating transcripts...");
	setLockScreenContentProgress(locker, nb, "Generating transcripts...", false, function(span,pb){
		var checkEnd = function() {
			if (--nb > 0) return;
			setLockScreenContent(locker, "Preparation of pages for printing...");
			setTimeout(function() {
				printContent(container,function() {
					container.parentNode.removeChild(container);
					unlockScreen(locker);
				});
			},10);
		};
		for (var i = 0; i < students_ids.length; ++i) {
			var div = document.createElement("DIV");
			div.style.pageBreakAfter = "always";
			div.style.breakAfter = "always";
			//div.style.height = "810px";
			container.appendChild(div);
			service.html("transcripts","generate_transcript",{id:selected_transcript,student:students_ids[i],id_suffix:"_to_print"},div,function(div) {
				makePageLayout(div);
				pb.addAmount(1);
				checkEnd();
			});
		}
	});
}
</script>
<?php 
	}
	
}
?>