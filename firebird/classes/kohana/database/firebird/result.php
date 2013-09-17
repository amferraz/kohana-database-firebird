<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Firebird database result. See [Results](/database/results) for usage and examples.
 *
 * @package    Kohana/Firebird
 * @category   Query/Result
 * @author     Anderson Marques Ferraz
 * @copyright  (c) 2011 Anderson Marques Ferraz
 * @license    http://kohanaphp.com/license
 */
class Kohana_Database_Firebird_Result extends Database_Result {

	protected $_internal_row = 0;
        protected $_rows = array();

	public function __construct($result, $sql, $as_object = FALSE, array $params = NULL)
	{
		parent::__construct($result, $sql, $as_object, $params);

                $func_name = $as_object ? 'ibase_fetch_object' : 'ibase_fetch_assoc';

                while (($row = $func_name($result, IBASE_TEXT))!== FALSE ){

                    if ($func_name=='ibase_fetch_assoc'){
                        //convert column names to lowercase... damn, Firebird!
                        foreach($row as $key=>$value){
                            $aux[strtolower ($key)] = $value;
                        }
                        $this->_rows[] = $aux;
                    }
                    else{
                        throw new Exception('Not implemented yet.');
                        $this->_rows[] = $row;
                    }
                }

		// Find the number of rows in the result
		$this->_total_rows = count($this->_rows);
	}


       

        
	public function __destruct()
	{
		if (is_resource($this->_result))
		{
			ibase_free_result($this->_result);
		}
	}

	public function seek($offset)
	{
		if ($this->offsetExists($offset))
		{
			// Set the current row to the offset
			$this->_current_row = $this->_internal_row = $offset;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function current()
	{
		if ($this->_current_row !== $this->_internal_row AND ! $this->seek($this->_current_row))
			return NULL;


                $this->_internal_row++;

		if ($this->_as_object === TRUE)
		{
			// Return an stdClass
			return $this->_rows[$this->_internal_row-1];
		}
		elseif (is_string($this->_as_object))
		{
                        throw new Exception('Not supported yet');
                        
			// Return an object of given class name
//			return mysql_fetch_object($this->_result, $this->_as_object, $this->_object_params);
		}
		else
		{
			// Return an array of the row
			return $this->_rows[$this->_internal_row-1];
		}

                // Increment internal row for optimization assuming rows are fetched in order
		
	}

} // End Database_Firebird_Result_Select
