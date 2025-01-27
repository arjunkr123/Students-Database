if (typeof theme != 'undefined')
	theme.css("mac_tabs.css");

/**
 * List of options, which looks like the tabs on Mac system
 * @param {String} css CSS class
 */
function mac_tabs(css) {
	var t=this;
	/** DIV containing the tabs */
	t.element = document.createElement("DIV");
	t.element.className = "mac_tabs"+(css ? " "+css : "");
	setBorderRadius(t.element, 5, 5, 5, 5, 5, 5, 5, 5);
	/** {String} id of the currently selected tab */
	t.selected = null;
	/** Add a tab
	 * @param {Element|String} content HTML of the tab
	 * @param {String} id identifier
	 */
	t.addItem = function(content,id) {
		var div = document.createElement("DIV");
		div.className = "mac_tab";
		if (typeof content == "string")
			div.innerHTML = content;
		else
			div.appendChild(content);
		div.data = id;
		t.element.appendChild(div);
		div.onclick = function() { t.select(this.data); };
		t._updateRadius();
	};
	/** Update radius of each tab */
	t._updateRadius = function() {
		for (var i = 0; i < t.element.childNodes.length; ++i) {
			var tl = 0, tr = 0, bl = 0, br = 0;
			if (i == 0) { tl = 5; bl = 5; }
			if (i == t.element.childNodes.length-1) { tr = 5; br = 5; }
			setBorderRadius(t.element.childNodes[i], tl, tl, tr, tr, bl, bl, br, br);
		}
	};
	/** Select an item
	 * @param {String} id identifier of the tab to select
	 */
	t.select = function(id) {
		for (var i = 0; i < t.element.childNodes.length; ++i) {
			var tab = t.element.childNodes[i];
			if (tab.data == id)
				tab.className = "mac_tab selected";
			else
				tab.className = "mac_tab";
		}
		t.selected = id;
		if (t.onselect) t.onselect(id);
	};
}