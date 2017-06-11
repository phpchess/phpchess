<?php
/*get fields
get constraints (primary, unique)
get relationships

for related objects get fields, etc...

draw table:

generate query to count players:
- where (search string if set)

generate query:
- select fields
- where (search string if set)
- order by (if set)
- Limit/Offset (if count > allowed on a page)

add row
foreach field to display (see order array)
	add header cell using field name or label for field (see fields array)

foreach item in the query result
	add row to table
	for each field to display (see order array)
		add cell and display content (use render_cell_content(content, data_type, fieldname))
	add cells for actions (see actions array):
		edit, delete
		generate urls: current_url + '&action=edit&id=[id]' or '&action=delete&id=[id]'
	add cells for custom actions?
		generate urls: current_url + '&action=[custom_action]&id=[id]'
endeach

Also add search textbox and 'add' button for creating new players



action forms:





classes:

Renderer::Table
Renderer::Form
Renderer::DataModel
- get fields, constraints, relationships
- create instance for each table being worked with
- handles converting mysql types to basic types: string, integer, float, date, time, enum, binary
- also maps fields to default render types (input/output)
*/


class Table
{
	var $model_def;			// Stores the definition of the model being used.
	var $column_options;	// Stores the column options used to initialise the table.
	var $fetch_opts;		// Options to use in generating the query to fetch data.
	var $model_name;		// The model name (db::table)

	// Holds arrays of callbacks used to process model instances for use on the client side and for
	// use on the server side. Eg: array('client' => array('cbeads\User' => callback, 'cbeads\Application' => callback), 'server' => array('cbeads\User'))
	public $model_value_processors = array('client' => array(), 'server' => array());
	
	function initialise($options)
	{
		$result = $this->process_table_options($options);
		if(isset($result['error']))
		{
			return array('success' => FALSE, 'msg' => $ret['error']);
		}
		$ret = array('success' => TRUE, 'table_init_options' => $result);
		return $ret;
	}
	
	private function process_table_options($options)
	{
		//var_dump($options);
		
		if(!isset($options['model']))
		{
			return array('error' => 'Need to provide the ` model ` option!');
		}
		$model_def = ModelManager::get_model_definition($options['model']);
		$order = (isset($options['order']) ? $options['order'] : $model_def->get_properties_list());
		$columns = (isset($options['columns']) ? $options['columns'] : array());
		$controllers = (isset($options['controllers']) ? $options['controllers'] : array());
		//$filter_by_dql = (isset($options['filter_by_dql']) ? $options['filter_by_dql'] : NULL);

		$can_search = $can_update = $can_create = $can_delete = $can_view = TRUE;
		$def_ctrls = array('update', 'view', 'delete', 'create');
		foreach($def_ctrls as $ctrl)
		{
			$allowed = TRUE;
			if(isset($options[$ctrl]))
			{
				if($options[$ctrl] == FALSE)
					$allowed = FALSE;
			}
			if($allowed)
			{
				// If not included in the controller order array (when is not empty), then it is the
				// same as setting this controller to false.
				if(isset($options['controller_order']) && count($options['controller_order']) != 0 && 
					!in_array($ctrl, $options['controller_order']))
				{
					$allowed = FALSE;
				}
			}
			if($allowed && !isset($controllers[$ctrl]))
			{
				$controllers[$ctrl] = array('label' => ucfirst($ctrl));
			}
		}
		
		
		$this->model_def = $model_def;
		$this->column_options = $columns;
		$this->model_name = $options['model'];
		//$this->filter_options[$data_source['model_name']] = $filter_by_dql;

		//$this->table_get_editable_attributes($order, $columns, $model);
		
		unset($options['data_source'], $options['callback'], $options['model']);
		$options['order'] = $order;
		$options['columns'] = $columns;
		$options['controllers'] = $controllers;
		
		$model_options = array();
		foreach($this->model_def->get_property_definitions() as $prop => $def)
		{
			$tmp = $def['options'];
			unset($tmp['ntype']);
			$model_opt = array_merge(array('type' => $def['type'], 'length' => $def['length']), $tmp);
			$model_options[$prop] = $model_opt;
		}
		$options['model_options'] = $model_options;
		
		return $options;
	}

	public function handle_table_action($action, $options)
	{
		$options = $this->process_table_options($options);
		if(isset($options['error']))
		{
			return array('success' => FALSE, 'msg' => $options['error']);
		}

		if($action == 'data')
		{
			$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
			$rp = isset($_POST['rp']) ? (int)$_POST['rp'] : 10;
			$sortname = isset($_POST['sortname']) && $_POST['sortname'] != 'undefined' ? $_POST['sortname'] : FALSE;
			$sortorder = isset($_POST['sortorder']) ? ($_POST['sortorder'] == 'asc' ? 'ASC' : 'DESC') : 'DESC';
			$search_string = isset($_POST['query']) ? ($_POST['query'] != '' ? $_POST['query'] : false) : false;
			$search_field = isset($_POST['qtype']) ? ($_POST['qtype'] != '' ? $_POST['qtype'] : false) : false;
			$this->fetch_opts = array('offset' => $page, 'amount' => $rp, 'sortfield' => $sortname, 'sortorder' => $sortorder, 'search_field' => $search_field, 'search_string' => $search_string);
			$result = $this->get_records();
			// Records can be processed before being sent to the client side.
			if(isset($this->model_value_processors['client'][$this->model_name]) && is_callable($this->model_value_processors['client'][$this->model_name])) // ?? Does model_value_processors have to be an array?
			{
				call_user_func($this->model_value_processors['client'][$this->model_name], array('instances' => &$result['records']));
			}
			
			$jsonData = array('page'=>$page,'total'=>0,'rows'=>array());
			$jsonData['total'] = $result['total'];
			$jsonData['rows'] = $result['records'];
			return $jsonData;
		}
		
		if($action == 'create' || $action == 'update' || $action == 'delete' || $action = 'view')
		{
			// Is this action allowed?
			if(isset($options['controller_order']) && count($options['controller_order']) > 0)
			{
				if(!in_array($action, $options['controller_order']))
					return array('success' => FALSE, 'msg' => "The action '$action' is not allowed");
			}
			else
			{
				if(isset($options[$action]) && $options[$action] == FALSE)
					return array('success' => FALSE, 'msg' => "The action '$action' is not allowed");
			}
			
			// A primary key value is required for any action other than 'create'
			$id = isset($_POST['pk']) ? ($_POST['pk'] !== '' ? $_POST['pk'] : NULL) : NULL;
			if($action != 'create' && $id === NULL)
			{
				return array('success' => FALSE, 'msg' => "A record id value is required for this action ('$action')");
			}
		
			// First apply table options such as order and column options.
			$form_options = array(
				'model' => $this->model_def,
				'model_name' => $this->model_name,
				'fields' => $this->column_options,
				'order' => $options['order'],
				'record_id' => $id
			);
			if(isset($options['form_options']))
			{
				// Then apply default form options if they exist.
				if(isset($options['form_options']['default']))
					$form_options = $this->merge_assoc_arrays($form_options, $options['form_options']['default']);
				// Finally apply specific form options if they exist.
				if($action == 'create' && isset($options['form_options']['create']))
				{
					$form_options = $this->merge_assoc_arrays($form_options, $options['form_options']['create']);
				}
				if($action == 'update' && isset($options['form_options']['update']))
				{
					$form_options = $this->merge_assoc_arrays($form_options, $options['form_options']['update']);
				}
				if($action == 'delete' && isset($options['form_options']['delete']))
				{
					$form_options = $this->merge_assoc_arrays($form_options, $options['form_options']['delete']);
				}
			}
			$form_options['type'] = $action;
			
			$Form = new Form();
			$Form->model_value_processors = $this->model_value_processors;
			if(!isset($_POST['instance'])) // no instance data, setup form
			{
				$result = $Form->initialise($form_options);
				return $result;
			}
			$result = $Form->handle_form_action($action, $form_options);
			if(!$result['success'])
				return $result;
			
			return array('success' => TRUE);
			
			// $result = $this->handle_form_action($form_options);
			// if(!$result['success'])
				// return $result;
			// // On success return a new list of instances that the table should display.
			// $instances = $this->get_records();
			// if(isset($this->model_value_processors['client'][$this->model_name]) && is_callable($this->model_value_processors['client'][$this->model_name]))
			// {
				// call_user_func($this->model_value_processors['client'][$this->model_name], array('instances' => &$instances));
			// }
			// $result['instances'] = $instances;
			// return $result;
		}
		//else if($action == 'search' || $ac
	}
	
	
	private function get_records()
	{
		$pk = $this->model_def->get_primary_key();
		$props = $this->model_def->get_properties_list();
	
		$query = "SELECT * FROM `" . $this->model_def->namespace . "`.`" . $this->model_def->name . "` ";
		$nonlimit_query = "SELECT count(`$pk`) as `count` FROM `" . $this->model_def->namespace . "`.`" . $this->model_def->name . "` ";
		$params = array();
		
		$search_field = $this->fetch_opts['search_field'];
		$search_string = $this->fetch_opts['search_string'];
		$offset = $this->fetch_opts['offset'];
		$amount = $this->fetch_opts['amount'];
		$sortname = $this->fetch_opts['sortfield'];
		$sortorder = $this->fetch_opts['sortorder'];
		//var_dump($this->fetch_opts);
		if($search_string !== FALSE && $search_field !== FALSE)
		{
			$query .= "WHERE $search_field LIKE ? ";
			$nonlimit_query .= "WHERE $search_field LIKE ? ";
			//$params[] = $search_field;
			$params[] = "%$search_string%";
		}
		$offset = ($offset - 1) * $amount;
		if($sortname)
			$query .= "ORDER BY $sortname $sortorder ";
		$query .= "LIMIT $offset, $amount";
		// var_dump($params);
		// echo $query;
		$all = ModelManager::find_where($this->model_name, $query, $params);
		$records = array();
		foreach($all as $item)
		{
			$properties = array();
			foreach($props as $prop)
			{
				$properties[$prop] = $item->$prop;
			}
			$entry = array('id' => $item->$pk, 'cell' => $properties);
			$records[] = $entry;
		}
		// Need to get total results available for pagination.
		//echo "counting $nonlimit_query";
		$count = DB::query_getone($nonlimit_query, $params);
		return array('records' => $records, 'total' => $count['count']);
	}
	
	// Utility functions
	
	// Merges two associative arrays together recursively. If both contain the same key, the value
	// from the 2nd array (a2) is taken. Returns a new associative array.
	private function merge_assoc_arrays($a1, $a2)
	{
	//cbeads_nice_vardump($a1);
	//cbeads_nice_vardump($a2);
		$keys1 = array_keys($a1);
		$keys2 = array_keys($a2);
		$keys = $this->merge_arrays($keys1, $keys2, false);
		$new = array();
		foreach($keys as $key)
		{
			if(isset($a1[$key]) && isset($a2[$key]))
			{
				if($this->is_assoc($a1[$key]) && $this->is_assoc($a2[$key]))
					$new[$key] = $this->merge_assoc_arrays($a1[$key], $a2[$key], false);
				else
					$new[$key] = $a2[$key];
			}
			else
			{
				$new[$key] = (isset($a1[$key]) ? $a1[$key] : $a2[$key]);
			}
		}
		return $new;
	}
	
	// Merges contents of two arrays into one. If allow_duplicates is set to true, then duplicate items
	// in both arrays will be allowed. If set to false then only unique items will be returned.
	private function merge_arrays($array1, $array2, $allow_duplicates)
	{
		$array = array();
		$used = array();
		foreach($array1 as $val)
		{
			if(!isset($used[$val]) || $allow_duplicates)
			{
				$used[$val] = true;
				$array[] = $val;
			}
		}
		foreach($array2 as $val)
		{
			if(!isset($used[$val]) || $allow_duplicates)
			{
				$used[$val] = true;
				$array[] = $val;
			}
		}
		return $array;
	}

	// Tests if the given array is an associative array. (Taken from
	// http://au1.php.net/manual/en/function.is-array.php#89332 , except I removed the 2nd array_keys() call
	// because it seems uneccessary).
	function is_assoc($var)
	{
		return is_array($var) && array_diff_key($var, array_keys($var));
	}
	
}

class Form
{
	private $model_def;			// Stores the definition of the model being used.
	private $model_name;		// The model name (db::table)
	private $options;			// Stores all the options used to initialise the form.
	private $field_options;		// Stores the field specific options.
	private $controllers;		// Stores the controllers (buttons/actions) options.
	
	private $requested_attributes;		// Holds the attributes that have been requested and will be sent to the client side.
	private $editable_attributes;		// Holds which attributes of the model instance can be altered.
	
	// Holds arrays of callbacks used to process model instances for use on the client side and for
	// use on the server side. Eg: array('client' => array('cbeads\User' => callback, 'cbeads\Application' => callback), 'server' => array('cbeads\User'))
	public $model_value_processors = array('client' => array(), 'server' => array());
	
	public function initialise($options)
	{
		// Generates initialisation code.
		$ret = $this->process_form_options($options);
		if(isset($ret['error']))
		{
			return array('success' => FALSE, 'msg' => $ret['error']);
		}
		//var_dump($ret, $this->editable_attributes, $this->requested_attributes);
		//exit();
		
		if($this->options['type'] == 'create')
			$instance = $this->get_record();
		else
			$instance = $this->get_record($this->options['record_id']);
		$instance = $this->process_instance_for_client($instance);
		
		//$script = json_encode($ret);
		//cbeads_nice_vardump($instance);
		//$instance = json_encode($instance);
		
		return array('success' => TRUE, 'form_init_options' => $ret, 'instance' => $instance);
	}
	
	public function handle_form_action($action, $options)
	{
		if(!isset($_POST['instance']))
			return array('success' => FALSE, 'msg' => 'No object instance received. Cannot proceed.');
		if($action != $options['type'])
			return array('success' => FALSE, 'msg' => 'Action requested is not allowed. Cannot proceed.');
		$this->process_form_options($options);
		// Check if a controller option was set. If the saving or delete options are set to false. In those
		// cases check if a callback was set. Invoke the callback and return as nothing else needs to be done.
		if(isset($this->controllers[$action]))
		{
			$ctrl = $this->controllers[$action];
			if((isset($ctrl['save']) && $ctrl['save'] == FALSE) ||
			   (isset($ctrl['delete']) && $ctrl['delete'] == FALSE))
			{
				if(isset($ctrl['callback']) && is_callable($ctrl['callback']))
				{
					$result = call_user_func($ctrl['callback'], array());
					return $result;
				}
				return array('success' => TRUE, 'notice' => 'no save');
			}
		}
		if($action == 'create')
			return $this->create_record();
		elseif($action == 'update')
			return $this->update_record();
		elseif($action == 'delete')
			return $this->delete_record();
		else
			return array('success' => FALSE, 'msg' => 'Invalid Table Action Requested');
	}
	
	private function process_form_options($options)
	{
		$this->model_def = $options['model'];
		$this->model_name = $options['model_name'];
		$this->field_options = isset($options['fields']) ? $options['fields'] : array();
		$this->controllers = isset($options['controllers']) ? $options['controllers'] : array();
		$this->options = $options;
		
		$model_options = array();
		foreach($this->model_def->get_property_definitions() as $prop => $def)
		{
			$tmp = $def['options'];
			unset($tmp['ntype']);
			$model_opt = array_merge(array('type' => $def['type'], 'length' => $def['length']), $tmp);
			$model_options[$prop] = $model_opt;
		}
		$options['model_options'] = $model_options;
		
		// Get which attributes can be altered (as set in the options) and that need to be sent to the client.
		$order = (isset($options['order']) ? $options['order'] : $this->model_def->get_properties_list());
		$fields = (isset($options['fields']) ? $options['fields'] : array());
		$this->get_editable_attributes($order, $fields, $model_options);
		$this->get_requested_attributes($order, $fields, $model_options);
		
		//if(!isset($options['order'])) $options['order'] = array();
		
		// Work out default validation for each editable field if no validator was expliticly set.
		foreach($this->editable_attributes as $attr)
		{
			if(isset($this->field_options[$attr]['validate_as']))
				continue;
			if(isset($model_options[$attr]))
			{
				$opts = $model_options[$attr];
				if($opts['type'] == 'integer')
				{
					$this->field_options[$attr]['validate_as'] = 'integer';
				}
			}
		}
		$options['order'] = $order;
		$options['fields'] = $this->field_options;
		
		// Clean up the options so they can be sent to the client side. Remove callbacks and other
		// things not needed by the client.
		unset($options['callbacks'], $options['model'], $options['model_name']);
		if(isset($options['controllers']))
		{
			foreach($options['controllers'] as $name => $opts)
			{
				$keys = array_keys($opts);
				$allow = array('label');
				foreach($keys as $key)
				{
					if(!in_array($key, $allow))
						unset($options['controllers'][$name][$key]);
				}
				if(count(array_keys($options['controllers'][$name])) == 0)	// don't want controllers with no options
					unset($options['controllers'][$name]);
			}
			if(count(array_keys($options['controllers'])) == 0)	// remove controllers array completely if no options set
				unset($options['controllers']);
		}
		
		// TODO: Look for inconsistencies in the options
		return $options;
	}
	
	
	
	// Get a new or existing record.
	private function get_record($id = NULL)
	{
		$pk = $this->model_def->get_primary_key();
		$props = $this->model_def->get_properties_list();

		if($id !== NULL)	// Get an existing one
		{
			$instance = ModelManager::find($this->model_name, $id);
			if($instance === FALSE) return FALSE;
			$record = $instance->toArray();
		}
		else	// Get a new one
		{
			$record = array();
			// foreach($this->requested_attributes as $attr)
			// {
				// $record[$attr] = '';
			// }
			foreach($props as $prop)
				$record[$prop] = '';
			return $record;
		}
		return $record;
	}
	
	private function process_instance_for_client($obj)
	{
		if(isset($this->model_value_processors['client'][$this->model_name]) && is_callable($this->model_value_processors['client'][$this->model_name]))
		{
		//cbeads_nice_vardump(array_keys($obj));
			call_user_func($this->model_value_processors['client'][$this->model_name], array('instance' => &$obj));
		}
		return $obj;
	}

	// Returns the attributes that can be edited in a form. This is based on the order provided
	// and if the field option has not been set to explicitly render as static.
	private function get_editable_attributes($order, $fields, $model)
	{
		$editable = array();
		if(count($order) > 0)
		{
			foreach($order as $item)
			{
				// attribute must exist in the model definition
				if(isset($model[$item]))
				{
					if(isset($fields[$item]))
					{
						if(empty($field['is_static']) && (!isset($field['render_as']) || $field['render_as'] != 'static'))
						{
							$editable[] = $item;
						}
					}
					else
					{
						$editable[] = $item;
					}
				}
			}
		}
		else		// No order specified so every can be edited.
		{
			$editable = array_keys($model);
		}
		$this->editable_attributes = $editable;
	}
	
	// When sending objects to the client side this function will return which attributes to include.
	// Only include those attributes specified in the form options. The primary key must always be
	// included.
	private function get_requested_attributes($order, $fields, $model)
	{
		$attributes = array();
		if(count($order) > 0)
		{
			//$attributes = $order;
			// Weed out attributes that don't exist in the model. Up to the programmer to supply values 
			// for additional fields model value processors.
			foreach($order as $item)
			{
				if(in_array($item, array_keys($model)))
					$attributes[] = $item;
			}
		}
		else
			$attributes = $props;
		
		//if(!in_array('id', $attributes))
		//	$attributes[] = 'id';
//cbeads_nice_vardump($order);
//cbeads_nice_vardump($attributes);
		$this->requested_attributes = $attributes;
	}
	
	
	private function create_record()
	{
		$instance_values = $_POST['instance'];
		$model = $this->model_name;
		$obj = new Model($model);
		
		return $this->save_record($obj, $instance_values, 'create');
	}
	
	private function update_record()
	{
		$instance_values = $_POST['instance'];
		//cbeads_nice_vardump($instance_values);
		$record_id = isset($_POST['pk']) ? $_POST['pk'] : NULL;
		// if(!$this->verify_record_can_be_updated($record_id))
		// {
			// return array('success' => FALSE, 'error' => 'access_error', 'msg' => 'Record was not found or cannot be updated');
		// }
		//$new_values = array();
		//$id = $instance_values['id'];
		$obj = ModelManager::find($this->model_name, $record_id);
		if(!$obj)
		{
			return array('success' => FALSE, 'error' => 'access_error', 'msg' => 'Record was not found or cannot be updated');
		}
		//cbeads_nice_vardump($obj->toArray());
		return $ret = $this->save_record($obj, $instance_values, 'update');
	}
	
	private function save_record($obj, $instance_values, $action)
	{
		// Check if a value processor has been defined. It will process the values as required
		// and then the values can be assigned to the object.
		if(isset($this->options['callbacks']['process_values']) && is_callable($this->options['callbacks']['process_values']))
		{
			$result = call_user_func($this->options['callbacks']['process_values'], 
				array(
					'values' => &$instance_values,
					'action' => $action
				)
			);
		}

		$this->apply_values_to_object($obj, $instance_values);
		
		// TODO: check for pre validation callback
		if(isset($this->form_options['callbacks']['pre_validation']) && is_callable($this->form_options['callbacks']['pre_validation']))
		{
			$result = call_user_func($this->form_options['callbacks']['pre_validation'], 
				array(
					'object' => &$obj,
					'action' => $action
				)
			);
			if(!$result['success'])
			{
				return array('success' => FALSE, 'error' => 'validation_failed', 'validation_errors' => $result['errors']);
			}
		}
		
		//cbeads_nice_vardump($obj->toArray());
		$res = $obj->validate();
		//cbeads_nice_vardump($res);
		if($res !== TRUE)
		{
			return array('success' => FALSE, 'error' => 'validation_failed', 'validation_errors' => $res);
		}
		
		// Pre-save validation check
		if(isset($this->form_options['callbacks']['pre_save']) && is_callable($this->form_options['callbacks']['pre_save']))
		{
			$result = call_user_func($this->form_options['callbacks']['pre_save'], 
				array(
					'object' => &$obj
				)
			);
			if(!$result['success'])
			{
				return array('success' => FALSE, 'error' => 'validation_failed', 'validation_errors' => $result['errors']);
			}
		}
		
		// save
		if(!$obj->save())
		{
			//echo "not saving yet";
		
			return array('success' => FALSE, 'error' => 'saving_error', 'msg' => 'Unable to save the record');
		}
		
		// TODO: check for post save callback
		if(isset($this->form_options['callbacks']['post_save']) && is_callable($this->form_options['callbacks']['post_save']))
		{
			$result = call_user_func($this->form_options['callbacks']['post_save'], 
				array(
					'object' => &$obj
				)
			);
			if(!$result['success'])
			{
				return array('success' => FALSE, 'error' => 'post_save_error', 'msg' => $result['errors']);
			}
		}
		
		$res = array('success' => TRUE, 'instance' => $this->process_instance_for_client($obj->toArray()));
		
		return $res;
	}
	
	private function delete_record()
	{
		$record_id = isset($_POST['pk']) ? $_POST['pk'] : NULL;
		// if(!$this->verify_record_can_be_deleted($record_id))
		// {
			// return array('success' => FALSE, 'error' => 'access_error', 'msg' => 'Record was not found or cannot be deleted');
		// }
		$obj = ModelManager::find($this->model_name, $record_id);
		if(!$obj)
		{
			return array('success' => FALSE, 'error' => 'access_error', 'msg' => 'Record was not found or cannot be deleted');
		}
		
		if(isset($this->form_options['callbacks']['pre_deletion']) && is_callable($this->form_options['callbacks']['pre_deletion']))
		{
			$result = call_user_func($this->form_options['callbacks']['pre_deletion'], 
				array(
					'object' => &$obj
				)
			);
			if(!$result['success'])
			{
				return array('success' => FALSE, 'msg' => $result['msg']);
			}
		}
		
		if(!$obj->delete())
		{
			return array('success' => FALSE, 'msg' => 'Unable to deleted the record');
		}
		
		if(isset($this->form_options['callbacks']['post_delete']) && is_callable($this->form_options['callbacks']['post_delete']))
		{
			$result = call_user_func($this->form_options['callbacks']['post_delete'], 
				array(
					'object' => &$obj
				)
			);
			if(!$result['success'])
			{
				return array('success' => FALSE, 'error' => 'post_save_error', 'msg' => $result['msg']);
			}
		}
		
		return array('success' => TRUE);
	}
	
	private function apply_values_to_object($obj, $instance_values)
	{
		$model = $this->model_def->get_property_definitions();
		foreach($this->editable_attributes as $attr)
		{
			if(isset($instance_values[$attr]))
			{
				$val = $instance_values[$attr];
			//$val = $this->process_attribute_value_for_saving($instance_values[$attr], $model[$attr]);
			//var_dump($attr); var_dump($v);
				// Joins elements of an array into a string if a text data type is expected. This
				// is for cases where the underlying data type is a set.
				if(is_array($instance_values[$attr]) && $model[$attr]['type'] == 'text')
				{
					$val = implode(',', $instance_values[$attr]);
				}
				if($val === '')
				{
					$val = NULL;
				}
				$obj->$attr = $val;
			}
		}
	}
	
}

class DB
{
	static $host;
	static $db;
	static $user;
	static $pass;
	static $dbh;
	
	static function init($config)
	{
		include($config);

		DB::$host = $host = $conf['database_host'];
		DB::$db   = $db   = $conf['database_name'];
		DB::$user = $user = $conf['database_login'];
		DB::$pass = $pass = $conf['database_pass'];
		
		// Create a db connection using PDO. Should migrate everything over to use PDO.
		// try {
			// DB::$dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
			// DB::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// } catch (PDOException $e) {
			// print "Error!: " . $e->getMessage() . "<br/>";
			// die();
		// }
		
		mysqli_report(MYSQLI_REPORT_STRICT);
		try {
			DB::$dbh = new mysqli($host, $user, $pass, $db);
			if (DB::$dbh->connect_errno) {
				die( "FAILED TO CONNECT TO THE DB. ERROR: " . DB::$dbh->connect_error );
				exit();
			}
		} catch (mysqli_sql_exception $e) {
			die( "FAILED TO CONNECT TO THE DB. ERROR: " . $e->getMessage() );
		}
		
		//$result = DB::query("SELECT * FROM c4m_avatars");
		//echo '<pre>';var_dump($result);echo '</pre>';
		
		// $result = DB::query_getone("SELECT 'choices to please everybody.' AS _msg FROM DUAL");
		// echo '<pre>';var_dump($result);echo '</pre>';
		
		// $result = DB::query_getone("SELECT * FROM c4m_avatars WHERE a_playerid = 3", array());
		// echo '<pre>';var_dump($result);echo '</pre>';
		
		// $result = DB::query_getone("SHOW FULL COLUMNS FROM `c4m_avatars`");
		// echo '<pre>';print_r($result);echo '</pre>';
		
		//$defs = DBDefinition::mysql_defs_getColumns($db, 'game');
		//echo '<pre>';print_r($defs);echo '</pre>';
	}
	
	static function query($query, $params = array())
	{
		$results = array();
		try {
			if(count($params) == 0)
			{
				$rows = DB::$dbh->query($query);
				if($rows)
				{
					while($row = $rows->fetch_assoc())
						$results[] = $row;
					return $results;
				}
			}
			else
			{
				$stmt = DB::$dbh->prepare($query);
				DB::bind_params($stmt, $params);
				if ($stmt->execute()) {
					return DB::get_results($stmt);
				}
				else
				{
					echo $dbh->error;
				}
			}
		} catch (mysqli_sql_exception $e) {
			echo 'DB error : ' . $e->getMessage() . '<br/>';
			return FALSE;
		}
		
		return FALSE;
	}
	
	static function query_getone($query, $params = array())
	{
		$results = DB::query($query, $params);
		if($results)
			return $results[0];
		return FALSE;
	}
	
	static function insert($query, $params = array())
	{
		$last_id = FALSE;
		try {
			if(count($params) == 0)
			{
				$rows = DB::$dbh->query(query);
				if($rows)
					$last_id = DB::$dbh->insert_id;
				else
					echo DB::$dbh->error;
			}
			else
			{
				$stmt = DB::$dbh->prepare($query);
				DB::bind_params($stmt, $params);
				if ($stmt->execute()) {
					$last_id = DB::$dbh->insert_id;
				}
				else
				{
					echo DB::$dbh->error;
				}
			}
		} catch (mysqli_sql_exception $e) {
			echo 'DB error : ' . $e->getMessage() . '<br/>';
			return FALSE;
		}

		return $last_id;
	}
	
	static function update($query, $params = array())
	{
		try {
			if(count($params) == 0)
			{
				$rows = DB::$dbh->query($query);
				if(!$rows)
				{
					echo DB::$dbh->error;
					return FALSE;
				}
			}
			else
			{
				$stmt = DB::$dbh->prepare($query);
				DB::bind_params($stmt, $params);
				if (!$stmt->execute()) {
					echo $dbh->error;
				}
			}
		} catch (mysqli_sql_exception $e) {
			echo 'DB error : ' . $e->getMessage() . '<br/>';
			return FALSE;
		}

		return TRUE;
	}
	
	static function delete($query, $params = array())
	{
		//echo "Running query: $query with values: "; var_dump($params);
		try {
			if(count($params) == 0)
			{
				$rows = DB::$dbh->query($query);
				if(!$rows)
				{
					echo DB::$dbh->error;
					return FALSE;
				}
			}
			else
			{
				$stmt = DB::$dbh->prepare($query);
				DB::bind_params($stmt, $params);
				if (!$stmt->execute()) {
					echo $dbh->error;
				}
			}
		} catch (mysqli_sql_exception $e) {
			echo 'DB error : ' . $e->getMessage() . '<br/>';
			return FALSE;
		}

		return TRUE;
	}
	
	private static function bind_params($stmt, $params)
	{
		$values = array();
		$bindtypes = '';
		foreach($params as $key => $value)
		{
			$values[] = &$params[$key];
			$type = gettype($value);
			if($type == 'integer')
				$bindtypes .= 'i';
			elseif($type == 'double')
				$bindtypes .= 'd';
			else
				$bindtypes .= 's';
		}
		call_user_func_array(array($stmt, 'bind_param'), array_merge(array($bindtypes), $values));
	}
	
	
	
	private static function get_results($stmt)
	{
		$meta = $stmt->result_metadata();
		while ($field = $meta->fetch_field())
		{
			$params[] = &$row[$field->name];
		}

		call_user_func_array(array($stmt, 'bind_result'), $params);

		$result = array();
		while ($stmt->fetch()) {
			foreach($row as $key => $val)
			{
				$c[$key] = $val;
			}
			$result[] = $c;
		}
	   
		$stmt->close();
		return $result;
	}
	
	private static function refValues($arr)
	{
		if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
		{
			$refs = array();
			foreach($arr as $key => $value)
				$refs[$key] = &$arr[$key];
			return $refs;
		}
		return $arr;
	} 

}

class DBDefinition
{
	// Fetches the column definitions for a given table in a database.
	// The column definitions use simplified data types.
	// namespace - the database name.
	// table     - the table name.
	// Returns an array of column definitions
	static function mysql_defs_getColumns($namespace, $table)
	{
		$definitions = array();
		$columns = DB::query("SHOW FULL COLUMNS FROM `$namespace`.`$table`");
		foreach ($columns as $col) {
			$definitions[] = DBDefinition::mysql_defs_convert_columns($col);
		}

		return $definitions;
	}
	
	
	// This function is adapted from Doctrine ORM (v1.x) and is slightly modified.
	// (See Doctrine\Import\Mysql.php listTableColumns() function).
	// Converts a mysql column definitions into a more general definition.
	// val - the mysql column definition
	static function mysql_defs_convert_columns($val)
	{
		$val = array_change_key_case($val, CASE_LOWER);
		$decl = DBDefinition::mysql_defs_getPortableDeclaration($val);
		$values = isset($decl['values']) ? $decl['values'] : array();
		$val['default'] = $val['default'] == 'CURRENT_TIMESTAMP' ? null : $val['default'];

		$description = array(
						'name'          => $val['field'],
						'type'          => $decl['type'][0],
						'alltypes'      => $decl['type'],
						'ntype'         => $val['type'],
						'length'        => $decl['length'],
						'fixed'         => ((bool) $decl['fixed']),
						'unsigned'      => ((bool) $decl['unsigned']),
						'values'        => $values,
						'primary'       => (strtolower($val['key']) == 'pri'),
						'default'       => $val['default'],
						'notnull'       => ($val['null'] != 'YES') ? TRUE : FALSE,
						'autoincrement' => (strpos($val['extra'], 'auto_increment') !== false) ? TRUE : FALSE
						);
		if (isset($decl['scale'])) {
			$description['scale'] = $decl['scale'];
		}
		
		$options = $description;

		// Construct the options array. This code was taken from Doctrine\Import\Builder.php buildColumns() function
		// --------
		// // Remove name, alltypes, ntype. They are not needed in options array
		unset($options['name']);
		// unset($options['alltypes']);
		// unset($options['ntype']);
		unset($options['type']);

		// Remove notnull => true if the column is primary
		// Primary columns are implied to be notnull in Doctrine
		if (isset($options['primary']) && $options['primary'] == true && (isset($options['notnull']) && $options['notnull'] == true)) {
			unset($options['notnull']);
		}

		// Remove default if the value is 0 and the column is a primary key
		// Doctrine defaults to 0 if it is a primary key
		if (isset($options['primary']) && $options['primary'] == true && (isset($options['default']) && $options['default'] == 0)) {
			unset($options['default']);
		}
		
		// Should try to ensure the default value is the same data type as the field.
		if(isset($options['default']) && $options['type'] == 'integer')
		{
			$options['default'] = (int)$options['default'];
		}

		// Remove null and empty array values
		foreach ($options as $key => $value) {
			if (is_null($value) || (is_array($value) && empty($value))) {
				unset($options[$key]);
			}
		}

	//     if (is_array($options) && !empty($options)) {
	//         $description['options'] = mysql_defs_varExport($options);
	//     }
		$description['options'] = $options;
		//echo $description['options'];
		// --------
		
		// Unset everything that is not 'name', 'type', 'length' or 'options'
		foreach ($description as $key => $value) {
			if ($key != 'name' && $key != 'type' && $key != 'length' && $key != 'options') {
				unset($description[$key]);
			}
		}
		
		return $description;
	}

	/**
	* Maps a native array description of a field to a MDB2 datatype and length
	* (Adapted from Doctrine\DataDict\Mysql.php getPortableDeclaration() function.
	*
	* @param array  $field native field description
	* @return array containing the various possible types, length, sign, fixed
	*/
	static function mysql_defs_getPortableDeclaration(array $field)
	{
		$dbType = strtolower($field['type']);
		$dbType = strtok($dbType, '(), ');
		if ($dbType == 'national') {
			$dbType = strtok('(), ');
		}
		if (isset($field['length'])) {
			$length = $field['length'];
			$decimal = '';
		} else {
			$length = strtok('(), ');
			$decimal = strtok('(), ');
			if ( ! $decimal ) {
				$decimal = null;
			}
		}
		$type = array();
		$unsigned = $fixed = null;

		if ( ! isset($field['name'])) {
			$field['name'] = '';
		}

		$values = null;
		$scale = null;

		switch ($dbType) {
			case 'tinyint':
				$type[] = 'integer';
				$type[] = 'boolean';
				if (preg_match('/^(is|has)/', $field['name'])) {
					$type = array_reverse($type);
				}
				$unsigned = preg_match('/ unsigned/i', $field['type']);
				$length = 1;
			break;
			case 'smallint':
				$type[] = 'integer';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
				$length = 2;
			break;
			case 'mediumint':
				$type[] = 'integer';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
				$length = 3;
			break;
			case 'int':
			case 'integer':
				$type[] = 'integer';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
				$length = 4;
			break;
			case 'bigint':
				$type[] = 'integer';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
				$length = 8;
			break;
			case 'tinytext':
			case 'mediumtext':
			case 'longtext':
			case 'text':
			case 'varchar':
				$fixed = false;
			case 'string':
			case 'char':
				$type[] = 'string';
				if ($length == '1') {
					$type[] = 'boolean';
					if (preg_match('/^(is|has)/', $field['name'])) {
						$type = array_reverse($type);
					}
				} elseif (strstr($dbType, 'text')) {
					$type[] = 'clob';
					if ($decimal == 'binary') {
						$type[] = 'blob';
					}
				}
				if ($fixed !== false) {
					$fixed = true;
				}
			break;
			case 'enum':
				$type[] = 'enum';
				preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
				$length = 0;
				$fixed = false;
				if (is_array($matches)) {
					foreach ($matches[1] as &$value) {
						$value = str_replace('\'\'', '\'', $value);
						$length = max($length, strlen($value));
					}
					if ($length == '1' && count($matches[1]) == 2) {
						$type[] = 'boolean';
						if (preg_match('/^(is|has)/', $field['name'])) {
							$type = array_reverse($type);
						}
					}

					$values = $matches[1];
				}
				$type[] = 'integer';
				break;
			case 'set':
				$fixed = false;
				$type[] = 'text';
				$type[] = 'integer';
			break;
			case 'date':
				$type[] = 'date';
				$length = null;
			break;
			case 'datetime':
			case 'timestamp':
				$type[] = 'timestamp';
				$length = null;
			break;
			case 'time':
				$type[] = 'time';
				$length = null;
			break;
			case 'float':
			case 'double':
			case 'real':
				$type[] = 'float';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
			break;
			case 'unknown':
			case 'decimal':
				if ($decimal !== null) {
					$scale = $decimal;
				}
			case 'numeric':
				$type[] = 'decimal';
				$unsigned = preg_match('/ unsigned/i', $field['type']);
			break;
			case 'tinyblob':
			case 'mediumblob':
			case 'longblob':
			case 'blob':
			case 'binary':
			case 'varbinary':
				$type[] = 'blob';
				$length = null;
			break;
			case 'year':
				$type[] = 'integer';
				$type[] = 'date';
				$length = null;
			break;
			case 'bit':
				$type[] = 'bit';
			break;
			case 'geometry':
			case 'geometrycollection':
			case 'point':
			case 'multipoint':
			case 'linestring':
			case 'multilinestring':
			case 'polygon':
			case 'multipolygon':
				$type[] = 'blob';
				$length = null;
			break;
			default:
				$type[] = $field['type'];
				$length = isset($field['length']) ? $field['length']:null;
		}

		$length = ((int) $length == 0) ? null : (int) $length;
		$def =  array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed);
		if ($values !== null) {
			$def['values'] = $values;
		}
		if ($scale !== null) {
			$def['scale'] = $scale;
		}
		return $def;
	}


	/**
	* Lists table constraints. This was adapted from Doctrine\Import\Mysql.php
	*
	* @param string $table     database table name
	* @return array
	*/
	static function mysql_defs_getConstraints($namespace, $table)
	{
		$keyName = 'Key_name';
		$nonUnique = 'Non_unique';
		$colName = 'Column_name';

		$query = "SHOW INDEX FROM `$namespace`.`$table`";
		
		$indexes = DB::query($query);
	//nice_vardump($indexes);
		$result = array();
		$data = array();
		foreach ($indexes as $indexData) {
			//if ( ! $indexData[$nonUnique]) {
				if ($indexData[$keyName] !== 'PRIMARY') {
					$index = $indexData[$keyName];
				} else {
					$index = 'PRIMARY';
				}
				$data['unique'] = !$indexData[$nonUnique];
				$data['field'] = $indexData[$colName];
				if ( ! empty($index)) {
					$result[$index][] = $data;
				}
			//}
		}
		//nice_vardump($result);
		return $result;
	}
	
}

class Model
{
	var $model_def = NULL;
	var $exists_in_db = FALSE;		// Flag to indicate if this instance has a matching record in the database.
	var $old_values = array();		// Stores the model's properties as they are in the database. Updated when the model is saved.
	
	function __construct($model)
	{
		$this->model_def = $def = ModelManager::get_model_definition($model);
		
		//var_dump($this);
		$this->_setup_properties();
	}
	
	private function _setup_properties()
	{
		foreach($this->model_def->columns_ as $prop => $opts)
		{
			$type = $opts['type'];
			$val = NULL;
			
			// Has default value?
			if(isset($opts['options']['default']))
			{
				$val = $opts['options']['default'];
				if($val === "") $val = NULL;	// Empty strings treated as NULLs. Ideally the default option should not appear if the default value is just an empty string.
			}
			
			// if($val !== NULL)
			// {
				// if($type == "integer")	// force it to be an integer
					// $val = (int)$val;
			// }
			
			$this->$prop = $val;
		}
		$this->set_old_values();
	}
	
	function validate()
	{
		$props = $this->model_def->get_properties_list();
		$errors = array();
		$unique_checks = array();
		$ns = $this->model_def->namespace;
		$tbl = $this->model_def->name;
		
		foreach($props as $prop)
		{
			// Check required values (properties that cannot be NULL)
			$def = $this->model_def->columns_[$prop];
			//var_dump($def);
			$is_primary = isset($def['options']['primary']) ? $def['options']['primary'] : FALSE;
			$is_auto = isset($def['options']['autoincrement']) ? $def['options']['autoincrement'] : FALSE;
			$is_unsigned = isset($def['options']['unsigned']) ? $def['options']['unsigned'] : FALSE;
			$is_unique = $def['options']['unique'];
			$required = isset($def['options']['required']) ? $def['options']['required'] : FALSE;
			
			// if(isset($def['options']['notnull']))
				// $required = $def['options']['notnull'];
			// else
				// $required = TRUE;	// assume it is if no such option exists
			//echo "$prop is required ? " . ($required ? 'yes' : 'no') . '<br/>';
			if($required && $this->$prop === NULL && !$is_auto)
			{
				$errors[$prop] = array('type' => 'required', 'desc' => 'A value is required');
				continue;
			}
			// Special check to make sure a primary key value exists when updating an existing record.
			if($is_primary && $this->$prop === NULL && $this->exists_in_db)
			{
				$errors[$prop] = array('type' => 'required', 'desc' => 'A value is required');
				continue;
			}
			
			// Check data types match
			$type = $def['type'];
			//if(is_string($this->$prop) && ($type == 'integer' || $type == 'float')) 
			
			// Check value for enum data types is valid (if a value was set)
			if($type == 'enum' && $this->$prop !== NULL)
			{
				if(!in_array($this->$prop, $def['options']['values']))
				{
					$errors[$prop] = array(
						'type' => 'enum',
						'desc' => "Value must match one of the following: " . implode(", ", $def['options']['values']), 
						'values' => $def['options']['values']
					);
					continue;
				}
			}
			elseif($type == "string")
			{
				// Check string length is valid
				if($def['length'] && strlen($this->$prop) > $def['length'])
				{
					$errors[$prop] = array('type' => 'length', 'desc' => 'Value is too long. Cannot have more than ' . $def['length'] . ' characters');
					continue;
				}
				// Check numeric values are valid (unsigned, float or integer, etc..)
			}
			
			// Check value is unique if needed (unique/primary constraint)
			if($is_unique)
			{
				$check = TRUE;
				if($this->$prop === NULL)
				{
					if($is_auto) $check = FALSE;
				}
				if($check)
				{
					$unique_checks[$prop] = $this->$prop;
					// If the value for the unique property has changed, check if the new value is unique.
					if($this->$prop != $this->old_values[$prop])
					{
						$query = "SELECT count(*) as `count` FROM `$ns`.`$tbl` WHERE `$prop` = ?";
						$result = DB::query_getone($query, array($this->$prop));
						if($result && $result['count'] > 0)
						{
							$errors[$prop] = array('type' => 'unique', 'desc' => 'Value must be unique.');
						}
					}
				}
			}
			
		}

		if(count($errors) == 0) return TRUE;
		//var_dump($errors);
		return $errors;
	}
	
	function save()
	{
		$ns = $this->model_def->namespace;
		$tbl = $this->model_def->name;
		$pk = $this->model_def->get_primary_key();
		
		$props = $this->model_def->get_properties_list();
		$query = ($this->exists_in_db ? "UPDATE " : "INSERT ") . "`$ns`.`$tbl` SET ";
		$parts = array();
		$values = array();
		foreach($props as $prop)
		{
			$def = $this->model_def->columns_[$prop];
			$is_primary = isset($def['options']['primary']) ? $def['options']['primary'] : FALSE;
			$is_auto = isset($def['options']['autoincrement']) ? $def['options']['autoincrement'] : FALSE;
			
			// Don't specify fields that are auto increment WHERE there is no value set for this field.
			if(!(($this->$prop === NULL || $this->$prop === '') && $is_auto))
			{
				$parts[] = "`$prop` = ?";
				$values[] = $this->$prop;
			}
		}
		$query .= implode(', ', $parts);
		if($this->exists_in_db)
		{
			$query .= " WHERE `$pk` = ?";
			$values[] = $this->old_values[$pk];
			$result = DB::update($query, $values);
			if($result) 
				$this->set_old_values();
			//echo "result: $result";
		}
		else
		{// what about non increment pks ?
			$result = DB::insert($query, $values);
			if($result !== FALSE)
			{
				// Set primary key if NULL (ie the primary key field gets auto incremented)
				if($this->$pk === NULL)
					$this->$pk = $result;
				$this->exists_in_db = true;
				$this->set_old_values();
			}
			//echo "result: $result";
		}
		//echo $query;
		//var_dump($values);
		//var_dump($this->toArray());
		return $result;
	}
	
	function delete()
	{
		$ns = $this->model_def->namespace;
		$tbl = $this->model_def->name;
		$pk = $this->model_def->get_primary_key();
		
		$query = "DELETE FROM `$ns`.`$tbl` WHERE `$pk` = ?";
		//var_dump($query, array($this->old_values[$pk]));
		$result = DB::delete($query, array($this->old_values[$pk]));
		return $result;
	}
	
	function toArray()
	{
		$ret = array();
		$disallow = array('model_def', 'exists_in_db', 'old_values');
		foreach($this as $key => $val)
		{
			if(!in_array($key, $disallow))
				$ret[$key] = $val ;
		}
		return $ret;
	}

	public function set_old_values()
	{
		foreach($this->model_def->columns_ as $prop => $opts)
		{
			$this->old_values[$prop] = $this->$prop;
		}
	}
	
}

class ModelDefinition
{
	var $columns = array();			// Array of column definition arrays
	var $columns_ = array();		// Column definitions indexed by column name
	var $name;
	var $namespace;
	var $constraints = array();		// Constraints array
	
	function __construct($model)
	{
		list($this->namespace, $this->name) = preg_split('/::/', $model);
		//echo "get $db, $tbl";
		$this->columns = DBDefinition::mysql_defs_getColumns($this->namespace, $this->name);
		$this->constraints = DBDefinition::mysql_defs_getConstraints($this->namespace, $this->name);
		// Process the property definitions. Some changes are required.
		$processed = array();
		foreach($this->columns as $name => $opts)
		{
			$new_opts = array();
			$has_auto = isset($opts['options']['autoincrement']) ? $opts['options']['autoincrement'] : FALSE;
			foreach($opts['options'] as $key => $val)
			{
				//if($key == 'autoincrement') continue;
				if($key == 'notnull' && !$has_auto) $key = 'required';
				if($key == 'primary' && $val == TRUE && !$has_auto) $new_opts['required'] = TRUE;
				$new_opts[$key] = $val;
			}
			$this->columns[$name]['options'] = $new_opts;
		}
		
		// Create a list of property definitions indexed by the property name.
		foreach($this->columns as $col)
		{
			$name = $col['name'];
			foreach($this->constraints as $constraint => $opts)
			{
				if($opts[0]['field'] == $name)
				{
					$col['options']['unique'] = $opts[0]['unique'];
				}
			}
			if(!isset($col['options']['unique'])) $col['options']['unique'] = FALSE;
			$this->columns_[$name] = $col;
		}
	}
	
	function get_primary_key()	// what about multiple field primary keys?
	{
		$cols = array();
		foreach($this->columns as $col)
		{
			if($col['options']['primary'] === TRUE)
				//$cols[] = $col['name'];
				return $col['name'];
		}
		return $cols;
	}
	
	function get_properties_list()
	{
		$cols = array();
		foreach($this->columns as $col)
			$cols[] = $col['name'];
		return $cols;
	}
	
	function get_data_type($property)
	{
		foreach($this->columns as $col)
		{
			if($col['name'] == $property)
				return $col['type'];
		}
		return 'string';
	}
	
	function get_property_definitions()
	{
		return $this->columns_;
	}
}

class ModelManager
{
	static $model_definitions = array();	// Loaded model definitions
	
	static function get_model_definition($model)
	{
		if(isset(ModelManager::$model_definitions[$model]))
		{
			return ModelManager::$model_definitions[$model];
		}
		
		ModelManager::$model_definitions[$model] = new ModelDefinition($model);
		return ModelManager::$model_definitions[$model];
	}
	
	static function find($model, $id)
	{
		$m = new Model($model);
		$pk = $m->model_def->get_primary_key();
		$ns = $m->model_def->namespace;
		$name = $m->model_def->name;
		$q = "SELECT * FROM `$ns`.`$name` WHERE `$pk` = ?";
		//echo "query: $q";
		$result = DB::query_getone($q, array($id));
		if($result === FALSE) return FALSE;
		foreach($m->model_def->get_properties_list() as $prop)
		{
			$type = $m->model_def->get_data_type($prop);
			// if($type == "integer")
				// $val = (int)$result[$prop];
			// else
				$val = $result[$prop];
			$m->$prop = $val;
		}
		$m->exists_in_db = TRUE;
		$m->set_old_values();
		return $m;
	}
	
	static function find_where($model, $query, $params = array())
	{
		$models = array();
		$def = ModelManager::get_model_definition($model);
		$results = DB::query($query, $params);
		if($results)
		{
			foreach($results as $result)
			{
				$m = new Model($model);
				foreach($def->get_properties_list() as $prop)
				{
					$type = $def->get_data_type($prop);
					// if($type == "integer")
						// $val = (int)$result[$prop];
					// else
						$val = $result[$prop];
					$m->$prop = $val;
				}
				$m->exists_in_db = TRUE;
				$m->set_old_values();
				$models[] = $m;
			}
		}

		return $models;
	}
	
	static function get_all($model)
	{
		$def = ModelManager::get_model_definition($model);
		$ns = $def->namespace;
		$name = $def->name;
		$q = "SELECT * FROM `$ns`.`$name`";
		return ModelManager::find_where($model, $q);
		// $models = array();
		// $def = ModelManager::get_model_definition($model);
		// $ns = $def->namespace;
		// $name = $def->name;
		// $q = "SELECT * FROM `$ns`.`$name`";
		// $results = DB::query($q);
		// if($results)
		// {
			// foreach($results as $result)
			// {
				// $m = new Model($model);
				// foreach($def->get_properties_list() as $prop)
				// {
					// $type = $def->get_data_type($prop);
					// if($type == "integer")
						// $val = (int)$result[$prop];
					// else
						// $val = $result[$prop];
					// $m->$prop = $val;
				// }
				// $m->exists_in_db = TRUE;
				// $models[] = $m;
			// }
		// }

		// return $models;
	}
	
	
	
	// private function apply properties to instance
}

?>