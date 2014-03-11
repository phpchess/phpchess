function Table()
{
	var self = this;
	this.el_id = undefined;	// The dom element the table is created in.
	this.options = {};
	this.state = {
		merged_opts: {}	// Stores the column options and model options together in one object. Column options override values from the model options.
	};	
	this.Form = undefined;			// Hold the form object when a form is used.
	this.inst_ctrl_map = {};	// Holds controls used for each instance, indexed by attribute
	this.grid_opts = {};

	this.initialise = function(id, opts)
	{
		this.options = opts;
		this.state.merged_opts = merge_objects(opts.model_options, opts.columns);
		console.log(this.state.merged_opts);
		this.grid_opts = this.generate_flexigrid_options($.extend({}, opts));
		this.grid_opts['datacol'] = {'*': this.render_column};
		this.grid_opts['onSuccess'] = this.data_added;
		console.log(this.grid_opts);
		$(id).flexigrid(this.grid_opts);
		this.el_id = id;
	}
	
	this.generate_flexigrid_options = function(opts)
	{
		opts['dataType'] = 'json';
		opts['colModel'] = [];
		opts['datacol'] = {};
		for(var i = 0; i < opts.order.length; i++)
		{
			var colopts = {};
			var col = opts.order[i];
			if(opts.columns[col] !== undefined)
			{
				colopts = opts.columns[col];
				colopts['display'] = colopts.label !== undefined ? colopts.label : self.format_to_heading_text(col);
				colopts['name'] = col;
				delete colopts.label;
			}
			else
			{
				colopts = {display: self.format_to_heading_text(col), name: col};
			}
			if(colopts.align === undefined) colopts.align = 'left';
			if(colopts.sortable === undefined) colopts.sortable = true;
			opts.colModel.push(colopts);
		}
		
		var buttons = [];
		for(var key in opts.controllers)
		{
			var ctrl = opts.controllers[key];
			var btn_opts = {name: ctrl.label, bclass: key, onpress: this.button_clicked};
			buttons.push(btn_opts);
		}
		if(buttons.length > 0) opts['buttons'] = buttons;
		
		var searchable = [];
		for(var i = 0; i < opts.colModel.length; i++)
		{
			searchable.push({display: opts.colModel[i].display, name: opts.colModel[i].name});
		}
		if(searchable.length > 0) opts['searchitems'] = searchable;
		
		delete opts.columns;
		delete opts.controllers;
		delete opts.order;
		
		return opts;
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
	
	this.button_clicked = function(button, grid)
	{	
		var pk = undefined;
		var action = undefined;
		
		$.each($('.trSelected', grid),
			function(key, value){
				pk = value.id.substring(3);
				//console.log(pk, key, value);
		});
		
		// Match button with controller provided in the options.
		for(var key in self.options.controllers)
		{
			if(self.options.controllers[key].label == button)
				action = key;
				//console.log('hello', key);
		}
		
		if(action)
		{
			if(action != 'create' && pk === undefined)
			{
				alert('You must select a record for this action');
				return;
			}
			$.post(self.action_callback, {tbl_action: action, pk: pk}, self.button_return);
		}
	}
	
	this.render_column = function(val, col, id)
	{
		var html = self.render_attribute_as_view(val, col, id);
		return html;
	}
	
	this.render_attribute_as_view = function(value, attr, id)
	{
		var html = '';
		//console.log(value, attr);
		var ctrl = this.get_control_for_attribute(attr, 'view');
		//console.log(value, attr, ctrl);
		var opts = this.state.merged_opts[attr] || {};
		opts = $.extend({}, opts, {instance_id: id});
		html = ctrl.render(value !== undefined ? value : '', attr, opts);
		if(this.inst_ctrl_map[id] == undefined) this.inst_ctrl_map[id] = {};
		this.inst_ctrl_map[id][attr] = ctrl;
		return html;
	}

	this.render_attribute_as_edit = function(value, attr)
	{
		var html = '';
		var ctrl = this.get_control_for_attribute(attr, 'edit');
		// Use passed value, unless a value is specified in the merged options.
		// This is used to set the value of static column.
		if(this.state.merged_opts[attr].value !== undefined)
			value = this.state.merged_opts[attr].value
		html = ctrl.render(value, attr, this.state.merged_opts[attr]);
		this.inst_ctrl_map[attr] = ctrl;
		return html;
	}
		
	this.get_control_for_attribute = function(attr, mode)
	{
		var ctrl = false;
		var render_type = 'text';
		
		// First check if there is an input or output ctrl specified in the columns option. If not check for 
		// a render type being set. If that doesn't exist either, get a default render type from the attribute
		// data type.
		if(mode == 'view' && this.options.columns[attr] !== undefined && this.options.columns[attr].output_as !== undefined)
		{
			ctrl = this.get_output_control(this.options.columns[attr].output_as);
		}
		else if(mode == 'edit' && this.options.columns[attr] !== undefined && this.options.columns[attr].input_as !== undefined)
		{
			ctrl = this.get_input_control(this.options.columns[attr].input_as);
		}
		
		if(ctrl === false)
		{
			if(this.options.columns[attr] !== undefined && this.options.columns[attr].render_as !== undefined)
			{
				render_type = this.options.columns[attr].render_as;
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

	
	this.button_return = function(data)
	{
		console.log(data);
		
		if(data.success)
		{
			self.Form = new Form();
			data.form_init_options.parent_ui_callback = self.form_return;
			self.Form.create($('#form'), data.form_init_options, data.instance);
			$('#form').show();
			$('.flexigrid').hide();
		}
		else
		{
			alert(data.msg);
		}
		
	}
	
	this.form_return = function(args)
	{
		$('.flexigrid').show();
		if(args.require_update)
		{
			$(self.el_id).flexigrid().flexReload();
		}
		self.Form.destroy();
		delete self.Form;
		// self.show_table();
		
		if(self.options.on_form_close !== undefined)
			self.options.on_form_close();
	}
	
	this.data_added = function()
	{
		for(instance_id in self.inst_ctrl_map)
		{
			var ctrls = self.inst_ctrl_map[instance_id];
			for(attr in ctrls)
			{
				if(ctrls[attr].initialise !== undefined)
				{
					var opts = self.state.merged_opts[attr] || {};
					opts = $.extend({}, opts, {instance_id: instance_id});
					ctrls[attr].initialise(null, attr, opts);
					
				}
			}
		}
	}
}