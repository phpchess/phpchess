function Form()
{
	var self = this;
	this.options = undefined;
	this.instance = undefined;
	this.state = {merged_opts: undefined, submission_in_progress: false};
	this.layout = undefined;
	this.container = undefined;
	this.pk = undefined;			// Holds the primary key value of the instance in use.
	
	this.attr_ctrl_map = {};
	this.LANG = {
		validation_errors: 'Encountered Validation Errors:',
		field: 'Field',
		value_required: 'A value is required',
		expected_positive_int: 'A positive integer value is expected.',
		expected_int: 'A integer value is expected.',
		expected_positive_number: 'A positive number is expected.',
		expected_number: 'A number is expected.',
		invalid_chars_or_format: 'The value contains invalid characters or is in the wrong format',
		expected_unique: 'The value must be unique. Another record already uses this value',
		value_too_long: 'The value is too long.',
		value_invalid_list: 'The value is invalid. Please use one from this list: ',
		unmet_db_constraint: 'The value does not meet a constraint set in the database'
	};
	
	// Create the form given:
	// container: DOM element to contain the form.
	// options: object containing the model info and field options
	// instance: object representing the record being worked with
	this.create = function(container, options, instance)
	{
		this.container = container;
		this.options = options;
		this.instance = instance;

		//console.log(options, instance);
		this.state.merged_opts = merge_objects(options.model_options, options.fields);
		//console.log(this.state.merged_opts);
		for(var prop in options.model_options)
		{
			if(options.model_options[prop].primary)
			{
				this.pk = instance[prop];
				break;
			}
		}
		this.setup_form();
		
		if(options.callbacks !== undefined)
		{
			if(options.callbacks.post_form_setup !== undefined)
				options.callbacks.post_form_setup(this);
		}
		
		$('#debug').html("SUCCESS");
	}
	
	this.destroy = function()
	{
		// Do I need to destroy ever object created or is it enough to delete the form instance?
		
		// Delete all the DOM elements created.
		if(self.layout === undefined)
		{
			self.container.empty();
		}
		
		// If a custom layout is used, may need to remove certain dom element.
		// Custom layout DOM needs to be hidden.
		if(self.layout !== undefined)
		{
			self.layout.find('[_form_hidden_elements]').empty();
			self.layout.find('[_form_ctrl_element=controller_cancel]').empty();
			self.layout.find('[_form_ctrl_element=controller_save]').empty();
			self.layout.find('[_form_ctrl_element=controller_delete]').empty();
			self.layout.find('#form_errors').hide();
			self.layout.hide();
		}
	}
	
	this.setup_form = function()
	{
		var fields = this.get_fields();
		var form_type = (this.options.type == 'create' || this.options.type == 'update') ? 'edit' : 'view';
		var html = '';
		
		if(this.options.predefined_layout !== undefined)
		{
			html = this.setup_using_predefined_layout(fields, form_type, this.options.predefined_layout);
			this.run_form_initialisation(fields, form_type);
		}
		else
		{
			html = this.setup_generic_form(fields, form_type);
			this.container.html(html);
			this.run_form_initialisation(fields, form_type);
		}
			
		$('#controller_save').click(function(){self.save();});
		$('#controller_delete').click(function(){self._delete();});
		$('#controller_cancel').click(function(){self.cancel();});
	}
	
	this.setup_generic_form = function(fields, type)
	{
		var func = (type == 'edit' ? this.render_attribute_as_edit : this.render_attribute_as_view);
		var label_style = '';
		if(this.options.layout && this.options.layout.labels)
		{
			if(this.options.layout.labels.width)
				label_style += 'width: ' + this.options.layout.labels.width;
		}
		var hidden = '';
		var html = '<form>\n';
		html += '<div id="form_errors" class="_ajax_form_errors" style="display: none"></div>';
		html += '<table class="_ajax_form_table">\n';
		for(var attr in fields)
		{
			if(this.state.merged_opts[attr] !== undefined && this.state.merged_opts[attr].render_as == 'hidden')
				hidden += func.call(this, this.instance, attr);
			else
			{
				var label = fields[attr];
				html += '<tr>\n';
				html += '<td class="_ajax_form_label_cell" style="' + label_style + '"><div id="' + attr + '_label_container" class="_ajax_form_label" >' + label + '</div></td><td class="_ajax_form_control_cell" ><div id="' + attr + '_container" class="_ajax_form_control" >' + func.call(this, this.instance, attr) + '</div></td>\n';
				html += '</tr>\n';
			}
		}
		html += '</table>\n';
		
		// Add hidden fields plus add the instance id as a special hidden field.
		hidden += '<input id="-id" type="hidden" value="' + this.instance.id + '" />';
		html += hidden;
		
		html += '</form>';		// End form here. Otherwise clicking buttons will force a form submit.
		
		// Add buttons.
		html += '<div class="_ajax_form_controllers">';
		if(this.options.cancel === undefined || this.options.cancel == true)
			html += '<button id="controller_cancel">Cancel</button>';
		if(type == "edit")
		{
			html += '<button id="controller_save">Save</button>';
		}
		if(this.options.type == 'delete')
			html += '<button id="controller_delete">Delete</button>';
		html += '</div>';
		
		return html;
	}
	
	this.setup_using_predefined_layout = function(fields, type, layout)
	{
		var func = (type == 'edit' ? this.render_attribute_as_edit : this.render_attribute_as_view);
		var label_style = '';
		var id = layout.id;
		var hidden = '';
		this.layout = $('#' + layout.id);
		if(this.options.layout && this.options.layout.labels)
		{
			if(this.options.layout.labels.width)
				label_style += 'width: ' + this.options.layout.labels.width;
		}
		for(var attr in fields)
		{
			if(this.state.merged_opts[attr] !== undefined && this.state.merged_opts[attr].render_as == 'hidden')
				hidden += func.call(this, this.instance, attr);
			else
			{
				// $('#' + id + ' [_form_element=' + attr + ']').empty();
				// $('#' + id + ' [_form_element=' + attr + ']').html(func.call(this, this.instance, attr));
				$('#' + id + '_form_element_' + attr).empty().html(func.call(this, this.instance, attr));
			}
		}
		// Add hidden fields plus add the instance id as a special hidden field.
		hidden += '<input id="-id" type="hidden" value="' + this.instance.id + '" />';
		$('#' + id + ' [_form_hidden_elements]').append(hidden);
		
		// Add controllers
		if(this.options.cancel === undefined || this.options.cancel == true)
			$('#' + id + ' [_form_ctrl_element=controller_cancel]').html('<button id="controller_cancel">Cancel</button>');
		if(type == 'edit')
			$('#' + id + ' [_form_ctrl_element=controller_save]').html('<button id="controller_save">Save</button>');
		if(this.options.type == 'delete')
			$('#' + id + ' [_form_ctrl_element=controller_delete]').html('<button id="controller_delete">Delete</button>');
	
		this.layout.show();
	}
	
	// Once the form html has been generated, it may be necessary to run some intialisation functions.
	// For example date fields need to be initialised using jquery.
	this.run_form_initialisation = function(fields, type)
	{
		for(var attr in fields)
		{
			this.initialise_field(this.instance, attr, type); 
		}
	}
	
	// Gets the fields that are needed for the form. It first checks if the 'order' option was set.
	// If not it gets the fields from the 'model' option.
	this.get_fields = function()
	{
		var fields = {};
		var field_order = [];
		
		if(this.options.order === undefined || this.options.order.length == 0)
		{
			field_order = get_array_keys(this.options.model_options);
		}
		else
		{
			field_order = this.options.order;
		}
		for(var i = 0; i < field_order.length; i++)
		{
			var label = this.format_to_heading_text(field_order[i]);
			if(this.options.fields !== undefined && this.options.fields[field_order[i]] !== undefined && this.options.fields[field_order[i]].label !== undefined)
				label = this.options.fields[field_order[i]].label;
			fields[field_order[i]] = label;
		}
		return fields;
	}
	
	this.render_attribute_as_view = function(instance, attr)
	{
		var html = '';
		//console.log(instance, attr);
		var ctrl = this.get_control_for_attribute(attr, 'view');
		html = ctrl.render(instance[attr] !== undefined ? instance[attr] : '', attr, this.state.merged_opts[attr]);
		this.attr_ctrl_map[attr] = ctrl;
		return html;
	}

	this.render_attribute_as_edit = function(instance, attr)
	{
		var html = '';
		var ctrl = this.get_control_for_attribute(attr, 'edit');
		// Get the value to assign from the record, unless a value is specified in the merged options.
		// This is used to set the value of static fields.
		var val = instance[attr];
		if(this.state.merged_opts[attr].value !== undefined)
			val = this.state.merged_opts[attr].value
		html = ctrl.render(val, attr, this.state.merged_opts[attr]);
		this.attr_ctrl_map[attr] = ctrl;
		return html;
	}
	
	this.initialise_field = function(instance, attr, render_type)
	{
		var ctrl = this.attr_ctrl_map[attr];
		if(ctrl.initialise !== undefined)
			ctrl.initialise(instance[attr], attr, this.state.merged_opts[attr]);
	},
	
	this.get_control_for_attribute = function(attr, mode)
	{
		var ctrl = false;
		var render_type = 'text';
		
		// First check if there is an input or output ctrl specified in the fields option. If not check for 
		// a render type being set. If that doesn't exist either, get a default render type from the attribute
		// data type.
		if(mode == 'view' && this.options.fields[attr] !== undefined && this.options.fields[attr].output_as !== undefined)
		{
			ctrl = this.get_output_control(this.options.fields[attr].output_as);
		}
		else if(mode == 'edit' && this.options.fields[attr] !== undefined && this.options.fields[attr].input_as !== undefined)
		{
			ctrl = this.get_input_control(this.options.fields[attr].input_as);
		}
		
		if(ctrl === false)
		{
			if(this.options.fields[attr] !== undefined && this.options.fields[attr].render_as !== undefined)
			{
				render_type = this.options.fields[attr].render_as;
				var ctrls = this.get_controls_for_render_type(render_type);
				ctrl = ctrls[mode];
				//console.log(ctrls);
			}
		}
		
		if(ctrl === false)
		{
			var ctrls = this.get_default_controls_for_attribute(attr);
			ctrl = ctrls[mode];
			if(ctrl === false) 
			{
				ctrls = this.get_dummy_control();
				ctrl = ctrls[mode];
			}
			
		}
		
		ctrl = new ctrl();
		return ctrl;
	}

	this.get_controls_for_render_type = function(render_type)
	{
		var input_ctrl = false;
		var output_ctrl = false;
		if(_render_types[render_type] !== undefined)
		{
			input_ctrl = _render_types[render_type].edit;
			if(_input_controls[input_ctrl] !== undefined)
				input_ctrl = _input_controls[input_ctrl];
			else
				input_ctrl = _input_controls['textbox'];
			output_ctrl = _render_types[render_type].view;
			if(_output_controls[output_ctrl] !== undefined)
				output_ctrl = _output_controls[output_ctrl];
			else
				output_ctrl = _output_controls['text'];
		}
		
		return {edit: input_ctrl, view: output_ctrl};
	}

	this.get_default_controls_for_attribute = function(attr)
	{
		var input_ctrl = undefined;
		var output_ctrl = undefined;
		var opts = this.options.model_options[attr];
		if(opts === undefined) return this.get_controls_for_render_type('short_text');
		if(opts.type == 'string')
		{
			if(opts.length < 100)
				return this.get_controls_for_render_type('short_text');
			else
				return this.get_controls_for_render_type('long_text');
		}
		else if(opts.type == 'integer' || opts.type == 'float')
		{
			return this.get_controls_for_render_type('short_text');
		}
		else if(opts.type == 'enum')
		{
			return this.get_controls_for_render_type('select');
		}
		else if(opts.type == 'date')
		{
			return this.get_controls_for_render_type('date');
		}
		else if(opts.type == 'time')
		{
			return this.get_controls_for_render_type('time');
		}
		
		// Default to simple text entry/display if nothing else matched.
		return this.get_controls_for_render_type('short_text');
	}

	this.get_input_control = function(ctrl)
	{
		if(_input_controls[ctrl] !== undefined)
			return _input_controls[ctrl];
		
		return false;
	}

	this.get_output_control = function(ctrl)
	{
		if(_output_controls[ctrl] !== undefined)
			return _output_controls[ctrl];
		
		return false;
	}

	// If get_control_for_attribute() fails for any reason return a dummy control which
	// just displays an error message.
	this.get_dummy_control = function()
	{
		return {
			edit: function() {
				this.render = function() {
					return "ERROR";
				};
				this.get_value = function() {
					return undefined;
				};
			},
			view: function() {
				this.render = function() {
					return "ERROR";
				};
			}
		};
	}
	
	this.cancel = function()
	{
		if(this.options.parent_ui_callback !== undefined)
		{
			this.options.parent_ui_callback({require_update: false});
			return;
		}
	}
	
	this.save = function()
	{
		if(this.state.submission_in_progress) return;
		this.state.submission_in_progress = true;
		// var updated_inst = {};
		// for(var attr in this.instance)
		// {
			// //updated_inst[attr] = this.instance[attr];
			
			// //var ctrl = this.get_control_for_attribute(attr, 'edit');
			// var ctrl = this.attr_ctrl_map[attr];
			// //console.log('found', attr, ctrl, ctrl.get_value(attr));
			// var val = ctrl.get_value();
			// if(val !== undefined)
			// {
				// updated_inst[attr] = val;	// Apply new value.
			// }
		// }
		var fields = this.get_fields();
		var values = {};
		var errors = {};
		var validation_errors = false;
		for(var field in fields)
		{
			var ctrl = this.attr_ctrl_map[field];
			var val = ctrl.get_value();
			if(ctrl.validate)
			{
				var res = ctrl.validate();
				if(res !== true)
				{
					errors[field] = res;
					validation_errors = true;
				}
			}
			else
			{
				if(val === '' && ctrl._opts.required)
				{
					errors[field] = {type: 'required', desc: "A value is required for field '" + field + "'"};
					validation_errors = true;
				}
			}
			
			if(val !== undefined)
			{
				values[field] = val;
			}
			else
				values[field] = '';
		}
		
		if(validation_errors)
		{
			this.display_errors({error: 'validation_failed', validation_errors: errors});
			this.state.submission_in_progress = false;
			return;
		}
		//console.log(values);
		
		$.post(this.options.action_callback, {tbl_action: this.options.type, form_action: this.options.type, instance: values, pk: this.pk}, function(data){ 
			self.save_response(data); 
		}).fail(function() { 
			self.state.submission_in_progress = false;
			alert('Communication Error');
		});
	};

	this.save_response = function(data)
	{
		this.state.submission_in_progress = false;
		$('#form_errors').hide();
		try
		{
			//console.log(data);
			//data = $.parseJSON(data);
			if(data == null)
			{
				alert('Received no response from the server.');
				return;
			}
			//console.log(data);
		}
		catch(ex)
		{
			alert('Unable to parse reply from server. Your session may have expired, please log back in. If this problem persists contact the site admin.');
			return;
		}
		
		if(data.success) 
		{
			$('#debug').append('Saved!');
			if(this.options.parent_ui_callback !== undefined)
			{
				this.options.parent_ui_callback({require_update: true, data: data});
				return;
			}
			return;
		}
		
		//alert('Encountered errors on form submission');
		this.display_errors(data);
			
	}
	
	this.display_errors = function(data)
	{
		if(this.options.custom_error_display !== undefined)
		{
			this.options.custom_error_display(data, this);
		}
		else
		{
			var errors = '';
			var fields = this.get_fields();
			if(data.error == 'validation_failed')
			{
				errors += '<p>' + self.LANG.validation_errors + '</p>';
				for(var field in data.validation_errors)
				{
					var err = data.validation_errors[field];
					
					//errors += '<p class="_ajax_error_item" onclick="$(\'#' + field + '\').focus().get(0).scrollIntoView();" >Field <b>' + field + '</b>: ' + this.generate_error_message_for_field(err, field) + '</p>';
					errors += '<p class="_ajax_error_item" _field="' + field + '" >' + self.LANG.field + ' <b>' + fields[field] + '</b>: ' + this.generate_error_message_for_field(err, field) + '</p>';
				}
				$('#form_errors').html(errors);
				$('#form_errors ._ajax_error_item').click(function(){
					var field = $(this).attr('_field');
					// Note: some fields are composed of multiple items. Need a way to get the first item to scroll to.
					// would be good to use the actual control and say ctrl.scrollTo() and it works out where to scroll 
					// to.
					var offset = $('#' + field).offset();
					offset.top -= 20;
					offset.left -= 20;
					$('html, body').animate({scrollTop: offset.top, scrollLeft: offset.left});
					var ctrl = self.attr_ctrl_map[field];
					if(ctrl.set_focus) ctrl.set_focus();
					//$('#' + field).get(0).scrollIntoView();
				});
			}
			$('#form_errors').show();
			$('#form_errors').get(0).scrollIntoView();
		}
	}
	
	// Works out what message to display for a given field and error object.
	// Returns a message string.
	this.generate_error_message_for_field = function(error, field)
	{
		var field_opts = this.state.merged_opts[field];
	//console.log(field_opts);
		if(error.type == 'required')
			msg = self.LANG.value_required;
		else if(error.type == 'type')
		{
			if(field_opts.type == 'integer')
			{
				if(field_opts.unsigned)
					msg = self.LANG.expected_positive_int;
				else
					msg = self.LANG.expected_int;
			}
			else if(field_opts.type == 'float')
			{
				if(field_opts.unsigned)
					msg = self.LANG.expected_positive_number;
				else
					msg = self.LANG.expected_number;
			}
			else
			{
				msg = self.LANG.invalid_chars_or_format;
			}
		}
		else if(error.type == 'unique')
		{
			msg = self.LANG.expected_unique;
		}
		else if(error.type == 'length')
		{
			msg = self.LANG.value_too_long; 
		}
		else if(error.type == 'enum')
		{
			msg = self.LANG.value_invalid_list + error.values.join(', ');
		}
		else if(error.type == 'constraint')
		{
			msg = self.LANG.unmet_db_constraint;
		}
		else if(error.type == 'custom')
		{
			msg = error.error_msg;
		}
		else
		{
			msg = error.type;
		}
		
		return msg;
	}
	
	this._delete = function()
	{
		if(this.state.submission_in_progress) return;
		this.state.submission_in_progress = true;
		
		if(!confirm('Are you sure you wish to delete this record?\nThis action cannot be undone.'))
		{
			this.state.submission_in_progress = false;
			return;
		}
		
		$.post(this.options.action_callback, {tbl_action: this.options.type, form_action: this.options.type, instance: this.instance, pk: this.pk}, function(data) {
			self.delete_response(data);
		}).fail(function() {
			self.state.submission_in_progress = false;
			alert('Communication Error');
		});
	}
	
	this.delete_response = function(data)
	{
		this.state.submission_in_progress = false;
		try
		{
			//data = $.parseJSON(data);
			if(data == null)
			{
				alert('Received no response from the server');
				return;
			}
		}
		catch(ex)
		{
			alert('Unable to parse reply from server. Your session may have expired, please log back in. If this problem persists contact the site admin.');
			return;
		}
		
		if(data.success)
		{
			if(this.options.parent_ui_callback !== undefined)
			{
				this.options.parent_ui_callback({require_update: true, data: data});
				return;
			}
		}
		
		alert("Unable to delete the record. Error message:\n" + data.msg);
	}
	
	this.format_to_heading_text = function(text)
	{
		text = text.replace(/_/g, " ");
		var parts = text.split(" ");
		for(var i = 0; i < parts.length; i++)
		{
			var item = parts[i];
			var c = item.substr(0, 1);
			c = c.toUpperCase();
			parts[i] = c + item.substr(1);
		}
		return parts.join(' ');
	}
	
}