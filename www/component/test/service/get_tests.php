<?php 
class service_get_tests extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$cname = $input["component"];
		require_once("component/test/TestScenario.inc");
		$all_functions = $this->get_all_functions($cname);
		$all_services = $this->get_all_services($cname);
		echo "{";
		echo "functions:";
		if (count($all_functions) == 0)
			echo "null";
		else {
			echo "{scenarios:[";
			if (file_exists("component/".$cname."/test/functionalities")) {
				$files = array();
				$this->browse("component/".$cname."/test/functionalities", "/", $files);
				foreach ($files as $file) {
					echo "{";
					echo "path:".json_encode($file);
					require_once("component/".$cname."/test/functionalities".$file);
					$scenario_class = substr($file, 1, strlen($file)-5);
					$scenario_class = str_replace("/","_",$scenario_class);
					$scenario = new $scenario_class();
					echo ",name:".json_encode($scenario->getName());
					echo ",steps:[";
					$steps = $scenario->getSteps();
					for ($i = 0; $i < count($steps); $i++) {
						if ($i>0) echo ",";
						echo json_encode($steps[$i]->getName());
					}
					echo "]";
					echo "}";
					foreach ($scenario->getCoveredFunctions() as $f) {
						$index = array_search($f, $all_functions);
						if ($index === FALSE) continue;
						array_splice($all_functions, $index, 1);
					}
				}
			}
			echo "]";
			echo ",not_covered:[";
			for ($i = 0; $i < count($all_functions); $i++) {
				if ($i>0) echo ",";
				echo json_encode($all_functions[$i]);
			}
			echo "]";
			echo "}";
		}
		echo ",services:";
		if (count($all_services) == 0)
			echo "null";
		else {
			echo "{scenarios:[";
			if (file_exists("component/".$cname."/test/services")) {
				$files = array();
				$this->browse("component/".$cname."/test/services", "/", $files);
				foreach ($files as $file) {
					echo "{";
					echo "path:".json_encode($file);
					require_once("component/".$cname."/test/services".$file);
					$scenario_class = substr($file, 1, strlen($file)-5);
					$scenario_class = str_replace("/","_",$scenario_class);
					$scenario = new $scenario_class();
					echo ",name:".json_encode($scenario->getName());
					echo ",steps:[";
					$steps = $scenario->getSteps();
					for ($i = 0; $i < count($steps); $i++) {
						if ($i>0) echo ",";
						echo json_encode($steps[$i]->getName());
					}
					echo "]";
					echo "}";
					foreach ($scenario->getCoveredServices() as $f) {
						$index = array_search($f, $all_services);
						if ($index === FALSE) continue;
						array_splice($all_services, $index, 1);
					}
				}
			}
			echo "]";
			echo ",not_covered:[";
			for ($i = 0; $i < count($all_services); $i++) {
				if ($i>0) echo ",";
				echo json_encode($all_services[$i]);
			}
			echo "]";
			echo "}";
		}
		echo "}";
	}

	private function browse($dir_path, $rel_path, &$list) {
		$dir = opendir($dir_path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($dir_path."/".$filename)) {
				$this->browse($dir_path."/".$filename, $rel_path.$filename."/", $list);
			} else {
				if (substr($filename, strlen($filename)-4) == ".php")
					array_push($list, $rel_path.$filename);
			}
		}
		closedir($dir);
	}
	
	private function get_all_functions($cname) {
		$c = PNApplication::$instance->components[$cname];
		$cl = new ReflectionClass($c);
		$list = array();
		foreach ($cl->getMethods() as $m) {
			if ($m->isStatic()) continue;
			if (!$m->isPublic()) continue;
			if ($m->isConstructor()) continue;
			if ($m->isDestructor()) continue;
			if ($m->getDeclaringClass() <> $cl) continue;
			if ($m->getName() == "init") continue;
			$doc = $m->getDocComment();
			if ($doc <> null && strpos($doc, "@not_tested") !== FALSE) continue;
			array_push($list, $m->getName());
		}
		return $list;
	}
	
	private function get_all_services($cname) {
		$path = "component/".$cname."/service";
		$list = array();
		if (!file_exists($path)) return $list;
		$this->browse_services($path, "", $list);
		return $list;
	}
	private function browse_services($path, $rel, &$list) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == ".") continue;
			if ($filename == "..") continue;
			if (is_dir($path."/".$filename))
				$this->browse_services($path."/".$filename, $rel.$filename."/", $list);
			else if (substr($filename, strlen($filename)-4) == ".php")
				array_push($list, $rel.substr($filename, 0, strlen($filename)-4));
		}
		closedir($dir);
	}
}
?>