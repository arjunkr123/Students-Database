/**
 * Screen to edit a Subject
 * @param {CurriculumSubject} subject the subject to edit
 * @param {Array} existing_subjects list of CurriculumSubject
 * @param {Function} onvalidation called when we validate the data enter by the user, with a boolean as parameter indicating if this is valid or not
 */
function edit_curriculum_subject(subject,existing_subjects,onvalidation) {
	/** Top level element, to put where we want to display this screen */
	this.element = document.createElement("TABLE");
	var tr,td;
	var spes = getPeriodSpecializations(subject.period_id);
	if (spes.length > 0) {
		this.element.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.colSpan = 2;
		/** List of radio buttons for the specializations */
		this.radios_spes = [];
		for (var i = 0; i < spes.length; ++i) {
			var radio = document.createElement("INPUT");
			radio.name = 'spe';
			radio.type = 'radio';
			radio.value = spes[i];
			if (subject.specialization_id == spes[i]) radio.checked = 'checked';
			this.radios_spes.push(radio);
			td.appendChild(radio);
			td.appendChild(document.createTextNode(" only for Specialization "+getSpecializationName(spes[i])));
			td.appendChild(document.createElement("BR"));
		}
		var radio = document.createElement("INPUT");
		radio.name = 'spe';
		radio.type = 'radio';
		radio.value = 0;
		if (subject.specialization_id == null) radio.checked = 'checked';
		this.radios_spes.push(radio);
		td.appendChild(radio);
		td.appendChild(document.createTextNode(" common to all specializations"));
		td.appendChild(document.createElement("BR"));
	}
	this.element.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Code";
	tr.appendChild(td = document.createElement("TD"));
	/** Input to edit the code of the subject */
	this.input_code = document.createElement("INPUT");
	this.input_code.type = "text";
	this.input_code.size = "15";
	this.input_code.maxLength = 100;
	this.input_code.value = subject.code;
	td.appendChild(this.input_code);
	this.element.appendChild(this.tr_code_error = document.createElement("TR"));
	this.tr_code_error.appendChild(td = document.createElement("TD"));
	td.colSpan = 2;
	
	this.element.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Name";
	tr.appendChild(td = document.createElement("TD"));
	/** Input to edit the name of the subject */
	this.input_name = document.createElement("INPUT");
	this.input_name.type = "text";
	this.input_name.size = "40";
	this.input_name.maxLength = 100;
	this.input_name.value = subject.name;
	td.appendChild(this.input_name);
	this.element.appendChild(this.tr_name_error = document.createElement("TR"));
	this.tr_name_error.appendChild(td = document.createElement("TD"));
	td.colSpan = 2;

	this.element.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Hours";
	tr.appendChild(td = document.createElement("TD"));
	/** Input to edit the number of hours of the subject */
	this.input_hours = document.createElement("INPUT");
	this.input_hours.type = "text";
	this.input_hours.maxLength = 5;
	this.input_hours.size = 5;
	this.input_hours.value = subject.hours ? subject.hours : "";
	td.appendChild(this.input_hours);
	/** Select to choose if the hours are by week or by period */
	this.select_hours_type = document.createElement("SELECT");
	var o;
	o = document.createElement("OPTION");
	o.value = "Per week"; o.text = "Per week";
	this.select_hours_type.add(o);
	o = document.createElement("OPTION");
	o.value = "Per period"; o.text = "Per period";
	this.select_hours_type.add(o);
	switch (subject.hours_type) {
	case "Per week": this.select_hours_type.selectedIndex = 0; break;
	case "Per period": this.select_hours_type.selectedIndex = 1; break;
	}
	td.appendChild(this.select_hours_type);
	this.element.appendChild(this.tr_hours_error = document.createElement("TR"));
	this.tr_hours_error.appendChild(td = document.createElement("TD"));
	td.colSpan = 2;
	
	this.element.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "Coefficient";
	tr.appendChild(td = document.createElement("TD"));
	/** Input fo the coefficient of the subject */
	this.input_coef = document.createElement("INPUT");
	this.input_coef.type = "text";
	this.input_coef.size = "2";
	this.input_coef.maxLength = 2;
	if (subject.coefficient)
		this.input_coef.value = subject.coefficient;
	td.appendChild(this.input_coef);
	this.element.appendChild(this.tr_coef_error = document.createElement("TR"));
	this.tr_coef_error.appendChild(td = document.createElement("TD"));
	td.colSpan = 2;
	
	/**
	 * Signal an error
	 * @param {Element} tr the tr containing the error message
	 * @param {String} msg the error message or null if no error
	 */
	this._error = function(tr, msg) {
		var title = tr.previousSibling;
		title = title.childNodes[0];
		if (msg == null) {
			tr.style.visibility = "hidden";
			tr.style.position = "absolute";
			tr.style.top = "-10000px";
			title.style.color = "black";
		} else {
			tr.style.visibility = "visible";
			tr.style.position = "static";
			title.style.color = "red";
			var td = tr.childNodes[0];
			td.style.color = "red";
			td.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+msg;
		}
	};
	
	/** Validate the data enter by the user */
	this.validate = function() {
		var ok = true;
		// code
		var code = this.input_code.value;
		code = code.trim();
		if (code.length == 0 || !code.checkVisible()) {
			this._error(this.tr_code_error, "Please enter a code");
			ok = false;
		} else {
			var found = false;
			for (var i = 0; i < existing_subjects.length; ++i) {
				var s = existing_subjects[i];
				if (s.id == subject.id) continue;
				if (s.code.toLowerCase() == code.toLowerCase()) {
					found = true;
					break;
				}
			}
			if (found) {
				this._error(this.tr_code_error, "A subject already exists with this code in the same period");
				ok = false;
			} else {
				this._error(this.tr_code_error, null);
			}
		}
		// name
		var name = this.input_name.value;
		name = name.trim();
		if (name.length == 0 || !name.checkVisible()) {
			this._error(this.tr_name_error, "Please enter a name");
			ok = false;
		} else {
			var found = false;
			for (var i = 0; i < existing_subjects.length; ++i) {
				var s = existing_subjects[i];
				if (s.id == subject.id) continue;
				if (s.name.toLowerCase() == name.toLowerCase()) {
					found = true;
					break;
				}
			}
			if (found) {
				this._error(this.tr_name_error, "A subject already exists with this name in the same period");
				ok = false;
			} else {
				this._error(this.tr_name_error, null);
			}
		}
		// hours
		var hours = this.input_hours.value;
		var hours_type = this.select_hours_type.value;
		hours = hours.trim();
		if (hours.length == 0) {
			hours = null;
			hours_type = null;
			this._error(this.tr_hours_error, null);
		} else {
			hours = parseInt(hours);
			if (isNaN(hours)) { 
				ok = false;
				this._error(this.tr_hours_error, "Invalid number");
			} else
				this._error(this.tr_hours_error, null);
		}
		// coefficient
		var coef = this.input_coef.value;
		coef = coef.trim();
		if (coef.length == 0) {
			coef = null;
			this._error(this.tr_coef_error, null);
		} else {
			coef = parseInt(coef);
			if (isNaN(coef)) { 
				ok = false;
				this._error(this.tr_coef_error, "Invalid number");
			} else
				this._error(this.tr_coef_error, null);
		}
		//specializations
		var spe_id = null;
		if (this.radios_spes) {
			for (var i = 0; i < this.radios_spes.length; ++i)
				if (this.radios_spes[i].checked) {
					if (this.radios_spes[i].value > 0)
						spe_id = this.radios_spes[i].value;
					break;
				}
		}
		layout.changed(this.element);
		onvalidation(ok);
		return new CurriculumSubject(subject.id, code, name, subject.category_id, subject.period_id, spe_id, hours, hours_type, coef);
	};
	this.validate();
	
	var t=this;
	this.input_code.onkeyup = function() { t.validate(); };
	this.input_name.onkeyup = function() { t.validate(); };
	this.input_hours.onkeyup = function() { t.validate(); };
	this.input_coef.onkeyup = function() { t.validate(); };
}
