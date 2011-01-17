<?php
/**
* Base Utility Model
*
* @version 1.0
* @author Jim Mayes <jim.mayes@gmail.com>
* @link http://style-vs-substance.com
* @copyright Copyright (c) 2010, Jim Mayes
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL v2.0
*/

class MY_Model extends Model {

	private		$_error;
	protected	$_table_name;

	function __construct(){
		parent::__construct();
	}

	function get_by($field, $value){
		$this->db->where($field, $value);
		$query = $this->db->get($this->_table_name);
		$result = $query->row();

		if( $result ){
			$this->_put($result);
		}
	}

	function get_by_id($id){
		$this->get_by('id', $id);
	}

	function exists(){
		if( $this->id ){
			return TRUE;
		}

		return FALSE;
	}

	function save(){
		$data = $this->_pull();

		if( !$data ){
			$this->set_error('The object is empty and cannot be saved');
			return FALSE;
		}

		if( $this->exists() ){
			if( method_exists($this, '_pre_update') ){
				$this->_pre_insert();
			}

			if( property_exists($this, 'updated') ){
				$data->updated = time();
			}

			$this->db->where('id', $data->id);
			$update = $this->db->update($this->_table_name, $data);

			if( $update ){
				return TRUE;
			}

		} else {
			if( method_exists($this, '_pre_insert') ){
				$this->_pre_insert();
			}

			if( property_exists($this, 'created') ){
				$data->created = time();
			}

			if( property_exists($this, 'updated') ){
				$data->updated = $data->created;
			}

			$insert = $this->db->insert($this->_table_name, $data);

			if( $insert ){
				$this->id = $this->db->insert_id();
				return TRUE;
			}
		}

		$this->set_error( $this->db->_error_message() );

		return FALSE;
	}

	function clear(){
		$properties = get_class_vars(get_class($this));

		foreach( $properties as $property => $value ){
			if( substr($property, 0, 1) != '_' ){
				$data[$property] = NULL;
			}
		}
	}

	function set_error($msg){
		$this->_error = $msg;
	}

	function _put($data=array()){
		$properties = get_class_vars( get_class($this) );

		foreach( $data as $property => $value ){
			if( substr($property, 0, 1) != '_' && array_key_exists($property, $properties) ){
				$this->$property = $value;
			}
		}
	}

	function _pull(){
		$properties = get_class_vars( get_class($this) );

		$data = array();

		foreach( $properties as $property => $value ){
			if( substr($property, 0, 1) != '_' ){
				$data[$property] = $this->$property;
			}
		}

		return (object) $data;
	}

	function __set($property, $value){
		if( $property == 'id' ){
			show_error(get_class($this) . '::$id is a protected property and cannot be set directly');
		}

		$filter = '_filter_set_' .$property;

		if( method_exists($this, $filter) ){
			$value = $this->$filter($value);
		}

		$this->$property = $value;
	}

	function __get($property){
		$filter = '_filter_get_' .$property;

		if( method_exists($this, $filter) ){
			return $this->$filter();
		}

		return $this->$property;
	}
}
?>