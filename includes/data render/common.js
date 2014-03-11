// Merges elements of object one with elements of object two.
// If both objects have the same key, and the key's values are objects, then those objects 
// will be merged. If the key's values are not both objects then the value of obj2 will be used.
// Returns a new object.
function merge_objects(obj1, obj2)
{
	// get keys of obj1, get keys of obj2.
	// merge keys (no duplicates)
	// for each key
	// 	if both objects have the same key, and the values are object then merge them and assign the result to obj.
	//  if only one or the other has the key then simply assign to obj.
	var keys1 = get_array_keys(obj1);
	var keys2 = get_array_keys(obj2);
	var keys = merge_arrays(keys1, keys2, false);
	var obj = {};
	//console.log(keys);
	for(var i = 0; i < keys.length; i++)
	{
		var key = keys[i];
		if(obj1[key] !== undefined && obj2[key] !== undefined)
		{
			if($.isPlainObject(obj1[key]) && $.isPlainObject(obj2[key]))
				obj[key] = merge_objects(obj1[key], obj2[key]);
			else
				obj[key] = obj2[key];
		}
		else
		{
			obj[key] = (obj1[key] !== undefined ? obj1[key] : obj2[key]);
		}
	}
	//console.log(obj);
	
	return obj;
}

function get_array_keys(array)
{
	var keys = [];
	for(var k in array)
		keys.push(k);
	return keys;
}

// Merges contents of two arrays into one. If allow_duplicates is set to true, then duplicate items
// in both arrays will be allowed. If set to false then only unique items will be returned.
function merge_arrays(array1, array2, allow_duplicates)
{
	var array = [];
	var used = {};
	for(var i in array1)
	{
		if(used[array1[i]] === undefined || allow_duplicates)
		{
			used[array1[i]] = true;
			array.push(array1[i]);
		}
	}
	for(var i in array2)
	{
		if(used[array2[i]] === undefined || allow_duplicates)
		{
			used[array2[i]] = true;
			array.push(array2[i]);
		}
	}
	return array;
}