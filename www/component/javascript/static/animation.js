/**
 * Store information about an animation
 * @param {Element} element HTML element on which the animation occurs
 * @param {Number} from starting value of the animation
 * @param {Number} to ending value of the animation
 * @param {Number} duration duration in milliseconds of the animation
 * @param {Number} start_time timestamp when the animation started
 * @param {Function} handler function called regularely to update the element according to the current value (between from and to): given parameters are (value,element)
 */
function Animation(element,from,to,duration,start_time,handler) {
	this.element = element;
	this.from = from;
	this.to = to;
	this.duration = duration;
	this.start_time = start_time;
	this.handler = handler;
	/** Indicates if this animation has been stopped, by calling the animation.stop method */
	this.stopped = false;
}

/** Functionalities to do some animation. An animation being a change over the time of a given HTML element */
window.animation = {
	/** List of current animations */
	animations: [],
	/**
	 * Create an animation
	 * @param {Element} element HTML element on which the animation occurs
	 * @param {Number} from starting value of the animation
	 * @param {Number} to ending value of the animation
	 * @param {Number} duration duration in milliseconds of the animation
	 * @param {Function} handler function called regularely to update the element according to the current value (between from and to): given parameters are (value,element)
	 * @returns {Animation} the created animation
	 */
	create: function(element, from, to, duration, handler) {
		var anim = new Animation(element,from,to,duration,new Date().getTime(),handler);
		this.animations.push(anim);
		handler(from, element);
		if (this.animations.length == 1) setTimeout("if (animation) animation.evolve()",1);
		return anim;
	},
	/** Stop the given animation
	 * @param {Animation} anim the animation to stop
	 */
	stop: function(anim) {
		anim.stopped = true;
	},
	/**
	 * Called regularly to make the animations evolve: compute the new values according to the time, and call the respective handlers.
	 */
	evolve: function() {
		var now = new Date().getTime();
		for (var i = 0; i < this.animations.length; ++i) {
			var anim = this.animations[i];
			if (anim.stopped) {
				this.animations.splice(i,1);
				i--;
				continue;
			}
			if (now - anim.start_time >= anim.duration) {
				this.animations.splice(i,1);
				i--;
				try { anim.handler(anim.to, anim.element); }
				catch (e) {
					window.top.logException(e, "Animation handler");
				}
				continue;
			}
			var time = now-anim.start_time;
			var new_value;
			if (anim.from && anim.from.length) {
				new_value = new Array();
				for (var vi = 0; vi < anim.from.length; ++vi) {
					var amount = anim.to[vi]-anim.from[vi];
					new_value.push(anim.from[vi]+(time*amount/anim.duration));
				}
			} else {
				var amount = anim.to-anim.from;
				new_value = anim.from+(time*amount/anim.duration);
			}
			try { anim.handler(new_value, anim.element); }
			catch (e) {
				window.top.logException(e, "Animation handler");
			}
		}
//		var now2 = new Date().getTime();
//		var next = 50 - (now2-now);
//		if (next < 0) next = 0;
		if (this.animations.length > 0) setTimeout("if (animation) animation.evolve()",1);
	},
	
	/** Implemetation of an animation to modify the opacity of the element
	 * @param {Element} element the element to fade in
	 * @param {Number} duration in milliseconds
	 * @param {Function} end_handler called at the end of the animation
	 * @param {Number} start starting opacity (from 0 to 100)
	 * @param {Number} end ending opacity (from 0 to 100)
	 * @param {Function} callback if given, called each time we modify the opacity
	 * @returns {Animation} the created animation
	 */
	fadeIn: function(element, duration, end_handler, start, end, callback) {
		if (start == null) start = 0;
		if (end == null) end = 100; else end = Math.floor(end);
		return animation.create(element, start, end, duration, function(value, element) {
			value = Math.floor(value);
			try {
				if (value == 0)
					element.style.visibility = 'hidden';
				else {
					setOpacity(element,value/100);
					element.style.visibility = 'visible';
				}
				if (callback) callback(value);
			} catch (e) { window.top.logException(e); }
			if (value == end && end_handler != null) { 
				try { end_handler(element); }
				catch (e) { window.top.logException(e); }
				end_handler = null; 
			}
		});
	},
	/** Implemetation of an animation to modify the opacity of the element
	 * @param {Element} element the element to fade out
	 * @param {Number} duration in milliseconds
	 * @param {Function} end_handler called at the end of the animation
	 * @param {Number} start starting opacity (from 0 to 100)
	 * @param {Number} end ending opacity (from 0 to 100)
	 * @returns {Animation} the created animation
	 */
	fadeOut: function(element, duration, end_handler, start, end) {
		if (start == null) start = 100;
		if (end == null) end = 0;
		return animation.create(element, start, end, duration, function(value, element) {
			value = Math.floor(value);
			try {
				if (value == 0) {
					element.style.visibility = 'hidden';
				} else {
					setOpacity(element,value/100);
					element.style.visibility = 'visible';
				}
			} catch (e) { window.top.logException(e); }
			if (value == 0 && end_handler != null) {
				try { end_handler(element); }
				catch (e) { window.top.logException(e); }
				end_handler = null;
			}
		});
	},
	/**
	 * Make an element appear, by playing on the opacity and the scale transformation
	 * @param {Element} element the element to make appearing
	 * @param {Number} duration duration of the animation in milliseconds
	 * @param {Function} end_handler if given, called at the end of the animation
	 * @param {String} orientation 'top', 'bottom', 'center' (if not given, center will be used)
	 * @returns {Animation} the created animation
	 */
	appear: function(element, duration, end_handler, orientation) {
		return animation.create(element, 0, 100, duration, function(value, element) {
			element.style.visibility = 'visible';
			if (value == 100) {
				setOpacity(element, 1);
				element.style.transform = "";
				if (end_handler)
					try { end_handler(element); }
					catch (e) { window.top.logException(e); }
				end_handler = null;
				return;
			}
			// opacity
			setOpacity(element,value/100);
			// scale
			switch (orientation) {
			default:
			case 'center':
				element.style.transform = "scaleX("+value/100+") scaleY("+value/100+")";
				break;
			case 'top':
				element.style.transform = "scaleY("+value/100+")";
				element.style.transformOrigin = "0 0";
				break;
			case 'bottom':
				element.style.transform = "scaleY("+value/100+")";
				element.style.transformOrigin = "0 100%";
				break;
			}
		});
	},
	/**
	 * Make an element disappear, by playing on the opacity and the scale transformation
	 * @param {Element} element the element to make disappearing
	 * @param {Number} duration duration of the animation in milliseconds
	 * @param {Function} end_handler if given, called at the end of the animation
	 * @param {String} orientation 'top', 'bottom', 'center' (if not given, center will be used)
	 * @returns {Animation} the created animation
	 */
	disappear: function(element, duration, end_handler, orientation) {
		return animation.create(element, 100, 0, duration, function(value, element) {
			if (value == 0) {
				setOpacity(element, 0);
				if (end_handler)
					try { end_handler(element); }
					catch (e) { window.top.logException(e); }
				end_handler = null;
				return;
			}
			// opacity
			setOpacity(element,value/100);
			// scale
			switch (orientation) {
			default:
			case 'center':
				element.style.transform = "scaleX("+value/100+") scaleY("+value/100+")";
				break;
			case 'top':
				element.style.transform = "scaleY("+value/100+")";
				element.style.transformOrigin = "0 0";
				break;
			case 'bottom':
				element.style.transform = "scaleY("+value/100+")";
				element.style.transformOrigin = "0 100%";
				break;
			}
		});
	},
	/** Implemetation of an animation to modify the color of the element
	 * @param {Element} element the element to animate
	 * @param {Array} from [r,g,b]
	 * @param {Array} to [r,g,b]
	 * @param {Number} duration in milliseconds
	 * @returns {Animation} the created animation
	 */
	fadeColor: function(element, from, to, duration) {
		return animation.create(element, from, to, duration, function(value, element){
			element.style.color = "rgb("+Math.round(value[0])+","+Math.round(value[1])+","+Math.round(value[2])+")";
		});
	},
	/** Implemetation of an animation to modify the background color of the element
	 * @param {Element} element the element to animate
	 * @param {Array} from [r,g,b]
	 * @param {Array} to [r,g,b]
	 * @param {Number} duration in milliseconds
	 * @returns {Animation} the created animation
	 */
	fadeBackgroundColor: function(element, from, to, duration) {
		return animation.create(element, from, to, duration, function(value, element){
			element.style.backgroundColor = "rgb("+Math.round(value[0])+","+Math.round(value[1])+","+Math.round(value[2])+")";
		});
	},
	
	/**
	 * Make some elements appear (with opacity), when the mouse is over the given element 
	 * @param {Element} onover_element element that fire the apparition of the elements
	 * @param {Array} appears_elements list of Element to appear
	 */
	appearsOnOver: function(onover_element, appears_elements) {
		if (getObjectClassName(appears_elements) != "Array") appears_elements = [appears_elements];
		for (var i = 0; i < appears_elements.length; ++i)
			setOpacity(appears_elements[i], 0);
		var anim_in = [];
		var anim_out = [];
		var listener = function(ev) {
			var e = getCompatibleMouseEvent(ev);
			var x = absoluteLeft(onover_element);
			if (e.x >= x && e.x < x+onover_element.offsetWidth) {
				var y = absoluteTop(onover_element);
				if (e.y >= y && e.y < y+onover_element.offsetHeight) {
					// inside
					if (anim_in.length > 0) 
						return; // already in progress
					for (var i = 0; i < anim_out.length; ++i) animation.stop(anim_out[i]);
					anim_out = [];
					for (var i = 0; i < appears_elements.length; ++i) {
						var e = appears_elements[i];
						var o;
						if (e.style && e.style.visibility && e.style.visibility == 'hidden')
							o = 0;
						else
							o = getOpacity(e);
						if (o == 1) continue; // already fully visible
						e.anim_in = animation.fadeIn(e, 250, function(e) {
							anim_in.remove(e.anim_in);
							e.anim_in = null;
						},o*100,100);
						anim_in.push(e,anim_in);
					}
					return;
				}
			}
			// outside
			if (anim_out.length > 0) 
				return; // already in progress
			for (var i = 0; i < anim_in.length; ++i) animation.stop(anim_in[i]);
			anim_in = [];
			for (var i = 0; i < appears_elements.length; ++i) {
				var e = appears_elements[i];
				var o;
				if (e.style && e.style.visibility && e.style.visibility == 'hidden')
					o = 0;
				else
					o = getOpacity(e);
				if (o == 0) continue; // already hidden
				e.anim_out = animation.fadeOut(e, 250, function(e) {
					anim_out.remove(e.anim_out);
					e.anim_out = null;
				},o*100,0);
				anim_out.push(e,anim_out);
			}
		};
		listenEvent(window,'mousemove',listener);
		window.to_cleanup.push({cleanup:function() { unlistenEvent(window,'mousemove',listener); }});
	}
};
