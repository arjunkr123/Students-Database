<?php 
class service_find_lost_entities extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Look for lost data in the database"; }
	public function inputDocumentation() { echo "No input"; }
	public function outputDocumentation() { echo "List of lost entities: {table,rows:[...]}"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$tables_done = array();
		$tables_limits = array();
		foreach (DataModel::get()->internalGetTables() as $table) {
			if ($table->isRoot()) continue;
			if ($table->getModel() instanceof SubDataModel) {
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					$tables_limits[$table->getName()."_".$sm] = SQLQuery::create()->bypassSecurity()->select($table->getName())->selectSubModelForTable($table, $sm)->count()->executeSingleValue();
				}
			} else {
				$tables_limits[$table->getName()] = SQLQuery::create()->bypassSecurity()->select($table->getName())->count()->executeSingleValue();
			}
		}
		echo "[";
		$first = true;
		foreach (DataModel::get()->internalGetTables() as $table) {
			if ($table->isRoot()) continue;
			// not a root table
			if ($table->getModel() instanceof SubDataModel) {
				// do it for each instance
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					$rows = SQLQuery::create()->bypassSecurity()->select($table->getName())->selectSubModelForTable($table, $sm)->execute();
					if (count($rows) == 0) continue;
					$sub_models = array();
					$sub_models[$table->getModel()->getParentTable()] = $sm;
					$this->findLinked($table, $sub_models, $rows, $tables_done, $tables_limits);
					if (count($rows) == 0) continue;
					if ($first) $first = false; else echo ",";
					$this->printRows($table, $sm, $rows);
				}
			} else {
				$rows = SQLQuery::create()->bypassSecurity()->select($table->getName())->execute();
				if (count($rows) == 0) continue;
				$this->findLinked($table, array(), $rows, $tables_done, $tables_limits);
				if (count($rows) == 0) continue;
				if ($first) $first = false; else echo ",";
				$this->printRows($table, null, $rows);
			}
		}
		echo "]";
	}
	
	/**
	 * Find somewhere the given rows are linked to, and remove them from the list of rows when a path has been found.
	 * @param \datamodel\Table $table the table
	 * @param array $sub_models list of sub model instances
	 * @param array $rows rows to check
	 * @param array $tables_done list of tables already analyzed, to avoid infinite recursivity
	 * @param array $tables_limits number of rows for each table
	 */
	private function findLinked($table, $sub_models, &$rows, $tables_done, $tables_limits) {
		set_time_limit(300+count($rows)/100);
		$table_sql_name = $table->getSQLName($sub_models);
		if (isset($tables_done[$table_sql_name])) {
			if ($tables_done[$table_sql_name] == "all") {
				array_splice($rows, 0, count($rows));
				return;
			}
			for ($i = 0; $i < count($rows); $i++) {
				$k = $this->getRowKey($table, $rows[$i]);
				if (in_array($k, $tables_done[$table_sql_name])) {
					array_splice($rows, $i, 1);
					$i--;
				} else
					array_push($tables_done[$table_sql_name], $k);
			}
			if (count($tables_done[$table_sql_name]) >= $tables_limits[$table_sql_name])
				$tables_done[$table_sql_name] = "all";
		}
		if (count($rows) == 0) return;
		if (!isset($tables_done[$table_sql_name])) {
			if (count($rows) >= $tables_limits[$table_sql_name])
				$tables_done[$table_sql_name] = "all";
			else {
				$tables_done[$table_sql_name] = array();
				set_time_limit(300+count($rows)/100);
				foreach ($rows as $row)
					array_push($tables_done[$table_sql_name], $this->getRowKey($table, $row));
			}
		}
		set_time_limit(300);
		$sm = null;
		if ($table->getModel() instanceof SubDataModel)
			$sm = $sub_models[$table->getModel()->getParentTable()];
		// first, look at root tables directly linked
		// look for foreign keys on this table
		foreach ($table->internalGetColumnsFor($sm) as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			$ft = DataModel::get()->internalGetTable($col->foreign_table);
			if (!$ft->isRoot()) continue;
			set_time_limit(300+count($rows)/100);
			$keys = array();
			foreach ($rows as $r) if ($r[$col->name] <> null) array_push($keys, $r[$col->name]);
			if (count($keys) == 0) continue;
			$found = SQLQuery::create()->bypassSecurity()->select($ft->getName())->selectSubModels($sub_models)->whereIn($ft->getName(), $ft->getPrimaryKey()->name, $keys)->field($ft->getName(), $ft->getPrimaryKey()->name)->executeSingleField();
			set_time_limit(300+count($rows)/100);
			for ($i = 0; $i < count($rows); $i++) {
				if (in_array($rows[$i][$col->name], $found)) {
					// ok, we have a link
					array_splice($rows, $i, 1);
					$i--;
					continue;
				}
			}
			if (count($rows) == 0) return; // we are done
		}
		// look for tables having a foreign key to this table
		set_time_limit(300);
		$pk = $table->getPrimaryKey();
		if ($pk <> null) {
			foreach ($table->getModel()->internalGetTables() as $ft) {
				if ($ft == $table) continue;
				if (!$ft->isRoot()) continue;
				$ft_sub_models = array();
				if ($ft->getModel() instanceof SubDataModel) {
					if ($table->getModel() instanceof SubDataModel) {
						// we are on the same
						$ft_sub_models = array($sm);
					} else {
						$ft_sm = @$sub_models[$ft->getModel()->getParentTable()];
						if ($ft_sm <> null) $ft_sub_models = array($ft_sm); // go back to the sub model
						else $ft_sub_models = $ft->getModel()->getExistingInstances(); // go through all
					}
				} else
					$ft_sub_models = array(null);
				foreach ($ft_sub_models as $ft_sm) {
					foreach ($ft->internalGetColumnsFor($ft_sm) as $col) {
						if (!($col instanceof \datamodel\ForeignKey)) continue;
						if ($col->foreign_table <> $table->getName()) continue;
						// we have a foreign key here
						set_time_limit(300+count($rows)/100);
						$keys = array();
						foreach ($rows as $r) array_push($keys, $r[$pk->name]);
						$new_sub_models = array_merge($sub_models);
						if ($ft_sm <> null) $new_sub_models[$ft->getModel()->getParentTable()] = $ft_sm;
						$found = SQLQuery::create()->bypassSecurity()->select($ft->getName())->selectSubModels($new_sub_models)->whereIn($ft->getName(), $col->name, $keys)->field($ft->getName(), $col->name)->executeSingleField();
						set_time_limit(300+count($rows)/100);					
						for ($i = 0; $i < count($rows); $i++) {
							if (in_array($rows[$i][$pk->name], $found)) {
								// ok, we have a link
								array_splice($rows, $i, 1);
								$i--;
								continue;
							}
						}
						if (count($rows) == 0) return; // we are done
					}
				}
			}
		}
		
		set_time_limit(300);
		// second step: indirect links
		// look for foreign keys on this table
		foreach ($table->internalGetColumnsFor($sm) as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			$ft = DataModel::get()->internalGetTable($col->foreign_table);
			if ($ft->isRoot()) continue;
			set_time_limit(300+count($rows)/100);
			$keys = array();
			foreach ($rows as $r) if ($r[$col->name] <> null) array_push($keys, $r[$col->name]);
			if (count($keys) == 0) continue;
			$found = SQLQuery::create()->bypassSecurity()->select($ft->getName())->selectSubModels($sub_models)->whereIn($ft->getName(), $ft->getPrimaryKey()->name, $keys)->execute();
			if (count($found) == 0) continue;
			set_time_limit(300+count($found)/100);
			$keys_found = array();
			foreach ($found as $f) array_push($keys_found, $f[$ft->getPrimaryKey()->name]);
			$this->findLinked($ft, $sub_models, $found, $tables_done, $tables_limits);
			// found are the rows which didn't find any link
			// so keys_found which are not anymore in found are rows which have link
			foreach ($keys_found as $key) {
				set_time_limit(300+count($found)/100);
				$has_link = true;
				foreach ($found as $f) if ($f[$ft->getPrimaryKey()->name] == $key) { $has_link = false; break; }
				if ($has_link) {
					// remove rows
					set_time_limit(300+count($rows)/100);
					for ($i = 0; $i < count($rows); $i++) {
						if ($rows[$i][$col->name] == $key) {
							array_splice($rows, $i, 1);
							$i--;
							continue;
						}
					}
					if (count($rows) == 0) return; // we are done
				}
			}
		}
		// look for tables having a foreign key to this table
		set_time_limit(300);
		$pk = $table->getPrimaryKey();
		if ($pk <> null) {
			foreach (DataModel::get()->internalGetTables() as $ft) {
				if ($ft == $table) continue;
				if ($ft->isRoot()) continue;
				$ft_sub_models = array();
				if ($ft->getModel() instanceof SubDataModel) {
					if ($table->getModel() instanceof SubDataModel) {
						// we are on the same
						$ft_sub_models = array($sm);
					} else {
						$ft_sm = @$sub_models[$ft->getModel()->getParentTable()];
						if ($ft_sm <> null) $ft_sub_models = array($ft_sm); // go back to the sub model
						else $ft_sub_models = $ft->getModel()->getExistingInstances(); // go through all
					}
				} else
					$ft_sub_models = array(null);
				foreach ($ft_sub_models as $ft_sm) {
					foreach ($ft->internalGetColumnsFor($ft_sm) as $col) {
						if (!($col instanceof \datamodel\ForeignKey)) continue;
						if ($col->foreign_table <> $table->getName()) continue;
						// we have a foreign key here
						set_time_limit(300+count($rows)/100);
						$keys = array();
						foreach ($rows as $r) array_push($keys, $r[$pk->name]);
						$new_sub_models = array_merge($sub_models);
						if ($ft_sm <> null) $new_sub_models[$ft->getModel()->getParentTable()] = $ft_sm;
						$found = SQLQuery::create()->bypassSecurity()->select($ft->getName())->selectSubModels($new_sub_models)->whereIn($ft->getName(), $col->name, $keys)->execute();
						set_time_limit(300+count($found)/100);
						$no_link = array();
						foreach ($found as $f) array_push($no_link, $f);
						$this->findLinked($ft, $new_sub_models, $no_link, $tables_done, $tables_limits);
						foreach ($found as $f) {
							set_time_limit(300);
							$has_link = true;
							foreach ($no_link as $n) if ($n == $f) { $has_link = false; break; }
							if ($has_link) {
								// we can remove the row
								set_time_limit(300+count($rows)/100);
								for ($i = 0; $i < count($rows); $i++) {
									if ($rows[$i][$pk->name] == $f[$col->name]) {
										array_splice($rows, $i, 1);
										$i--;
										continue;
									}
								}
							}
						}
						if (count($rows) == 0) return; // we are done
					}
				}
			}
		}
	}
	
	/** Retrieve the key for the given row
	 * @param datamodel\Table $table the table
	 * @param array $row the row
	 * @return integer|string the key
	 */
	private function getRowKey($table, $row) {
		$pk = $table->getPrimaryKey();
		if ($pk <> null)
			return $row[$pk->name];
		$cols = $table->getKey();
		$key = "";
		for ($i = 0; $i < count($cols); $i++) {
			if ($i > 0) $key .= "-";
			$key .= ($row[$cols[$i]] === null ? "null" : $row[$cols[$i]]);
		}
		return $key;
	}
	
	/**
	 * Print the rows found on the given table
	 * @param \datamodel\Table $table the table
	 * @param number|null $sub_model sub model instance
	 * @param array $rows the lost rows found
	 */
	private function printRows($table, $sub_model, $rows) {
		set_time_limit(300);
		echo "{table:".json_encode($table->getSQLNameFor($sub_model)).",rows:[";
		$first = true;
		foreach ($rows as $r) {
			if ($first) $first = false; else echo ",";
			echo "{";
			$first_col = true;
			foreach ($table->internalGetColumnsFor($sub_model) as $col) {
				if ($first_col) $first_col = false; else echo ",";
				echo $col->name.":".json_encode($r[$col->name]);
			}
			echo "}";
		}
		echo "]}";
	}
	
}
?>