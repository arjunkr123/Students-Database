<?php 
require_once("/../selection_page.inc");
class page_IS_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$page->add_javascript("/static/widgets/grid/grid.js");
		$page->add_javascript("/static/data_model/data_list.js");
		$page->onload("init_organizations_list();");
		$container_id = $page->generateID();
		$can_create = PNApplication::$instance->user_management->has_right("manage_information_session",true);

	?>
		<div id = '<?php echo $container_id; ?>' style = "width:100%; height:100%">
		</div>
		
		<script type='text/javascript'>
			function init_organizations_list() {
				new data_list(
					'<?php echo $container_id;?>',
					'Information_session',
					['Information Session.Name','Information Session.Date'],
					[],
					function (list) {
						list.addTitle(null, "Information Sessions List");
						var new_IS = document.createElement("DIV");
						new_IS.className = 'button';
						new_IS.innerHTML = "<img src='"+theme.icons_16.add+"'/> New Information Session";
						new_IS.onclick = function() {
							location.assign("/dynamic/selection/page/IS/profile");
						};
						list.addHeader(new_IS);
					}
				);
			}
			
			
			
		</script>
	
	<?php		
	}
	
}