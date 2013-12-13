<?php 
class page_data_import extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["data_import_check"], true);
		
		require_once("component/data_model/Model.inc");
		require_once("component/data_model/DataPath.inc");
		
		$context = new DataPathBuilderContext();
		$paths = DataPathBuilder::search_from($context, $input["root_table"]);
		$current_path = $paths[0];
		while ($current_path->parent <> null) $current_path = $current_path->parent;
		
		$rows = array();
		$this->build_import($input["root_table"], $input["fields"], $input["data"], $rows, $paths, $current_path);
		
		var_dump($input);
		//echo str_replace("\n","<br/>",htmlentities(var_export($rows, true)));
	}
	
	private function build_import($table, $fields, $data, &$rows, $paths, $current_path) {
		$t = DataModel::get()->getTable($table);
		$columns = $t->getColumns();
		$rows_imports = array();
		if (count($rows) == 0)
			for ($i = 0; $i < count($data); $i++) {
				$import = new DataImport();
				$import->table = $t;
				array_push($rows, $import);
				array_push($rows_imports, $import);
		} else {
			
		}
		foreach ($columns as $col) {
			// first see if we have the value
			$found = false;
			for ($j = 0; $j < count($fields); ++$j) {
				$f = $fields[$j];
				if ($f["edit"]["table"] <> $table) continue;
				if ($f["edit"]["column"] <> $col->name) continue;
				$found = true;
				for ($i = 0; $i < count($data); $i++)
					$rows_imports[$i]->fields[$col->name] = $data[$i][$j];
			}
			if (!$found) {
				if ($col instanceof \datamodel\ForeignKey) {
					// try to see if we can create one
					foreach ($paths as $p) {
						// TODO
					}
					// TODO
				} else if (!$col->can_be_null) {
					// error: cannot be null, and we don't have the value
					// TODO
				}
			}
		}
	}
	
}

class DataImport {
	public $table;
	public $fields = array();
}
?>