<?php 
class service_download extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Functionalities to download an update"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/plain"; }
	
	public function execute(&$component, $input) {
		if (isset($_GET["download"])) {
			// deploy utils functionalities
			require_once("component/application/service/deploy_utils.inc");
			if (isset($_POST["getmirrors"])) {
				try {
					$list = getMirrorsList(json_decode($_POST["provider_info"],true));
					if ($list == null) {
						header("HTTP/1.0 200 Error");
						die("Unable to get mirrors list");
					}
					echo json_encode($list);
					die();
				} catch (Exception $e) {
					header("HTTP/1.0 200 Error");
					die($e->getMessage());
				}
			}
			$url = $_POST["url"];
			if (isset($_POST["getsize"])) {
				try {
					$mirror_id = @$_POST["mirror_id"];
					$mirrors_provider = @$_POST["mirrors_provider"];
					$res = getURLFileSize($url, "application/octet-stream", $mirror_id, $mirrors_provider);
					$info = json_decode($res, true);
					if ($info == null || $info["size"] <= 0) {
						header("HTTP/1.0 200 Error");
						die("Unable to find the file on SourceForge".$size);
					}
					die($res);
				} catch (Exception $e) {
					header("HTTP/1.0 200 Error");
					die($e->getMessage());
				}
			}
			@mkdir("data/updates");
			if (isset($_GET["reset"]) && @$_POST["range_from"] == 0)
				@unlink($_POST["target"]);
			try {
				$from = isset($_POST["range_from"]) ? intval($_POST["range_from"]) : null;
				$to = isset($_POST["range_to"]) ? intval($_POST["range_to"]) : null;
				$result = download($url, @$_POST["target"], $from, $to, true);
				if (!isset($_POST["target"]))
					die($result);
			} catch (Exception $e) {
				header("HTTP/1.0 200 Error");
				die($e->getMessage());
			}
		}
		switch ($input["step"]) {
			case "check_if_done":
				if (!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip") ||
					!file_exists("data/updates/Students_Management_Software_".$input["version"].".zip.checksum")) {
						echo "not_downloaded";
						return;
					}
				if (!$this->checkSum(realpath("data/updates/Students_Management_Software_".$input["version"].".zip"))) {
					echo "invalid_download";
					return;
				}
				echo "OK";
				return;
		}
	}
	
	/** Check a downloaded file using checksum
	 * @param string $path path of the file to check
	 * @return boolean true if it succeed
	 */
	private function checkSum($path) {
		$f = fopen($path,"r");
		$f2 = fopen($path.".checksum","r");
		//$pos = 0;
		do {
			set_time_limit(60);
			$s = fread($f, 1024);
			while (strlen($s) < 1024) {
				$s2 = fread($f, 1024-strlen($s));
				if (strlen($s2) == 0) break;
				$s .= $s2;
			} 
			if (strlen($s) == 0) {
				$s = fread($f2, 1);
				if (strlen($s) <> 0) { /*echo "still";*/ return false; }
				break;
			}
			$bytes = unpack("C*",$s);
			$cs = 0;
			for ($i = 1; $i <= count($bytes); $i++) $cs += $bytes[$i];
			$cs %= 256;
			$s = fread($f2, 1);
			$bytes = unpack("C",$s);
			if ($bytes[1] <> $cs) { /*echo "invalid at ".$pos." expected is ".$cs." found is ".$bytes[1];*/ return false; }
			//$pos++;
		} while (true);
		fclose($f);
		fclose($f2);
		return true;
	}
	
}
?>