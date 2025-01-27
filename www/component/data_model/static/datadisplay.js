/**
 * Represents a DataDisplay on JavaScript side
 * @param {String} category the name of the category
 * @param {String} name the name of the data
 * @param {String} table table on the data model
 * @param {String} field_classname name of the implementation of typed_field to use to display this data
 * @param {Object} field_config configuration of the typed_field
 * @param {String} horiz_align one of 'left','right','center'
 * @param {Boolean} editable true if the data can be edited
 * @param {Object} edit_locks information to know what to lock when the user wants to edit the data
 * @param {Boolean} sortable indicates if the data can be sorted or not in a list
 * @param {String} filter_classname implementation of typed_filter to use to display/edit/create a filter on this data
 * @param {Object} filter_config configuration of the typed_filter
 * @param {Object} cell if the data represents a single cell in the database, this object indicates which one
 * @param {Object} new_data structure to use for a new data
 * @param {SubDataDisplay} sub_data if specified, the data contains sub-data
 */
function DataDisplay(category, name, table, field_classname, field_config, 
		horiz_align, editable, edit_locks, sortable, 
		filter_classname, filter_config, cell, new_data, sub_data) {
	this.category = category;
	this.name = name;
	this.table = table;
	this.field_classname = field_classname;
	this.field_config = field_config;
	this.horiz_align = horiz_align;
	this.editable = editable;
	this.edit_locks = edit_locks;
	this.sortable = sortable;
	this.filter_classname = filter_classname;
	this.filter_config = filter_config;
	this.cell = cell;
	this.new_data = new_data;
	this.sub_data = sub_data;
}

/**
 * Represents the sub-data in a DataDisplay
 * @param {Array} names list of the name of the sub-data
 * @param {Array} editable array of boolean, indicating if we can edit them
 * @param {Array} editable_for_new array of boolean, indicating if we can edit them for a new data
 */
function SubDataDisplay(names, editable, editable_for_new) {
	this.names = names;
	this.editable = editable;
	this.editable_for_new = editable_for_new;
}

/** Parse a DataPath to get information about it on JavaScript side
 * @param {String} s string representation of the DataPath, to be parsed 
 */
function DataPath(s) {
	/** The original path in string format */
	this.path = s;
	/** Parse the first section of the path in the given string, then call recursively on the remaining string
	 * @param {String} s the string to parse
	 */
	this._parseElement = function(s) {
		var i = s.indexOf('>');
		var j = s.indexOf('<');
		if (i == -1) i = j;
		if (i == -1) {
			this._parseTable(s);
			this.next = false;
			return;
		}
		this._parseTable(s.substring(0,i));
		s = s.substring(i);
		this.direction = s.charAt(0);
		s = s.substring(1);
		this.multiple = false;
		if (s.charAt(1) == this.direction) {
			this.multiple = true;
			s = s.substring(1);
		}
		this.can_be_null = false;
		if (s.charAt(0) == '?') {
			this.can_be_null = true;
			s = s.substring(1);
		}
		this.next = new DataPath(s);
	};
	/**
	 * Parse the path for a table, with parenthesis for a join key, and a brace for sub model
	 * @param {String} s the string to parse
	 */
	this._parseTable = function(s) {
		var i = s.indexOf('(');
		if (i > 0) {
			this.join_key = s.substring(i+1, s.length-1);
			s = s.substring(0,i);
		} else
			this.join_key = null;
		i = s.indexOf('[');
		if (i > 0) {
			this.sub_model = s.substring(i+1, s.length-1);
			s = s.substring(0,i);
		} else
			this.sub_model = null;
		this.table = s;
	};
	this._parseElement(s);
	
	/** Indicates if the data is mandatory or not (if it cannot be null in the data model)
	 * @returns {Boolean} true if it is mandatory 
	 */
	this.isMandatory = function() {
		var p = this.next;
		while (p) {
			if (p.can_be_null || p.direction == '<')
				return false;
			p = p.next;
		}
		return true;
	};
	/**
	 * Returns the list element of the path, corresponding to the final table
	 * @returns {Object} the last table in the path
	 */
	this.lastElement = function() {
		var p = this;
		while (p.next) p = p.next;
		return p;
	};
}
