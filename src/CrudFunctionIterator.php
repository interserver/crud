<?php
/**
 * CRUD Class
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Crud
 */
namespace MyCrud;

/**
 *Crud class to handle iterating over the output of a local function and give an interface similar to the db class
 */
Class CrudFunctionIterator {
	public $function;
	public $result;
	public $size;
	public $idx = -1;
	public $ran = FALSE;
	public $Record;
	public $keys;

	/**
	 * CrudFunctionIterator constructor.
	 *
	 * @param $function
	 */
	public function __construct($function) {
		$this->function = $function;
	}

	/**
	 * runs the function and grabs the output from it applying it usually
	 *
	 * @return void
	 */
	public function run() {
		function_requirements($this->function);
		$this->result = call_user_func($this->function);
		$this->ran = TRUE;
		$this->size = count($this->result);
		$this->keys = array_keys($this->result);
	}

	/**
	 * grabs the next record in the current row if there is one.
	 *
	 * @param int $resultType the result type, can pass MYSQL_ASSOC, MYSQL_NUM, and other stuff
	 * @return bool whether it was able to get an array or not
	 */
	 public function next_record($resultType) {
		if ($this->ran == FALSE)
			$this->run();
		$this->idx++;
		if ($this->idx >= $this->size)
			return FALSE;
		$this->Record = $this->result[$this->keys[$this->idx]];
		return is_array($this->Record);
	}

}
