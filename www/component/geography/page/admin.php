<?php 
class page_admin extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('geography_page');");
		$this->add_javascript("/static/widgets/select/select.js");
		?>
		<div id='geography_page' icon='/static/geography/geography_32.png' title='Geography' page='/dynamic/geography/page/set_geography_area'>
			<div>
				Country to edit: <span id='select_country'></span>
			</div>
		</div>
		<script type='text/javascript'>
		var select_country = new select('select_country');
		<?php 
		$countries = SQLQuery::create()->select("Country")->order_by("Country", "name")->execute();
		foreach ($countries as $c)
			echo "select_country.add(".$c["id"].",\"<img src='/static/geography/flags/".strtolower($c["code"]).".png' style='vertical-align:bottom'/> ".htmlentities($c["name"])."\");\n";
		?>
		select_country.onchange = function() {
			document.getElementById('geography_page_content').src = '/dynamic/geography/page/set_geography_area?country='+select_country.getSelectedValue();
		};
		</script>
		<?php 
	}
	
}
?>