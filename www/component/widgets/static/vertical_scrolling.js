function vertical_scrolling(container, bgcolor, color, arrow_height) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!arrow_height) arrow_height = 7;
	var t=this;

	container.style.display = "flex";
	container.style.flexDirection = "column";
	container.style.position = "relative";
	this.scroll_up = document.createElement("DIV");
	this.scroll_up.style.display = "none";
	this.scroll_up.style.flex = "none";
	this.scroll_up.style.height = arrow_height+"px";
	this.scroll_up.style.paddingTop = "1px";
	this.scroll_up.style.paddingBottom = "1px";
	this.scroll_up.style.cursor = "pointer";
	this.scroll_up.style.zIndex = "1";
	this.scroll_up.style.backgroundColor = bgcolor;
	this.scroll_up.style.position = "absolute";
	this.scroll_up.style.top = "0px";
	container.appendChild(this.scroll_up);
	this.element = document.createElement("DIV");
	this.element.style.flex = "1 1 auto";
	this.element.style.zIndex = 0;
	container.appendChild(this.element);
	this.scroll_down = document.createElement("DIV");
	this.scroll_down.style.display = "none";
	this.scroll_down.style.flex = "none";
	this.scroll_down.style.height = arrow_height+"px";
	this.scroll_down.style.paddingTop = "1px";
	this.scroll_down.style.paddingBottom = "1px";
	this.scroll_down.style.cursor = "pointer";
	this.scroll_down.style.zIndex = 1;
	this.scroll_down.style.backgroundColor = bgcolor;
	this.scroll_down.style.position = "absolute";
	this.scroll_down.style.bottom = "0px";
	container.appendChild(this.scroll_down);
	
	this.arrow_up = document.createElement("DIV");
	this.arrow_up.style.borderBottom = arrow_height+"px solid "+color;
	setOpacity(this.arrow_up, 0.8);
	this.scroll_up.appendChild(this.arrow_up);
	this.scroll_up.onmouseover = function() { setOpacity(t.arrow_up, 1); };
	this.scroll_up.onmouseout = function() { setOpacity(t.arrow_up, 0.8); };

	this.arrow_down = document.createElement("DIV");
	this.arrow_down.style.borderTop = arrow_height+"px solid "+color;
	setOpacity(this.arrow_down, 0.8);
	this.scroll_down.appendChild(this.arrow_down);
	this.scroll_down.onmouseover = function() { setOpacity(t.arrow_down, 1); };
	this.scroll_down.onmouseout = function() { setOpacity(t.arrow_down, 0.8); };
	
	this.scroll_up.onclick = function() {
		var scroll = 20;
		if (container.scrollTop - scroll < 0)
			scroll = container.scrollTop;
		container.scrollTop -= scroll;
		t.scroll_up.style.top = (container.scrollTop)+"px";
		t.scroll_down.style.bottom = (-container.scrollTop)+"px";
		t.layout();
	};
	this.scroll_down.onclick = function() {
		var scroll = 20;
		if (container.scrollTop + scroll + t.element.clientHeight > t.element.scrollHeight)
			scroll = t.element.scrollHeight - container.scrollTop - t.element.clientHeight;
		container.scrollTop += scroll;
		t.scroll_up.style.top = (container.scrollTop)+"px";
		t.scroll_down.style.bottom = (-container.scrollTop)+"px";
		t.layout();
	};
	
	this.layout = function() {
		if (this.element.scrollHeight > this.element.clientHeight) {
			// scrolling
			if (container.scrollTop == 0)
				this.scroll_up.style.display = "none";
			else
				this.scroll_up.style.display = "";
			if (container.scrollTop >= this.element.scrollHeight-this.element.clientHeight)
				this.scroll_down.style.display = "none";
			else
				this.scroll_down.style.display = "";
			
			var width = this.element.clientWidth;
			if (width > 100) {
				var diff = width-100;
				width = 100;
				this.arrow_up.style.marginLeft = Math.floor(diff/2)+"px";
				this.arrow_up.style.marginRight = Math.floor(diff/2)+"px";
				this.arrow_down.style.marginLeft = Math.floor(diff/2)+"px";
				this.arrow_down.style.marginRight = Math.floor(diff/2)+"px";
			} else {
				this.arrow_up.style.marginLeft = "";
				this.arrow_up.style.marginRight = "";
				this.arrow_down.style.marginLeft = "";
				this.arrow_down.style.marginRight = "";
			}
			this.arrow_up.style.borderLeft = Math.floor(width/2)+"px solid transparent";
			this.arrow_up.style.borderRight = Math.floor(width/2)+"px solid transparent";
			this.arrow_down.style.borderLeft = Math.floor(width/2)+"px solid transparent";
			this.arrow_down.style.borderRight = Math.floor(width/2)+"px solid transparent";
		} else {
			// no scrolling
			this.scroll_up.style.display = "none";
			this.scroll_down.style.display = "none";
		}
	};
	this.layout();
	
	layout.addHandler(container, function() { t.layout(); });
}