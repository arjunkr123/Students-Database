<?php 
class page_test extends Page {
	
	public function getRequiredRights() { return array(); }

	public function execute() {
		require_once("update_urls.inc");
		$c = curl_init("https://sourceforge.net/settings/mirror_choices?projectname=studentsdatabase&filename=updates/stable/versions.txt");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		set_time_limit(45);
		$result = curl_exec($c);
		$i = strpos($result, "<ul id=\"mirrorList\">");
		$mirrors = array();
		if ($i !== false) {
			$result = substr($result, $i);
			$i = strpos($result, "</ul>");
			if ($i !== false) {
				$result = substr($result, 0, $i);
				while (($i = strpos($result, "<li id=\"")) !== false) {
					$result = substr($result, $i+8);
					$i = strpos($result, "\"");
					if ($i !== false) {
						$id = substr($result,0,$i);
						$i = strpos($result, "<label");
						if ($i !== false) {
							$j = strpos($result, ">", $i);
							if ($j !== false) {
								$k = strpos($result, "</li>");
								if ($k !== false) {
									$name = substr($result, $j+1, $k-$j-1);
									$name = trim(str_replace("</label>","",$name));
									$mirrors[$id] = $name;
									$result = substr($result, $k+5);
								}
							}
						}
					}
				}
			}
		}
		var_dump($mirrors);
	}
	
// 	/** @var Google_Client */
// 	private $client;
	
// 	public function execute() {
// 		set_include_path(get_include_path() . PATH_SEPARATOR . realpath("component/google/lib_api"));
// 		// initialize Google client
// 		require_once("Google/Client.php");
// 		$this->client = new Google_Client();
// 		require_once("Google/IO/Curl.php");
// 		$io = new Google_IO_Curl($this->client);
// 		// TODO proxy
// 		$this->client->setIo($io);

// 		// authentication
// 		require_once("Google/Auth/AssertionCredentials.php");
// 		$this->client->setApplicationName("Students Management Software");
// 		$secrets = include("conf/google.inc");
// 		$key = file_get_contents("conf/".$secrets["service_key"]);
// 		$cred = new Google_Auth_AssertionCredentials(
// 			$secrets["service_account"],
// 			array('https://www.googleapis.com/auth/calendar'),
// 			$key
// 		);
// 		$this->client->setAssertionCredentials($cred);
// 		if($this->client->getAuth()->isAccessTokenExpired()) {
// 			$this->client->getAuth()->refreshTokenWithAssertion($cred);
// 		}
		
// 		switch (@$_POST["fct"]) {
// 			case "create_calendar": $this->createCalendar(); break;
// 			case "open_calendar": $this->openCalendar(); break;
// 			case "share_calendar": $this->shareCalendar(); break;
// 			case "calendar_list": $this->listCalendars(); break;
// 			default: $this->homePage(); break;
// 		}
// 	}
	
// 	private function homePage() {
// 		echo "<h1>Test Connection with Google</h1>";
// 		echo "<form method='POST'><input type='hidden' name='fct' value='calendar_list'/><input type='submit' value='Calendars'/></form>";
// 	}
	
// 	private function listCalendars() {
// 		// connect to Google Calendar service
// 		require_once("Google/Service/Calendar.php");
// 		$service = new Google_Service_Calendar($this->client);
// 		echo "<h1>Calendars</h1>";
// 		echo "<table>";
// 		echo "<tr><th>ID</th><th>Summary</th><th>Description</th><th>Location</th><th>Timezone</th><th></th></tr>";
		
// 		$calendarList = $service->calendarList->listCalendarList();
// 		while(true) {
// 			foreach ($calendarList->getItems() as $cal) {
// 				/* @var $cal Google_Service_Calendar_CalendarListEntry */
// 				echo "<tr>";
// 				echo "<td>".$cal->getId()."</td>";
// 				echo "<td>".$cal->getSummary()."</td>";
// 				echo "<td>".$cal->getDescription()."</td>";
// 				echo "<td>".$cal->getLocation()."</td>";
// 				echo "<td>".$cal->getTimeZone()."</td>";
// 				echo "<td>";
// 				echo "<form method='POST'><input type='hidden' name='fct' value='open_calendar'/><input type='hidden' name='calendar_id' value='".$cal->getId()."'/><input type='submit' value='Open'/></form>";
// 				echo "</td>";
// 				echo "</tr>";
// 			}
// 			$pageToken = $calendarList->getNextPageToken();
// 			if ($pageToken) {
// 				$optParams = array('pageToken' => $pageToken);
// 				$calendarList = $service->calendarList->listCalendarList($optParams);
// 			} else {
// 				break;
// 			}
// 		}
// 		echo "</table>";
// 		echo "<form method='POST'>";
// 		echo "<input type='hidden' name='fct' value='create_calendar'/>";
// 		echo "Create a new calendar:<br/>";
// 		echo "Summary <input type='text' name='summary'/><br/>";
// 		echo "Description <input type='text' name='description'/><br/>";
// 		echo "<input type='submit' value='Create'/>";
// 		echo "</form>";
// 		echo "<a href='/dynamic/development/page/test'>Back to home page</a>";
// 	}
	
// 	private function createCalendar() {
// 		// connect to Google Calendar service
// 		require_once("Google/Service/Calendar.php");
// 		$service = new Google_Service_Calendar($this->client);
// 		$cal = new Google_Service_Calendar_Calendar();
// 		$cal->setSummary($_POST["summary"]);
// 		$cal->setDescription($_POST["description"]);
// 		$service->calendars->insert($cal);
// 		echo "Calendar created.<br/>";
// 		echo "<form method='POST'><input type='hidden' name='fct' value='calendar_list'/><input type='submit' value='Back to calendars list'/></form>";
// 	}
	
// 	private function openCalendar() {
// 		// connect to Google Calendar service
// 		require_once("Google/Service/Calendar.php");
// 		$service = new Google_Service_Calendar($this->client);
// 		echo "<h1>Calendar</h1>";
// 		$cal = $service->calendars->get($_POST["calendar_id"]);
// 		echo "ID: ".$cal->getId()."<br/>";
// 		echo "Summary: ".$cal->getSummary()."<br/>";
// 		echo "Description: ".$cal->getDescription()."<br/>";
		
// 		echo "<h2>Rules</h2>";
// 		echo "<table>";
// 		echo "<tr><th>ID</th><th>Role</th><th>Scope</th><th></th></tr>";
// 		$list = $service->acl->listAcl($_POST["calendar_id"]);
// 		while(true) {
// 			foreach ($list->getItems() as $rule) {
// 				/* @var $rule Google_Service_Calendar_AclRule */
// 				echo "<tr>";
// 				echo "<td>".$rule->getId()."</td>";
// 				echo "<td>".$rule->getRole()."</td>";
// 				echo "<td>";
// 				$scope = $rule->getScope();
// 				echo $scope->getType()." = ".$scope->getValue();
// 				echo "</td>";
// 				echo "<td>";
// 				echo "</td>";
// 				echo "</tr>";
// 			}
// 			$pageToken = $list->getNextPageToken();
// 			if ($pageToken) {
// 				$optParams = array('pageToken' => $pageToken);
// 				$list = $service->acl->listAcl($_POST["calendar_id"]);
// 			} else {
// 				break;
// 			}
// 		}
// 		echo "</table>";
// 		echo "<form method='POST'>";
// 		echo "<input type='hidden' name='fct' value='share_calendar'/>";
// 		echo "<input type='hidden' name='calendar_id' value='".$_POST["calendar_id"]."'/>";
// 		echo "Share calendar with ";
// 		echo "<input type='text' name='email'/>";
// 		echo "<input type='submit' value='Share'/>";
// 		echo "</form>";
// 		echo "<form method='POST'><input type='hidden' name='fct' value='calendar_list'/><input type='submit' value='Back to calendars list'/></form>";
// 	}
	
// 	private function shareCalendar() {
// 		// connect to Google Calendar service
// 		require_once("Google/Service/Calendar.php");
// 		$service = new Google_Service_Calendar($this->client);
// 		$calendar_id = $_POST["calendar_id"];
// 		$email = $_POST["email"];
// 		$rule = new Google_Service_Calendar_AclRule();
// 		$rule->setRole("reader");
// 		$scope = new Google_Service_Calendar_AclRuleScope();
// 		$scope->setType("user");
// 		$scope->setValue($email);
// 		$rule->setScope($scope);
// 		$service->acl->insert($calendar_id, $rule);
// 		echo "Calendar shared.<br/>";
// 		echo "<form method='POST'><input type='hidden' name='fct' value='open_calendar'/><input type='hidden' name='calendar_id' value='".$calendar_id."'/><input type='submit' value='Back to calendar'/></form>";
// 	}
}
?>