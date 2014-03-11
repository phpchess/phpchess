var _render_types = {
	short_text: { edit: 'textbox', view: 'text' },
	long_text: { edit: 'textarea', view: 'long_text' },
	html: { edit: 'html_editor', view: 'html' },
	select: { edit: 'select', view: 'text' },
	'static': { edit: 'html', view: 'html' },
	tick: {edit: 'tick', view: 'tick'},
	date: {edit: 'date', view: 'date'},
	time: {edit: 'time', view: 'html'},
	radio: { edit: 'radio', view: 'text' },
	check: { edit: 'check', view: 'text' },
	hidden: { edit: 'hidden', view: 'hidden' },
	password: { edit: 'password', view: 'none' },
	email: { edit: 'email', view: 'email' },
	date_time: { edit: 'date_time', view: 'date_time' },
	preview_board: {view: 'board_preview_toggle'},
	edit_game: { edit: 'edit_game', view: 'preview_board' },
	board2fen: { edit: 'board2fen', view: 'text' },
	time_controls: { edit: 'time_controls', view: 'time_controls' }
};



var _input_controls = {
	textbox: function() {
		var self = this;
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts) {
			this._id = id;
			this._opts = opts;
			if(value === null) value = '';
			var style = get_styling(opts.render_opts);
			var html = '<input id="' + id + '" ' + style + ' value="' + value + '" autocapitalize="none" />';
			if(opts.after_text !== undefined)
				html += ' ' + opts.after_text;
			return html;
		}
		this.initialise = function() {
			if(this._opts.onchange !== undefined)
				$('#' + this._id).change(function(){self._handle_onchange()});
			if(this._opts.mask !== undefined && this._opts.mask != '')
				$('#' + this._id).mask(this._opts.mask);
		}
		this._handle_onchange = function() {
			eval(this._opts.onchange);
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
		this.set_value = function(val) {
			$('#' + this._id).val(val);
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
		this.validate = function() {
			var res = true;
			var val = this.get_value();
			if(val == '' && this._opts.required)
			{
				res = {type: 'required', desc: "A value is required for field '" + this._id + "'"};
			}
			else if(val !== '' && !validate_value_format(val, this._opts))
			{
				res = {type: 'type', desc: 'The value for field "' + this._id + "' is of the wrong data type or in the wrong format."};
			}
			return res;
		}
	},
	textarea: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts) {
			this._id = id;
			this._opts = opts;
			var style = get_styling(opts.render_opts);
			return '<textarea id="' + id + '" ' + style + '>' + value + '</textarea>';
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	html_editor: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			return '<textarea id="' + id + '">' + value + '</textarea>';
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	select: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			var style = get_styling(opts.render_opts);
			var multi = (opts.multi == true ? ' multiple ' : '');
			var html = '<select id="' + id + '"' + multi + style + '>';
			var items = opts.values;
			if(!$.isArray(value)) value = [value];
			if(items !== undefined)
			{
				// If this is optional, add '-- select --' option
				if(!opts.required && multi == '')
					html += '<option value="">-- select --</option>';
				if($.isArray(items))	// array
				{
					var list = {};
					for(var i = 0; i < items.length; i++)
					{
						html += '<option value="' + items[i] + '"';
						if($.inArray(items[i], value) > -1) html += ' selected="selected" ';
						html += '>' + items[i] + '</option>';
					}
				}
				else	// hash
				{
					for(var key in items)
					{
						html += '<option value="' + key + '"';
						if($.inArray(key, value) > -1) html += ' selected="selected" ';
						html += '>' + items[key] + '</option>';
					}
				}
			}
			html += '</select>';
			return html;
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	tick: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			var style = get_styling(opts.render_opts);
			var html = '<input id="' + id + '" type="checkbox" value="1" ';
			if(value == '1') html += 'checked ';
			html += '/>';
			if(opts.tick_settings)
			{
				if(opts.tick_settings.label)
					html += ' <label for="' + id + '">' + opts.tick_settings.label + '</label>';
			}
			return html;
		}
		this.get_value = function(){
			if($('#' + this._id + ':checked').length == 1)
				return 1;
			else
				return 0;
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	date: function() {
		var _id = undefined;
		var _opts = undefined;
		var _value = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			this._value = value;
			return '<input type="text" id="' + id + '" />';
		}
		this.initialise = function(){
			var options = {};
			if(this._opts['change_month'] == true) options['changeMonth'] = true;
			if(this._opts['change_year'] == true) options['changeYear'] = true;
			if(this._opts.year_range) options['yearRange'] = this._opts.year_range;
			options['dateFormat'] = 'dd/mm/yy';
			$('#' + this._id).datepicker(options);
			if(this._value === null) return;
			try
			{
				var date = $.datepicker.parseDate('yy-mm-dd', this._value);
				$('#' + this._id).datepicker("setDate", date);
			}
			catch(ex){}
		}
		this.get_value = function(){
			var date = $('#' + this._id).datepicker("getDate");
			var val = $.datepicker.formatDate('yy-mm-dd', date);
			return val;
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	time: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			return '<input type="text" id="' + id + '" value="' + value + '"/>';
		}
		this.initialise = function(){
			$('#' + this._id).mask('99:99');
		}
		this.validate = function(id, opts){
			return true;
		}
		this.get_value = function() {
			var val = $('#' + this._id).val();
			if(val != '') val += ':00';
			return val;
		}
		this.set_focus = function() {
			$('#' + this._id).focus();
		}
	},
	radio: function() {
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts) {
			this._id = id;
			this._opts = opts;
			var html = '<div id="' + id + '" /><table>';
			var items = opts.values;
			var cnt = 0;
			if(items !== undefined)
			{
				if($.isArray(items))	// array
				{
					var list = {};
					for(var i = 0; i < items.length; i++)
					{
						html += '<tr>';
						html += '<td style="vertical-align: top"><input id="' + id + '_' + cnt + '" type="radio" name="' + id + '" value="' + items[i] + '"';
						if(items[i] == value) html += ' checked ';
						html += '/></td><td><label for="' + id + '_' + (cnt++) + '">' + items[i] + '</label></td>';
						html += '</tr>';
					}
				}
				else	// hash
				{
					for(var key in items)
					{
						html += '<tr>';
						html += '<td style="vertical-align: top"><input id="' + id + '_' + cnt + '"type="radio" name="' + id + '" value="' + key + '"';
						if(key == value) html += ' checked ';
						html += '/></td><td><label for="' + id + '_' + (cnt++) + '">' + items[key] + '</label></td>';
						html += '</tr>';
					}
				}
			}
			else
				html += 'No items provided!';
			html += '</table></div>';
			return html;
		}
		this.get_value = function() {
			var val = $('input[name=' + this._id + ']:checked').val();
			if(val === undefined) val = '';
			return val;
		}
	},
	check: function() {
		var _id = undefined;
		var _opts = undefined;
		var _items = {};
		this.render = function(value, id, opts){
			this._id = id;
			this._opts = opts;
			var html = '';
			var items = opts.values;
			if(!$.isArray(value)) value = [value];
			if(items !== undefined)
			{
				// if($.isArray(items))	// convert array to hash
				// {
					// var list = {};
					// for(var i = 0; i < items.length; i++)
					// {
						// list[items[i]] = items[i];
					// }
					// items = list;
				// }
				// this._items = items;
				// html += '<table>';
				// for(var key in items)
				// {
					// html += '<tr>';
					// html += '<td style="vertical-align: top"><input id="' + id + '_' + key + '" name="' + id + '" type="checkbox" value="' + key + '"';
					// if($.inArray(key, value) > -1) html += ' checked ';
					// html += '></td><td><label for="' + id + '_' + key + '">' + items[key] + '</label></td>';
					// html += '</tr>';
				// }
				// html += '</table>';
				
				if($.isArray(items))	// convert array to hash
				{
					var list = {};
					for(var i = 0; i < items.length; i++)
					{
						list[items[i]] = items[i];
					}
					items = list;
				}
				this._items = items;
				
				// Add div around table to limit height if specified. 
				html += '<div id="' + id + '"';
				if(opts.check_opts !== undefined && opts.check_opts.height)
					html += ' style="height: ' + opts.check_opts.height + '; overflow-y: scroll"';
				html += '>';
				html += '<table>';
				// Items may be grouped. For each key check if the value is a hash.
				for(var key in items)
				{
					if($.isPlainObject(items[key]))
					{
						html += '<tr><td colspan="2"><b>' + key + '</b></td></tr>';
						for(var key2 in items[key])
						{
							html += '<tr>';
							html += '<td style="vertical-align: top"><input id="' + id + '_' + key2 + '" name="' + id + '" type="checkbox" value="' + key2 + '"';
							if($.inArray(key2, value) > -1) html += ' checked ';
							html += '></td><td><label for="' + id + '_' + key2 + '">' + items[key][key2] + '</label></td>';
							html += '</tr>';
						}
					}
					else
					{
						html += '<tr>';
						html += '<td style="vertical-align: top"><input id="' + id + '_' + key + '" name="' + id + '" type="checkbox" value="' + key + '"';
						if($.inArray(key, value) > -1) html += ' checked ';
						html += '></td><td><label for="' + id + '_' + key + '">' + items[key] + '</label></td>';
						html += '</tr>';
					}
				}
				html += '</table>';
				html += '</div>';
			}
			return html;
		}
		this.get_value = function(){
			var values = [];
			var checked = $('input[name=' + this._id + ']:checked');
			for(var i = 0; i < checked.length; i++)
			{
				var el = checked[i];
				values.push($(el).val());
			}
			return values;
		}
	},
	html: function() {
		var _value = undefined;
		var _id = undefined;
		var _opts = undefined;
		this.render = function(value, id, opts){
			if(value === null) value = '';
			this._value = value;
			this._id = id;
			this._opts = opts;

			if(opts.values !== undefined)	// Attribute has a list of possible values
			{
				if($.isPlainObject(opts.values))
				{
					var tmp = {};
					// Values may be grouped. For each key check if the value is an object.
					// If so add each item in the group to the temp array.
					for(var item in opts.values)
					{
						if($.isPlainObject(opts.values[item]))
						{
							for(grpitem in opts.values[item])
							{
								tmp[grpitem] = opts.values[item][grpitem];
							}
						}
						else
							tmp[item] = opts.values[item];
					}
					//console.log(tmp);
					opts.values = tmp;
				}
			
				if($.isArray(value))	// Multiple values selected.
				{
					var vals = value;
					if($.isPlainObject(opts.values))
					{
						vals = [];
						for(var i = 0; i < value.length; i++)
						{
							vals.push(opts.values[value[i]]);
						}
					}
					val = vals.join(', ');
				}
				else	// One value selected.
				{
					// opts.values may be an array or array of key/value pairs.
					if($.isArray(opts.values)){
						if(in_array(opts.values, value))
							val = value;
					}
					else
						val = opts.values[value];
				}
				if(val === undefined) val = "ERROR: Unable to obtain value";
				value = val;
			}
			if(opts.after_text !== undefined)
				value += ' ' + opts.after_text;
				
			var html = '<div id="' + id + '">' + value + '</div>';
			return html;
		}
		this.get_value = function(){
			return this._value;
		}
		this.set_value = function(val){
			this._value = val;
			$('#' + this._id).html(val);
		}
	},
	hidden: function() {
		var _value = undefined;
		this.render = function(value, id, opts){
			this._value = value;
			return '<input id="' + id + '" type="hidden" value="' + value + '" />';
		}
		this.get_value = function() {
			return this._value;
		}
	},
	password: function() {
		var _id;
		var _opts;
		this.render = function(value, id, opts) {
			this._id = id;
			this._opts = opts;
			return '<input id="' + id + '" type="password" />';
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
	},
	email: function() {
		var _id;
		var _opts;
		this.render = function(value, id, opts) {
			this._id = id;
			this._opts = opts;
			if(value === null) value = '';
			var maxlength = opts['length'] !== undefined ? 'maxlength="' + opts.length + '"' : '';
			return '<input id="' + id + '" value="' + value + '" ' + maxlength + ' autocapitalize="none" />';
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
	},
	date_time: function() {
		this._id = undefined;
		this._ids = {date: undefined, hour: undefined, minute: undefined, second: undefined};
		this._opts = undefined;
		this._value = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._ids.date = id + '-date';
			this._ids.hour = id + '-hour';
			this._ids.minute = id + '-minute';
			this._ids.second = id + '-second';
			this._opts = opts;
			this._value = value;
			var html = '<div id="' + id + '-container">';
			html += 'Date: <input type="text" id="' + this._ids.date + '" />';
			html += ' H: <input type="text" id="' + this._ids.hour + '" size="2" maxlength="2" />';
			html += ' M: <input type="text" id="' + this._ids.minute + '" size="2" maxlength="2" />';
			html += ' S: <input type="text" id="' + this._ids.second + '" size="2" maxlength="2" />';
			html += '</div>';
			return html;
		}
		this.initialise = function(){
			if(this._value === null) return;
			var d = new Date();
			d.setTime(this._value * 1000);
			var parts = {y: d.getUTCFullYear(), m: d.getUTCMonth() + 1, d: d.getUTCDate(), H: d.getUTCHours(), M: d.getUTCMinutes(), S: d.getUTCSeconds()};
			if(parts.m < 10) parts.m = '0' + parts.m;
			if(parts.d < 10) parts.d = '0' + parts.d;
			$('#' + this._ids.hour).val(parts.H);
			$('#' + this._ids.minute).val(parts.M);
			$('#' + this._ids.second).val(parts.S);
			$('#' + this._ids.date).val(parts.y + '-' + parts.m + '-' + parts.d);
			var options = {};
			if(this._opts['change_month'] == true) options['changeMonth'] = true;
			if(this._opts['change_year'] == true) options['changeYear'] = true;
			if(this._opts.year_range) options['yearRange'] = this._opts.year_range;
			options['dateFormat'] = 'yy-mm-dd';
			$('#' + this._ids.date).datepicker(options);
			try
			{
				var date = $.datepicker.parseDate('yy-mm-dd', parts.y + '-' + parts.m + '-' + parts.d);
				$('#' + this._ids.date).datepicker("setDate", date);
			}
			catch(ex){}
			//console.log(this._value, this.get_value());
		}
		this.get_value = function(){
			var date = $('#' + this._ids.date).datepicker("getDate");
			var tz = date.getTimezoneOffset();
			var h = parseInt($('#' + this._ids.hour).val());
			var m = parseInt($('#' + this._ids.minute).val());
			var s = parseInt($('#' + this._ids.second).val());
			//console.log(date, tz, h + tz / -60, h, m, s);
			date.setHours(h + tz / -60);	// !!! tz is different depending on the time of year!
			date.setUTCMinutes(m);
			date.setUTCSeconds(s);
			var ts = $.datepicker.formatDate('@', date) / 1000;
			return ts;
		}
		this.set_focus = function() {
			$('#' + this._ids.date).focus();
		}
	},
	_edit_game: function() {
		//todo
	},
	board2fen: function() {
		this._id = undefined;
		this._opts = undefined;
		this._value = undefined;
		this.render = function(value, id, opts)
		{
			this._id = id;
			this._value = value;
			this._opts = opts;
			var html = '<input type="text" id="' + id + '" size="60" /><input type="button" id="' + id + '_fen_btn" value="Create FEN" />';
			return html;
		}
		this.initialise = function()
		{
			$('#' + this._id + '_fen_btn').click(function() {
				var url = "../pgnviewer/board2fen.html";
				var hWnd = window.open(url,"796a0a8a771a0798b90dff04df48f4d7", "width=580, height=420, resizable=no, scrollbars=yes, status=yes");
				if(hWnd != null && hWnd.opener == null){ 
					hWnd.opener=self;
					window.name="home";
					hWnd.location.href=url; 
				}
			});
		}
		this.get_value = function() {
			return $('#' + this._id).val();
		}
	},
	time_controls: function() {
		this._id = undefined;
		this._opts = undefined;
		this._value = undefined;
		this.render = function(value, id, opts)
		{
			this._id = id;
			this._value = value;
			this._opts = opts;
			var str1 = 'moves adds';
			var str2 = 'minutes';
			if(opts.lang !== undefined)
			{
				if(opts.lang.moves_adds !== undefined) str1 = opts.lang.moves_adds;
				if(opts.lang.minutes !== undefined) str2 = opts.lang.minutes;
			}
			var html = '<input type="text" id="' + id + '_m1" size="2" maxlength="4" /> ' + str1 + ' <input type="text" id="' + id + '_t1" size="2" maxlength="4"/> ' + str2 + '<br/>';
			html += '<input type="text" id="' + id + '_m2" size="2" maxlength="4"/> ' + str1 + ' <input type="text" id="' + id + '_t2" size="2" maxlength="4"/> ' + str2;
			return html;
		}
		this.initialise = function()
		{
			// Cannot change time controls after they have been created. Therefore no init required.
		}
		this.get_value = function()
		{
			var values = {move1: '', move2: '', time1: '', time2: ''};
			values.move1 = $('#' + this._id + '_m1').val();
			values.move2 = $('#' + this._id + '_m2').val();
			values.time1 = $('#' + this._id + '_t1').val();
			values.time2 = $('#' + this._id + '_t2').val();
			return values;
		}
		this.validate = function() {
			var res = true;
			var val = this.get_value();
			// if(val == '' && this._opts.required)
			// {
				// res = {type: 'required', desc: "A value is required for field '" + this._id + "'"};
			// }
			// else if(val !== '' && !validate_value_format(val, this._opts))
			// {
				// res = {type: 'type', desc: 'The value for field "' + this._id + "' is of the wrong data type or in the wrong format."};
			// }
			if(val.move1 != '')
			{
				if(!validate_value_format(val.move1, {validate_as: 'integer', unsigned: true}))
					res = {type: 'type', desc: 'Invalid value entered for `moves` field in time control 1'};
				else if(!validate_value_format(val.time1, {validate_as: 'integer', unsigned: true}))
					res = {type: 'type'}
				if(val.move2 != '')
				{
					if(!validate_value_format(val.move2, {validate_as: 'integer', unsigned: true}))
						res = {type: 'type'}
					else if(!validate_value_format(val.time2, {validate_as: 'integer', unsigned: true}))
						res = {type: 'type'}
				}
			}
			return res;
		}
	}
};

var _output_controls = {
	text: function() {
		this._id = undefined;
		this.render = function(value, id, opts){
			var val = '';
			this._id = id;
			//console.log(id, value, opts);
			if(value === null) return '';
			if(opts.values !== undefined)	// Attribute has a list of possible values
			{
				if($.isPlainObject(opts.values))
				{
					var tmp = {};
					// Values may be grouped. For each key check if the value is an object.
					// If so add each item in the group to the temp array.
					for(var item in opts.values)
					{
						if($.isPlainObject(opts.values[item]))
						{
							for(grpitem in opts.values[item])
							{
								tmp[grpitem] = opts.values[item][grpitem];
							}
						}
						else
							tmp[item] = opts.values[item];
					}
					//console.log(tmp);
					opts.values = tmp;
				}
			
				if($.isArray(value))	// Multiple values selected.
				{
					var vals = value;
					if($.isPlainObject(opts.values))
					{
						vals = [];
						for(var i = 0; i < value.length; i++)
						{
							vals.push(opts.values[value[i]]);
						}
					}
					val = vals.join(', ');
				}
				else	// One value selected.
				{
					// opts.values may be an array or array of key/value pairs.
					if($.isArray(opts.values)){
						if(in_array(opts.values, value))
							val = value;
					}
					else
						val = opts.values[value];
				}
				if(val === undefined) val = "ERROR: Unable to obtain value";
			}
			else
			{
				val = value;
			}
			if(opts.after_text !== undefined)
				val += ' ' + opts.after_text;
			return val;
		}
		this.set_value = function(val){
			$('#' + this._id).html(val);
		}
	},
	long_text: function() {
		this.render= function(value, id, opts){
			return value.replace(/\n/g, '<br/>');
		};
	},
	tick: function() {
		this.render = function(value, id, opts){
			var t = 'true';
			var f = 'false';
			if(opts.tick_settings !== undefined)
			{
				if(opts.tick_settings.true_val !== undefined)
					t = opts.tick_settings.true_val;
				if(opts.tick_settings.false_val !== undefined)
					f = opts.tick_settings.false_val;
			}
			return value == 1 ? t : f;
		};
	},
	date: function() {
		this.render = function(value, id, opts){
			if(this._value === null) return '';
			var date;
			try
			{
				date = $.datepicker.parseDate('yy-mm-dd', value);
				date = $.datepicker.formatDate('dd/mm/yy', date);
			}
			catch(ex){}
			return date !== undefined ? date : '';
		};
	},
	html: function() {
		this._value = undefined;
		this._id = undefined;
		this.render = function(value, id, opts){
			this._id = id;
			this._value = value;
			html = '<div id="' + id + '">' + value + '</div>';
			return html;
		}
		this.get_value = function(){
			return this._value;
		}
		this.set_value = function(val){
			this._value = val;
			$('#' + this._id).html(val);
		}
	},
	hidden: function() {
		this.render = function(value, id, opts){
			return '';
		}
	},
	none: function() {
		this.render = function(value, id, opts) { return ''; }
		this.get_value = function() { return ''; }
	},
	email: function() {
		this.render = function(value, id, opts) {
			if(value === null || value === '') return '';
			return '<a href="mailto:' + value + '">' + value + '</a>';
		}
	},
	date_time: function() {
		this.render = function(value, id, opts) {
			var d = new Date();
			d.setTime(value * 1000);
			var parts = {y: d.getUTCFullYear(), m: d.getUTCMonth() + 1, d: d.getUTCDate(), H: d.getUTCHours(), M: d.getUTCMinutes(), S: d.getUTCSeconds()};
			if(parts.m < 10) parts.m = '0' + parts.m;
			if(parts.d < 10) parts.d = '0' + parts.d;
			if(parts.H < 10) parts.H = '0' + parts.H;
			if(parts.M < 10) parts.M = '0' + parts.M;
			if(parts.S < 10) parts.S = '0' + parts.S;
			var str = parts.y + '-' + parts.m + '-' + parts.d + ' ' + parts.H + ':' + parts.M + ':' + parts.S;
			return str;
		}
	},
	board_preview_toggle: function() {
		var self = this;
		this._game_id = undefined;
		this._id = undefined;
		this._showing_board = false;
		this._board = undefined;
		this._fen = undefined;
		this.render = function(value, id, opts) {
			this._id = opts.instance_id + '_' + id;
			this._game_id = opts.instance_id;
			html = '<div id="' + this._id + '" style="color: green; cursor: pointer;">View</div>';
			html += '<div style="position: relative; margin: 10px; display: none"><div id="' + this._id + '_board"></div></div>';
			return html;
		}
		this.initialise = function(value, id, opts) {
			$('#' + this._id).click(function(e){
				if(!self._showing_board)
				{
					if(self._fen === undefined)
						$.post('', {_request_game_fen: self._game_id}, self._got_fen);
					else
						self._got_fen({fen: self._fen});
				}
				else
				{
					//$(this).html('View');
					//self._board = undefined;
					//self._showing_board = false;
				}
				e.stopPropagation();
			});
		}
		this._got_fen = function(data) {
			var options = {
				container: $('#' + self._id + '_board'),
				pieces: ChessHelper.get_pieces_from_FEN(data.fen),
				width: 40,
				set: {location: '../modules/RealTimeInterface/img_chess/', white_files: ['wkw.gif', 'wqw.gif', 'wbw.gif', 'wnw.gif', 'wrw.gif', 'wpw.gif'], black_files: ['bkw.gif', 'bqw.gif', 'bbw.gif', 'bnw.gif', 'brw.gif', 'bpw.gif'] },
				border_width: 20,
				side: ChessConst.SIDE.white
			}
			self._board = new ChessBoard();
			self._board.initialise(options);
			self._showing_board = true;
			self._fen = data.fen;
			$('#' + self._id + '_board').parent().dialog({
				height: 430, width: 390, modal: true, resizable: false, draggable: false,
				close: function(event, ui){ 
					self._board = undefined;
					self._showing_board = false;
				}
			});
		}
	},
	preview_board: function() {		// Use only in forms
		var self = this;
		this._id = undefined;
		this._board = undefined;
		this._board_width = 300;
		this._board_border_width = 20;
		this._board_tile_width = undefined;
		this._value = undefined;
		this._opts = undefined;
		this.render = function(value, id, opts) {
			this._value = value;
			this._id = id;
			this._opts = opts;
			this._game_id = opts.game_id;
			if('board_width' in opts && opts.board_width != '' && opts.board_width != 0) this._board_width = opts.board_width;
			if('board_border_width' in opts && opts.board_border_width != '' && opts.board_border_width != 0) this._board_border_width = opts.board_border_width;
			this._board_tile_width = (this._board_width - 2 * this._board_border_width) / 8;
			// Force containing div to be the same size as the board. Otherwise the board will appear above other
			// dom elements due to its relative positioning.
			var html = '<table><tr><td><div style="position: relative; width: ' + this._board_width + 'px; height: ' + this._board_width + 'px; "><div id="' + id + '"></div></div></td><td><div id="' + id + '_moves"></div></td></tr></table>';
			return html;
		}
		this.initialise = function(value, id, opts) {
		//console.log('init', value, opts);
			
			var C = value.fen != '' ? new Chess(value.fen) : new Chess();
			// Apply all moves
			for(var i = 0; i < value.moves_san.length; i++)
			{
				var ret = C.move(value.moves_san[i]);
				//console.log(ret);
			}
			//console.log(C.ascii());
			
			var options = {
				container: $('#' + self._id),
				pieces: ChessHelper.get_pieces_from_FEN(C.fen()),
				width: self._board_tile_width,
				set: opts.set,
				border_width: self._board_border_width,
				side: ChessConst.SIDE.white,
				turn: ChessConst.SIDE.white
			}
			self._board = new ChessBoard();
			self._board.initialise(options);
			self.display_moves(value.moves_san);
		}
		this.display_moves = function(moves)
		{
			var html = '';
			var fullmove = 0;
			var move = 0;
			var class_;
			for(var i = 0; i < moves.length; i++)
			{
				class_ = (i%2 == 0 ? 'white' : 'black');
				if(i == moves.length - 1) class_ += ' move_current';
				if(i % 2 == 0)
				{
					fullmove++;
					html += ' ' + fullmove + '.';
				}
				move++;
				html += '&nbsp<span class="move_element move_' + class_ + '" fullmove=' + fullmove + ' move="' + move + '" >' + moves[i] + '</span>';
			}
			$('#' + self._id + '_moves').html(html);
			$('.move_element').click(function(){
				var current_move = $(this).attr('move')
				$('.move_current').removeClass('move_current');
				$(this).addClass('move_current');
				
				// setup board for the selected move.
				$('#' + self._id).html('');
			
				var C = self._value.fen != '' ? new Chess(self._value.fen) : new Chess();
				for(var i = 0; i < current_move; i++)
				{
					var ret = C.move(moves[i]);
					//console.log(ret);
				}
				//console.log(C.ascii());
				
				var options = {
					container: $('#' + self._id),
					pieces: ChessHelper.get_pieces_from_FEN(C.fen()),
					width: self._board_tile_width,
					set: self._opts.set,
					border_width: self._board_border_width,
					side: ChessConst.SIDE.white,
					turn: ChessConst.SIDE.none
				}
				self._board = new ChessBoard();
				self._board.initialise(options);
			});
		},
		this.get_value = function()
		{
			return "";
		}
	}
};
_input_controls.edit_game = _output_controls.preview_board;		// For now there is no game editing control, so use his.

function get_styling(opts)
{
	if(opts == undefined) return '';
	var style = [];
	if(opts.width !== undefined) style.push('width: ' + opts.width);
	if(opts.height !== undefined) style.push('height: ' + opts.height);
	return 'style="' + style.join('; ') + '" ';
}

function in_array(array, value)
{
	for(var i = 0; i < array.length; i++)
		if(array[i] == value) return true;
	return false;
}

function validate_value_format(value, opts)
{
	var patt;
	var success = true;
	var validation = (opts.validate_as !== undefined ? opts.validate_as : opts.type);

	if(validation == 'integer')
	{
		if(opts.unsigned)
			patt = /^\d+$/;
		else
			patt = /^-?\d+$/;
		value = value.trim();
		success = patt.test(value);
	}
	else if(validation == 'float')
	{
		if(opts.unsigned)
			patt = /^\d+$|^\.\d+$|^\d+\.?\d*$/;
		else
			patt = /^-?\d+$|^-?\.\d+$|^-?\d+\.?\d*$/;
		value = value.trim();
		success = patt.test(value);
	}
	else if(validation == 'name')
	{
	
	}
	else if(validation == 'email')
	{
		alert('email validation is not yet implemented');
	}
	
	return success;
}

// Add trim function to the String object if it doesn't exist.
if (!String.prototype.trim) {
	String.prototype.trim = function(){
		return this.replace(/^\s+|\s+$/g, '');
	};
}
