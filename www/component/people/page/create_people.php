<?php 
class page_create_people extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$input = json_decode($_POST["input"],true);
		$types = @$input["types"];
		if ($types == null || count($types) == 0) {
			PNApplication::error("Missing people types");
			return;
		}
		$types = array_merge($types);
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof PeoplePlugin)) continue;
			$supported_types = $c->getCreatePeopleSupportedTypes();
			for ($i = 0; $i < count($types); $i++) {
				if (!in_array($types[$i], $supported_types)) continue;
				if (!$c->isCreatePeopleAllowed($types[$i])) {
					PNApplication::error("You are not allowed to create a people of type '".$types[$i]."'.");
					return;
				}
				array_splice($types, $i, 1);
				$i--;
			}
		}
		if (count($types) <> 0) {
			foreach ($types as $type)
				PNApplication::error("Invalid people type '".$type."'");
			return;
		}
		$types = $input["types"];
		
		$this->add_javascript("/static/widgets/vertical_layout.js");
		?>
		<script type='text/javascript'>
		window.create_people = {
			<?php
			$first = true;
			foreach ($input as $name=>$value) {
				if ($first) $first = false; else echo ",";
				echo $name.": ".json_encode($value);
			}
			?>
		};
		window.create_people_sections = [];
		</script>
		<div id='wizard_page' style='width:100%;height:100%'>
			<div id='wizard_page_header' layout='fixed' class='wizard_header' style='padding-top:3px'>
				<img src="<?php echo $input["icon"];?>" style='vertical-align:bottom;padding-left:5px;padding-right:5px'/>
				<span id='wizard_page_title'><?php echo $input["title"];?></span>
			</div>
			<div id='wizard_page_content' layout='fill' style='overflow:auto'>
				<?php 
				$pages = array();
				foreach (PNApplication::$instance->components as $c) {
					if (!($c instanceof PeoplePlugin)) continue;
					$cpages = $c->getCreatePeoplePages($types);
					if ($cpages <> null)
					foreach ($cpages as $p) array_push($pages, $p);
				}
				usort($pages, "cmp_create_people_pages");
				foreach ($pages as $p) {
					?>
					<div class='section_with_title' style='float:left;margin:5px'>
						<div><img src='<?php echo $p[0];?>' style='vertical-align:bottom;padding-right:3px'/><?php echo $p[1];?></div>
						<div><?php include $p[2];?></div>
					</div>
					<?php
				}
				?>
				<br style='clear: both;'/>
			</div>
			<div id='wizard_page_footer' layout='fixed' class='wizard_buttons'>
				<button id='wizard_page_button_finish' disabled='disabled' onclick='wizard_page_finish();'>
					<img src="<?php echo theme::$icons_16["ok"];?>" style='vertical-align:bottom'/>
					Create
				</button>
			</div>
		</div>
		<script type='text/javascript'>
		function enable_wizard_page_finish(enabled) {
			document.getElementById('wizard_page_button_finish').disabled = enabled ? "" : "disabled";
		}
		function wizard_page_finish() {
			enable_wizard_page_finish(false);
			wizard_freeze();
			for (var i = 0; i < window.create_people_sections.length; ++i) {
				if (!window.create_people_sections[i].validate_and_save()) { 
					wizard_unfreeze();
					enable_wizard_page_finish(true);
					return;
				}
			}
			if (window.create_people.donotcreate) {
				var fct = eval('('+window.create_people.donotcreate+')');
				fct(window.create_people);
			} else
				service.json("people","create_people",window.create_people,function(res) {
					if (!res) {
						wizard_unfreeze();
						enable_wizard_page_finish(true);
						return;
					}
					window.create_people.people_id = res.id;
					if (window.create_people.redirect) {
						var u = new URL(window.create_people.redirect);
						u.params["people_id"] = res.id;
						window.location.href = u.toString();
					} else if (window.create_people.onsuccess) {
						var fct = eval('('+window.create_people.onsuccess+')');
						fct(window.create_people);
					}
				});
		}
		wizard_freeze_div = document.createElement("DIV");
		wizard_freeze_div.style.position = "absolute";
		wizard_freeze_div.style.backgroundColor = "rgba(192, 192, 192, 0.5)";
		wizard_freeze_div.innerHTML = "<table style='width:100%;height:100%'><tr><td align=center valign=middle id='wizard_freezer'></td></tr></table>";
		function wizard_freeze() {
			var frame = document.getElementById('wizard_page_content');
			wizard_freeze_div.style.top = absoluteTop(frame)+"px";
			wizard_freeze_div.style.left = absoluteLeft(frame)+"px";
			wizard_freeze_div.style.width = getWidth(frame)+"px";
			wizard_freeze_div.style.height = getHeight(frame)+"px";
			wizard_freeze_div.frozen = true;
			frame.parentNode.appendChild(wizard_freeze_div);
			document.getElementById('wizard_freezer').innerHTML = "";
		}
		function wizard_unfreeze() {
			if (wizard_freeze_div.frozen) {
				wizard_freeze_div.parentNode.removeChild(wizard_freeze_div);
				wizard_freeze_div.frozen = false;
			}
		}
		function wizard_freeze_message(msg) {
			document.getElementById('wizard_freezer').innerHTML = msg;
		}
		enable_wizard_page_finish(true);
		if (!window.parent.get_popup_window_from_frame || window.parent.get_popup_window_from_frame(window) == null)
			new vertical_layout('wizard_page');
		</script>
		<?php
	}
	
}

function cmp_create_people_pages($p1,$p2) {
	if ($p1[3] < $p2[3]) return -1;
	if ($p1[3] > $p2[3]) return 1;
	return 0;
}
?>