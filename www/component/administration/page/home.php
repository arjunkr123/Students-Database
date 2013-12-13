<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('admin_page');");
		?>
		<div id='admin_page' icon='/static/administration/admin_32.png' title='Administration' page='/dynamic/administration/page/dashboard'>
		<?php
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof AdministrationPlugin)) continue;
			foreach ($c->getAdministrationPages() as $page) {
				echo "<span class='page_menu_item'>";
				echo "<a href='".$page->getPage()."' target='admin_page_content'><img src='".$page->getIcon16()."'/>".$page->getTitle()."</a>";
				echo "</span>";
			}
		} 
		?>
		</div>
		<?php 
	}
	
}
?>