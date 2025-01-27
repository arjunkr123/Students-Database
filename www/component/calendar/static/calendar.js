// #depends[/static/javascript/utils.js]
if (typeof require != 'undefined') require("calendar_objects.js");
/**
 * Manage a list of calendars.
 */
function CalendarManager() {
	/** List of calendars */
	this.calendars = [];
	/** Index of the calendar used by default to create new events */
	this.default_calendar_index = 0;
	
	/** Event called when a new calendar is added to this manager */
	this.on_calendar_added = new Custom_Event();
	/** Event called when a calendar is removed from this manager */
	this.on_calendar_removed = new Custom_Event();
	
	/** Event called when an event is added to any calendar. The new event is given as parameter. */
	this.on_event_added = new Custom_Event();
	/** Event called when an event is removed from any calendar. The event is given as parameter. */
	this.on_event_removed = new Custom_Event();
	/** Event called when an event is updated in any calendar. The event is given as parameter. */
	this.on_event_updated = new Custom_Event;
	
	/** Event when a calendar is going to be refreshed */
	this.on_refresh = new Custom_Event();
	/** Event when a calendar just finished to be refreshed */
	this.on_refresh_done = new Custom_Event();
	
	/** Listeners this manager registered to its calendars, stored there to unregister when removing a calendar */
	this._calendars_listeners = [];
	
	/**
	 * Add a calendar to manage.
	 * @param {Calendar} cal the calendar to add
	 * @returns {Calendar} the given calendar
	 */
	this.addCalendar = function(cal) {
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i].provider.id == cal.provider.id && this.calendars[i].id == cal.id) return this.calendars[i]; // already there
		this.calendars.push(cal);
		var t=this;
		var listeners = {calendar:cal,listeners:[
		  function() { t.on_refresh.fire(cal); },
		  function() { t.on_refresh_done.fire(cal); },
		  function(ev) { t.on_event_added.fire(ev); },
		  function(ev) { t.on_event_updated.fire(ev); },
		  function(ev) { t.on_event_removed.fire(ev); },
		  function(cal) { t.removeCalendar(cal); }
		]};
		this._calendars_listeners.push(listeners);
		cal.onrefresh.addListener(listeners.listeners[0]);
		cal.onrefreshdone.addListener(listeners.listeners[1]);
		cal.on_event_added.addListener(listeners.listeners[2]);
		cal.on_event_updated.addListener(listeners.listeners[3]);
		cal.on_event_removed.addListener(listeners.listeners[4]);
		cal.on_removed.addListener(listeners.listeners[5]);
		if (!cal.last_update) cal.last_update = 0;
		if (cal.show && cal.last_update < new Date().getTime() - 60000)
			cal.refresh();
		this.on_calendar_added.fire(cal);
		return cal;
	};
	
	/**
	 * Remove a calendar.
	 * @param {Calendar} cal the calendar to remove
	 */
	this.removeCalendar = function(cal) {
		if (!this.calendars.contains(cal)) return;
		for (var i = 0; i < this._calendars_listeners.length; ++i) {
			if (this._calendars_listeners[i].calendar == cal) {
				var listeners = this._calendars_listeners[i].listeners;
				cal.onrefresh.removeListener(listeners[0]);
				cal.onrefreshdone.removeListener(listeners[1]);
				cal.on_event_added.removeListener(listeners[2]);
				cal.on_event_updated.removeListener(listeners[3]);
				cal.on_event_removed.removeListener(listeners[4]);
				cal.on_removed.removeListener(listeners[5]);
				this._calendars_listeners.splice(i,1);
				break;
			}
		}
		if (cal.show) {
			for (var i = 0; i < cal.events.length; ++i)
				this.on_event_removed.fire(cal.events[i]);
		}
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i] == cal) {
				this.calendars.splice(i, 1);
				break;
			};
		this.on_calendar_removed.fire(cal);
	};
	
	/**
	 * Signal that the events of the given calendar should not be displayed.
	 * @param {Calendar} cal the calendar to hide 
	 */
	this.hideCalendar = function(cal) {
		if (!cal.show) return;
		cal.show = false;
		if (cal.saveShow)
			cal.saveShow(false);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed.fire(cal.events[i]);
	};
	
	/**
	 * Signal that the events of the given calendar should be displayed.
	 * @param {Calendar} cal the calendar to show
	 */
	this.showCalendar = function(cal) {
		if (cal.show) return;
		cal.show = true;
		if (cal.saveShow)
			cal.saveShow(true);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
		if (cal.last_update < new Date().getTime() - 60000)
			cal.refresh();
	};
	
	/**
	 * Set the color of a calendar: save the color, remove all events from view, add back all events (so the events in the view will be in the new color)
	 * @param {Calendar} cal the calendar
	 * @param {String} color the new color
	 */
	this.setCalendarColor = function(cal, color) {
		if (cal.color == color) return;
		cal.color = color;
		if (cal.saveColor)
			cal.saveColor(color);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed.fire(cal.events[i]);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
	};
	
	/**
	 * Refresh all calendars of this CalendarManager.
	 */
	this.refreshCalendars = function() {
		for (var i = 0; i < this.calendars.length; ++i)
			this.calendars[i].refresh();
	};
}

/**
 * Abstract class of a calendars provider
 * @param {String} id the unique identifier for the provider 
 */
function CalendarsProvider(id) {
	this.id = id;
}
CalendarsProvider.prototype = {
	/** {Array} list of Calendar owned by this provider */
	calendars: [],
	/** {Custom_Event} event raised when a new calendar appears on this provider */
	on_calendar_added: new Custom_Event(),
	/** {Custom_Event} event raised when a calendar disappears on this provider */
	on_calendar_removed: new Custom_Event(),
	/** {Number} minimum time (in milliseconds) before calendars from this provider can be automatically refreshed */
	minimum_time_to_autorefresh_calendar: 5*60*1000,
	/** {Number} minimum time (in milliseconds) before the list of calendars from this provider can be automatically refreshed */
	minimum_time_to_autorefresh_calendars_list: 15*60*1000,
	/** Last time the list of calendars has been refreshed */
	_last_refresh_calendars_list: 0,
	/** Reload the list of calendars from this provider */
	refreshCalendars: function() {
		this._last_refresh_calendars_list = new Date().getTime();
		var t=this;
		this._retrieveCalendars(function (list) {
			var removed = [];
			for (var i = 0; i < t.calendars.length; ++i) removed.push(t.calendars[i]);
			t.calendars = list;
			for (var i = 0; i < list.length; ++i) {
				var found = false;
				for (var j = 0; j < removed.length; ++j) {
					if (removed[j].id == list[i].id) {
						found = true;
						removed.splice(j,1);
						break;
					}
				}
				if (!found) t.on_calendar_added.fire(list[i]);
			}
			for (var i = 0; i < removed.length; ++i) {
				removed[i].on_removed.fire(removed[i]);
				t.on_calendar_removed.fire(removed[i]);
				removed[i].cleanup();
			}
		});
	},
	/** Function to be overriden by the implementation, to load the list of calendars on this provider
	 * @param {Function} handler called when the list is ready, the list of calendars is given as parameter
	 */
	_retrieveCalendars: function(handler) { },
	/** Retrieve a calendar by id on this provider
	 * @param {String} id the identifier of the calendar to retrieve
	 * @returns {Calendar} the calendar, or null if it does not exist
	 */
	getCalendar: function(id) {
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i].id == id) return this.calendars[i];
		return null;
	},
	/**
	 * Icon (16x16) of the provider
	 * @returns {String} the url of the icon
	 */
	getProviderIcon: function() {
		
	},
	/**
	 * Name of the provider
	 * @returns {String} name of the provider
	 */
	getProviderName: function() {
		
	},
	/** Indicates a status about the connection to the provider, or empty if it is connected */
	connection_status: "",
	/** Event called when the connection_status changed */
	on_connection_status: new Custom_Event(),
	/** Update the connection_status
	 * @param {String} status the new status (empty string if it is already connected)
	 */
	connectionStatus: function(status) {
		this.connection_status = status;
		this.on_connection_status.fire(status);
	},
	/**
	 * Indicates if the provider supports the creation of calendars
	 * @returns {Boolean} true if the user can create calendars using this provider
	 */
	canCreateCalendar: function () { return false; },
	/**
	 * Indicates if the provider supports specifying a color when creating a calendar
	 * @returns {Boolean} true if the functionality is supported
	 */
	canCreateCalendarWithColor: function() { return false; },
	/**
	 * Indicates if the provider supports specifying an icon when creating a calendar
	 * @returns {Boolean} true if the functionality is supported
	 */
	canCreateCalendarWithIcon: function() { return false; },
	/**
	 * Create a calendar
	 * @param {String} name name of the calendar to create
	 * @param {String} color color of the calendar to create, or null
	 * @param {String} icon icon of the calendar to create, or null
	 * @param {Function} oncreate called with the calendar's id when the creation succeed
	 */
	createCalendar: function(name, color, icon, oncreate) {}
};
if (window == window.top && !window.top.CalendarsProviders) {
	/**
	 * Handle the list of existing providers of calendars (PN, Google...)
	 */
	window.top.CalendarsProviders = {
		/**
		 * Add a calendar provider 
		 * @param {CalendarsProvider} provider the new calendar provider
		 */
		add: function(provider) {
			for (var i = 0; i < this._providers.length; ++i)
				if (this._providers[i].getProviderName() == provider.getProviderName())
					return; // same
			this._providers.push(provider);
			for (var i = 0; i < this._handlers.length; ++i)
				this._handlers[i](provider);
			provider.refreshCalendars();
		},
		/** List of providers */
		_providers: [],
		/** List of handlers to call when a new provider is available */
		_handlers: [],
		/** Gives a function to be called for each available provider first, then each time a new provider becomes available
		 * @param {Function} handler_for_each_provider function which will be called, with a CalendarProvider as parameter
		 */
		get: function(handler_for_each_provider) {
			for (var i = 0; i < this._providers.length; ++i)
				handler_for_each_provider(this._providers[i]);
			this._handlers.push(handler_for_each_provider);
		},
		/** Return the current existing calendar providers
		 * @returns {Array} array of CalendarsProvider
		 */
		getCurrentProviders: function() {
			return arrayCopy(this._providers);
		},
		/** Retrieve a calendar provider by id
		 * @param {String} id identifier of the provider
		 * @returns {CalendarsProvider} the provider, or null if it does not exist
		 */
		getProvider: function(id) {
			for (var i = 0; i < this._providers.length; ++i)
				if (this._providers[i].id == id) return this._providers[i];
			return null;
		},
		/** Refresh list of calendars on every provider.
		 * @param {Boolean} force if false, the list won't be refreshed if the minimum time given by the provider is not reached since the last refresh.
		 */
		_refreshCalendarsList: function(force) {
			var now = new Date().getTime();
			for (var i = 0; i < this._providers.length; ++i) {
				if (!force && now - this._providers[i]._last_refresh_calendars_list < this._providers[i].minimum_time_to_autorefresh_calendars_list) continue;
				this._providers[i].refreshCalendars();
			}
		},
		/** Internal function to refresh the list of calendars on all providers
		 * @param {Boolean} force if false, the calendars won't be refreshed if the minimum time given by the provider is not reached since the last refresh.
		 */
		_refreshCalendars: function(force) {
			var now = new Date().getTime();
			for (var i = 0; i < this._providers.length; ++i) {
				for (var j = 0; j < this._providers[i].calendars.length; ++j) {
					if (!force && now - this._providers[i].calendars[j]._last_refresh < this._providers[i].minimum_time_to_autorefresh_calendar) continue;
					this._providers[i].calendars[j].refresh();
				}
			}
		}
	};
	/** Refresh calendars on login with new user, remove calendars on logout */
	var listenLoginLogout = function() {
		if (!window.top.pnapplication) {
			setTimeout(listenLoginLogout, 20);
		}
		window.top.pnapplication.onlogout.addListener(function() {
			for (var i = 0; i < window.top.CalendarsProviders._providers.length; ++i) {
				var prev_calendars = window.top.CalendarsProviders._providers[i].calendars;
				window.top.CalendarsProviders._providers[i].calendars = [];
				for (var j = 0; j < prev_calendars.length; ++j) {
					window.top.CalendarsProviders._providers[i].on_calendar_removed.fire(prev_calendars[j]);
					prev_calendars[j].cleanup();
				}
			}
		});
		window.top.pnapplication.onlogin.addListener(function() {
			window.top.CalendarsProviders._refreshCalendarsList(true);
			window.top.CalendarsProviders._refreshCalendars(true);
		});
	};
	listenLoginLogout();
	setInterval(function() { if (window.top.CalendarsProviders) window.top.CalendarsProviders._refreshCalendarsList(); }, 150*1000);
	setInterval(function() { if (window.top.CalendarsProviders) window.top.CalendarsProviders._refreshCalendars(); }, 45*1000);
}

/**
 * Abstract class of a calendar.
 * @param {CalendarsProvider} provider providing this calendar
 * @param {String} name name of the calendar
 * @param {String} color hexadecimal RGB color or null for a default one. ex: C0C0FF
 * @param {Boolean} show indicates if the events of the calendar should be displayed or not
 * @param {String} icon URL
 */
function Calendar(provider, name, color, show, icon) {
	if (!color) color = "A0A0FF";
	this.provider = provider;
	/** URL of the icon for the calendar */
	this.icon = icon;
	/** name of the calendar */
	this.name = name;
	/** hexadecimal RGB color or null for a default one. ex: C0C0FF */
	this.color = color;
	/** indicates if the events of the calendar should be displayed or not */
	this.show = show;
	/** indicates if the calendar is currently updating its events */
	this.updating = false;
	/** event called when the calendar is going to be refreshed (just before) */
	this.onrefresh = new Custom_Event();
	/** event called when the calendar has been refreshed */
	this.onrefreshdone = new Custom_Event();
	/** Event called when a new event appear on this calendar */
	this.on_event_added = new Custom_Event();
	/** Event called when an event was updated on this calendar */
	this.on_event_updated = new Custom_Event();
	/** Event called when an event disappear from this calendar */
	this.on_event_removed = new Custom_Event();
	/** Event called when this calendar is removed */
	this.on_removed = new Custom_Event();
	/** list of events in the calendar */
	this.events = [];
	/** Last time the calendar has been refreshed */
	this._last_refresh = 0;
	/** called to refresh the calendar. It must be overrided by the implementation of the calendar.
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this.refresh = function(ondone) {
		if (this.updating) return; // already in progress
		this._last_refresh = new Date().getTime();
		this.updating = true;
		this.onrefresh.fire();
		var t=this;
		this._refresh(function() {
			t.last_update = new Date().getTime();
			t.updating = false;
			if (ondone) ondone();
			t.onrefreshdone.fire();
		});
		
	};
	/** called to refresh the calendar. It must be overrided by the implementation of the calendar.
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this._refresh = function(ondone) {
		var type = getObjectClassName(this);
		window.top.status_manager.addStatus(new window.top.StatusMessageError(null, "Calendar._refresh not implemented: "+type));
		if (ondone) ondone();
	};
	/** {Function} function called to save an event. If it is not defined, it means the calendar is read only. This function takes the event to save as parameter. */
	this.saveEvent = null; // must be overriden if the calendar supports modifications
	/** {Function} function called to remove an event. If this is not defined, it means the calendar is read only. This function takes the event to remove as parameter. */
	this.removeEvent = null; // must be overriden if the calendar supports remove
	/** {Function} Save the visibility of the calendar (if supported by the provider)
	 * the format is function(show) {...}; with show a boolean: true to be visible, false to be hidden
	 */
	this.saveShow = null; // to be defined if supported
	/** {Function} Save the color of the calendar (if supported by the provider).
	 * The format is function(color) {...};
	 */
	this.saveColor = null; // to be defined if supported
	/** {Function} function to rename the calendar: null if not supported by the provider, else this attribute must be defined */
	this.rename = null; // must be overriden if this is supported
	/** {Function} function to remove the calendar: null if not supported by the provider, else this attribute must be defined */
	this.remove = null; // must be overriden if this is supported
	
	/** Cleanup */
	this.cleanup = function() {
		for (var i = 0; i < this.events.length; ++i)
			for (var n in this.events[i])
				this.events[i][n] = null;
		this.events = null;
		this.provider = null;
		t = null;
	};
}

/**
 * UI Control for a calendar: controls its color, visibility, name...
 * @param {Element} container where to put it
 * @param {Calendar} cal the calendar to control
 * @param {CalendarManager} manager the calandar manager to use for the actions on the calendar
 */
function CalendarControl(container, cal, manager) {
	var t=this;
	/** Creates the control */
	this._init = function() {
		this.div = document.createElement("DIV"); container.appendChild(this.div);
		this.box = document.createElement("DIV"); this.div.appendChild(this.box);
		this.box.style.display = "inline-block";
		this.box.style.width = "10px";
		this.box.style.height = "10px";
		this.box.style.border = "1px solid #"+cal.color;
		this.box.title = "Show/Hide Calendar";
		this.box.style.cursor = "pointer";
		if (cal.show)
			this.box.style.backgroundColor = "#"+cal.color;
		this.box.onclick = function() {
			if (cal.show) {
				manager.hideCalendar(cal);
				t.box.style.backgroundColor = '';
			} else {
				manager.showCalendar(cal);
				t.box.style.backgroundColor = "#"+cal.color;
			}
		};
		if (cal.icon) {
			this.icon = document.createElement("IMG");
			this.icon.style.paddingLeft = "3px";
			this.icon.style.verticalAlign = "bottom";
			this.icon.src = cal.icon;
			this.div.appendChild(this.icon);
		}
		this.name = document.createElement("SPAN"); this.div.appendChild(this.name);
		this.name.style.paddingLeft = "3px";
		this.name.innerHTML = cal.name;
		if (!cal.saveEvent) {
			var img = document.createElement("IMG");
			img.src = "/static/calendar/read_only.png";
			img.title = "Read-only: you cannot modify this calendar";
			t.div.appendChild(img);
		}
		this.menu_button = document.createElement("IMG");
		this.menu_button.className = "button";
		this.menu_button.style.padding = "0px";
		this.menu_button.src = theme.icons_10.arrow_down_context_menu;
		this.menu_button.style.verticalAlign = "bottom";
		this.menu_button.onclick = function() {
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(cal.show ? theme.icons_16.hide : theme.icons_16.show, cal.show ? "Hide" : "Show", function() {
					t.box.onclick();
				});
				menu.addIconItem(theme.icons_16.color, "Change color", function() {
					require(["color_choice.js","popup_window.js"], function() {
						var content = document.createElement("DIV");
						var chooser = new color_choice(content, "#"+cal.color);
						var popup = new popup_window("Change Color", theme.icons_16.color, content);
						popup.addOkCancelButtons(function() {
							var col = colorToString(chooser.color).substring(1);
							manager.setCalendarColor(cal, col);
							t.box.style.backgroundColor = "#"+col;
							t.box.style.border = "1px solid #"+col;
							popup.hide();
						});
						popup.show();
					});
				});
				if (cal.rename != null)
					menu.addIconItem(theme.icons_16.edit, "Rename", function() {
						inputDialog(theme.icons_16.edit,"Rename Calendar","Enter the new name",cal.name,100,function(name) {
							if (name.length == 0) return "Please enter a name";
							return null;
						},function(name) {
							if (!name) return;
							cal.rename(name,function(){
								t.name.innerHTML = name;
							});
						});
					});
				if (cal.remove != null)
					menu.addIconItem(theme.icons_16.remove, "Remove", function() {
						confirmDialog("Are you sure you want to completely remove this calendar ?", function(yes) {
							if (!yes) return;
							cal.remove();
						});
					});
				menu.showBelowElement(t.menu_button);
			});
		};
		this.div.appendChild(this.menu_button);
		var start_refresh = function() {
			if (window.closing) return;
			t.loading = document.createElement("IMG");
			t.loading.src = theme.icons_10.loading;
			t.div.appendChild(t.loading);
		};
		cal.onrefresh.addListener(start_refresh);
		var refresh_done = function(){
			if (!t.loading || !t.div) return;
			t.div.removeChild(t.loading);
			t.loading = null;
		};
		cal.onrefreshdone.addListener(refresh_done);
		var calendar_removed = function() {
			t.div.parentNode.removeChild(t.div);
		};
		cal.on_removed.addListener(calendar_removed);
		if (cal.updating) start_refresh();
		this.div.ondomremoved(function() {
			cal.onrefresh.removeListener(start_refresh);
			cal.onrefreshdone.removeListener(refresh_done);
			cal.on_removed.removeListener(calendar_removed);
			start_refresh = null;
			refresh_done = null;
			calendar_removed = null;
			t.div = null;
			t.loading = null;
			t.menu_button = null;
			t.name = null;
			t.box = null;
			t.icon = null;
			t = null;
		});
	};
	this._init();
}


/**
 * Implementation of Calendar, for an internal calendar (stored in database)
 * @param {PNCalendarsProvider} provider the provider
 * @param {Number} id the calendar id
 * @param {String} name the name of the calendar
 * @param {String} color the color
 * @param {Boolean} show indicates if the events should be displayed
 * @param {Boolean} writable indicates if the calendar can be modified
 * @param {String} icon icon of the calendar
 */
function PNCalendar(provider, id, name, color, show, writable, icon, removable) {
	Calendar.call(this, provider, name, color, show, icon);
	/** Id of this PN Calendar */
	this.id = id;
	this._refresh = function(ondone) {
		var t=this;
		require("calendar_objects.js", function(){
			service.json("calendar", "get", {id:t.id}, function(result) {
				if (!result) { ondone(); return; }
				try {
					var removed_events = t.events;
					t.events = [];
					for (var i = 0; i < result.length; ++i) {
						var ev = result[i];
						var found = false;
						for (var j = 0; j < removed_events.length; ++j) {
							if (ev.uid == removed_events[j].uid) {
								found = true;
								if (ev.last_modified != removed_events[j].last_modified) {
									t.events.push(ev);
									t.on_event_updated.fire(ev);
									for (var n in removed_events[j])
										removed_events[j][n] = null;
								} else {
									t.events.push(removed_events[j]);
								}
								removed_events.splice(j,1);
								break;
							}
						}
						if (!found) {
							t.events.push(ev);
							t.on_event_added.fire(ev);
						}
					}
					for (var i = 0; i < removed_events.length; ++i) {
						t.on_event_removed.fire(removed_events[i]);
						for (var n in removed_events[i])
							removed_events[i][n] = null;
					}
				} catch (e) {
					logException(e, "Error while refreshing PN calendar");
				}
				ondone();
			});
		});
	};
	if (id > 0)
		this.saveShow = function(show) {
			service.json("calendar", "set_configuration", {calendar:id,show:show},function(res){});
		};
	if (id > 0)
		this.saveColor = function(color) {
			service.json("calendar", "set_configuration", {calendar:id,color:color},function(res){});
		};
	if (writable) {
		var t = this;
		this.saveEvent = function(event) {
			service.json("calendar","save_event",{event:event},function(res){
				if (!event.uid && res && res.uid) {
					event.uid = res.uid;
					event.id = res.id;
					t.events.push(event);
					t.on_event_added.fire(event);
				} else if (event.uid && res) {
					for (var i = 0; i < t.events.length; ++i)
						if (t.events[i].uid == event.uid) {
							t.events.splice(i,1,event);
							t.on_event_updated.fire(event);
							break;
						}
				}
			});
		};
		this.removeEvent = function(event) {
			service.json("calendar","remove_event",{calendar:event.calendar_id,event:event.id},function(res) {
				if (!res) return;
				for (var i = 0; i < t.events.length; ++i)
					if (t.events[i].uid == event.uid) {
						t.events.splice(i,1);
						t.on_event_removed.fire(event);
						break;
					}
			});
		};
		this.rename = function(name,ondone) {
			service.json("calendar","rename_calendar",{id:t.id,name:name},function(res){
				if (!res) return;
				t.name = name;
				if (ondone) ondone();
			});
		};
		if (removable) {
			this.remove = function() {
				service.json("calendar", "remove_calendar",{id:t.id},function(res) {
					if (!res) return;
					t.provider.calendars.removeUnique(t);
					t.provider.on_calendar_removed.fire(t);
					t.on_removed.fire(t);
				});
			};
		}
	}
}
PNCalendar.prototype = new Calendar();
PNCalendar.prototype.constructor = PNCalendar;

/** Implementation of CalendarsProvider for internal(PN) calendar (stored in the database) */
function PNCalendarsProvider() {
	CalendarsProvider.call(this,"PN");
	var t=this;
	this.minimum_time_to_autorefresh_calendar = 2*60*1000; // we are in local, we can refresh regularly
	this.minimum_time_to_autorefresh_calendars_list = 5*60*1000; // we are in local, we can refresh regularly
	this._retrieveCalendars = function(handler) {
		t.connectionStatus("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading PN Calendars...");
		service.json("calendar", "get_my_calendars", {}, function(calendars) {
			t.connectionStatus("");
			if (!calendars) return;
			if (!PNCalendar) return;
			var list = [];
			for (var i = 0; i < calendars.length; ++i)
				list.push(new PNCalendar(t, calendars[i].id, calendars[i].name, calendars[i].color, calendars[i].show, calendars[i].writable, calendars[i].icon, calendars[i].removable));
			try { handler(list); } catch (e) {} // in case the page requesting it already disappear
		});
	};
	this.getProviderIcon = function() {
		return "/static/application/logo_16.png";
	};
	this.getProviderName = function() {
		return "PN Calendars";
	};
	this.canCreateCalendar = function() { return true; };
	this.canCreateCalendarWithColor = function() { return true; };
	this.canCreateCalendarWithIcon = function() { return true; };
	this.createCalendar = function(name, color, icon, oncreate) {
		service.json("calendar", "create_user_calendar", {name:name,color:color,icon:icon},function(res) {
			if (!res || !res.id) return;
			var cal = new PNCalendar(t, res.id, name, color, true, true, icon);
			t.calendars.push(cal);
			t.on_calendar_added(cal);
			oncreate(cal);
		});
	};
}
PNCalendarsProvider.prototype = new CalendarsProvider();
PNCalendarsProvider.prototype.constructor = PNCalendarsProvider;

if (!window.top.pn_calendars_provider)
	window.top.CalendarsProviders.add(window.top.pn_calendars_provider = new PNCalendarsProvider());