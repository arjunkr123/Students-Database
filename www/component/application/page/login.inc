<?php
global $need_app_loading;
if (isset($_GET["destroy_session"]) && !isset($_POST["domain"])) {
	@session_destroy();
}
if (isset($_GET["uninstall"])) {
	unlink("install_config.inc");
	echo "<script type='text/javascript'>window.top.location.reload();</script>";
	return;
}
$message = null;
if (isset($_POST["domain"]) && isset($_POST["username"]) && isset($_POST["password"])) {
	try {
		setcookie("domain",$_POST["domain"],time()+30*24*60*60,"/dynamic/application/page/enter");
		setcookie("username",$_POST["username"],time()+30*24*60*60,"/dynamic/application/page/enter");
		if (isset($_POST["tz_offset"]))
			PNApplication::$instance->timezone_offset = $_POST["tz_offset"];
		$error = PNApplication::$instance->user_management->login($_POST["domain"], $_POST["username"], $_POST["password"]);
		if ($error === null && !PNApplication::hasErrors()) {
			echo "<script type='text/javascript'>";
			echo "window.top.set_loading_message('Starting application...');";
			echo "window.top.pnapplication.logged_in = true;";
			echo "window.top.pnapplication.onlogin.fire();";
			echo "location.href = 'enter';";
			echo "</script>";
			return;
		} else
			$message = "Authentication failed: ".$error;
	} catch (Exception $e) {
		$message = "Internal error while authenticating";
		PNApplication::error($e);
	}
} else if (isset($_POST["message"]))
	$message = $_POST["message"];
	
$this->onload("window.top.pn_loading_end();");

if (isset($_GET["page"]) && !isset($_GET["section"])) {
	// only the frame
	echo "<iframe id='application_frame' name='application_frame' src='".$_GET["page"]."' style='width:100%;height:100%;border:none;'></iframe>";
	return;
}
?>
<form method="post" style="width:100%;height:100%" onsubmit="login();return false;" name="login_form">
<input type="hidden" name="tz_offset" id="timezone_input"/>
<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center">
<?php
if (isset($_GET["from"])) {
	switch ($_GET["from"]) {
	case "inactivity": echo "You have been automatically logged out because of more than 30 minutes of inactivity."; break;
	}
}
if ($message <> null) {
	echo "<div class='error_box' style='flex:none;margin-bottom:20px;box-shadow: 2px 2px 2px 0px #A0A0A0;'><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> ".$message."</div>";
}
?>
<table class='login_table' style='flex:none'>
	<tr>
		<td colspan=3 class='login_table_header' align=center>
			PN Students Management Software
		</td>
	</tr>
	<tr><td rowspan=5 class='login_table_logo'>
		<img src='/static/application/logo.png'/>
	</td></tr>
	<tr align=left>
		<td>Domain</td>
		<td>
			<select name="domain">
				<?php
				$def_domain = PNApplication::$instance->local_domain;
				if (isset($_COOKIE["domain"])) $def_domain = $_COOKIE["domain"];
				foreach (PNApplication::$instance->getDomains() as $domain=>$descr)
					echo "<option value=\"".$domain."\"".($def_domain==$domain ? " selected" : "").">".$domain."</option>";
				?>
			</select>
		</td>
	</tr>
	<tr align=left>
		<td>Username</td>
		<td><input type="text" size=25 maxlength=100 name="username" value='<?php if (isset($_COOKIE["username"])) echo $_COOKIE["username"];?>'/></td>
	</tr>
	<tr align=left>
		<td>Password</td>
		<td><input type="password" size=25 maxlength=100 name="password"/></td>
	</tr>
	<tr align=left>
		<td></td>
		<td>
			<button onclick="login();" style="padding:2px 5px">
				Login
				<img src='/static/application/login.gif' style='vertical-align:bottom'/>
			</button>
			<input type='submit' style='position:absolute;top:-10000px'/>
		</td>
	</tr>
	<?php if ($need_app_loading) {?>
	<tr>
		<td colspan=3 class='login_table_footer' id='login_table_footer'>
		</td>
	</tr>
	<?php } ?>
</table>
<?php 
#DEV
?>
<table class='login_table' style='margin-top:20px;flex:none;'>
	<tr>
		<td colspan=2 class='login_table_header' align=center>
			<img src='/static/development/debug.png'/>
			Development
		</td>
	</tr>
	<tr><td colspan=2>
		<div class='info_box'>
			<img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom' style='vertical-align:bottom'/>
			Your version is a development version (<?php echo $pn_app_version;?>)
		</div>
	</td></tr>
	<tr><td valign=top>
		<!-- 
		<a href="#" onclick="resetDB();return false;">Reset Database</a><br/>
		<a href="#" onclick="cleanStorage();return false;">Clean storage</a><br/>
		<a href="#" onclick="exportStorage();return false;">Export storage</a><br/>
		-->
		<a href="#" onclick="DBTools();return false;">Database Tools</a><br/>
		<a href="/dynamic/development/page/check_code" target="_blank">Check code</a><br/>
		<a href="/dynamic/documentation/page/index" target="_blank">Technical Documentation</a><br/>
		<a href="/dynamic/development/page/insert_data" target="_blank">Insert Data</a><br/>
		<a href="/dynamic/test/page/home" target="_blank">Tests</a><br/>
		<a href="?destroy_session=true">Destroy session</a><br/>
		<a href="?uninstall=true">Reset installation</a><br/>
		<a href="/deploy/" target="_blank">Deploy new version</a><br/>
	</td><td valign=top>
		External libraries:<ul>
			<li>tinyMCE: <span id='library_tinymce'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>PHPExcel: <span id='library_phpexcel'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>mPDF: <span id='library_mpdf'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>Google OAuth2: <span id='library_google_oauth2'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>Google Calendar: <span id='library_google_calendar'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>Google Plus: <span id='library_google_plus'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
			<li>Google PHP API: <span id='library_google_php_api'><img src='<?php echo theme::$icons_16["loading"];?>' style='vertical-align:bottom;'/></span></li>
		</ul>
	</td></tr>
</table>
<script type="text/javascript">
function resetDB() {
	popupFrame(null,"Reset Database", "/dynamic/development/page/reset_db?domain=<?php echo PNApplication::$instance->local_domain;?>", null, 90, 90, function(frame,pop) {
		pop.onclose = function() { window.top.location.reload(); };
	});
}
function DBTools() {
	popupFrame(null,"Database Tools", "/dynamic/development/page/db_tools", null, 90, 90, function(frame,pop) {
		pop.onclose = function() { window.top.location.reload(); };
	});
}
function cleanStorage() {
	popupFrame(null,"Clean Storage", "/dynamic/development/page/clean_storage", null, 90, 90, function(frame,pop) {
	});
}
function exportStorage() {
	popupFrame(null,"Export Storage", "/dynamic/development/page/export_storage", null, 90, 90, function(frame,pop) {
	});
}
var libraries = ["tinymce","phpexcel","mpdf","google_oauth2","google_calendar","google_plus","google_php_api"];
function checkLibraryUpdates(lib) {
	service.json("development","check_libraries_updates",{library:lib},function(res) {
		var span = document.getElementById('library_'+lib);
		if (!res)
			span.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Unable to get latest version";
		else if (res.error)
			span.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res.error;
		else {
			if (res.latest == res.current)
				span.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Up to date ("+res.latest+")";
			else
				span.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> Version "+res.latest+" available (currently in use: "+res.current+")";
		}
	});
}
for (var i = 0; i < libraries.length; ++i) checkLibraryUpdates(libraries[i]);
<?php if (rand(0,3)==0) { ?>
setTimeout(function() {
	service.json("administration","store_stats",null,function(res){});
},1000);
<?php } ?>
</script>
<?php
#END
#CHANNEL_BETA
#echo "<div class='info_box' style='margin-top:10px'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> You are using a <i>Beta</i> version, this should be for testing purpose only</div>";
#END
?>
</form>
<style type="text/css">
.login_table {
	border: 1px solid #22bbea;
	border-spacing: 0;
	box-shadow: 0 0 5px 3px #A0A0A0;
	border-radius: 10px;
	background: linear-gradient(to bottom, #FFFFFF 0%, #FFFFFF 30%, #E0E0E0 100%);
}
.login_table td.login_table_header {
	background-color: #22bbea;
	padding: 2px 10px 2px 10px;
	font-size: 12pt;
	font-weight: bold;
	color: white;
	border-radius: 8px 8px 0px 0px;
}
.login_table td.login_table_footer {
	border-top: 1px solid #a0d0f0;
	padding: 2px 10px 2px 10px;
	height: 10px;
	font-size: 8pt;
	text-align: center;
	border-radius: 0px 0px 8px 8px;
}
.login_table td {
	padding: 5px;
}
.login_table input, .login_table select {
	border: 1px solid #808080;
}
</style>
<script type='text/javascript'>
document.getElementById("timezone_input").value = -(new Date().getTimezoneOffset());
window.top.php_session_cookie_name = <?php echo json_encode(ini_get("session.name"));?>;
window.top.php_session_id = getCookie(window.top.php_session_cookie_name);
window.top.php_server = new URL(location.href);
window.top.php_server_port = window.top.php_server.port;
window.top.php_server = window.top.php_server.host;
window.top.pn_version = getCookie("pnversion");
window.top.require("documents.js",function(){
	window.top.pndocuments.connected_port = -1;
	window.top.pndocuments.connect();
});
if (window.top.pnapplication)
	window.top.pnapplication.onlogout.fire();
function login() {
	window.no_loading = true;
	window.top.pn_loading_start();
	if (window.loading && window.loading.stop) window.loading.stop();
	window.top.set_loading_message("Authentication on "+document.forms['login_form'].elements['domain'].value+"...");
	document.forms["login_form"].onsubmit = null;
	document.forms["login_form"].submit();
}
document.forms["login_form"].elements["username"].focus();
<?php if ($need_app_loading) {?>
require("load_static.js",function(){
	setTimeout(function() {
		if (window.no_loading) return;
		window.loading = new load_static_resources('login_table_footer');
	},1000);
});
<?php } ?>
</script>