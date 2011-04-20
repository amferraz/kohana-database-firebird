<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Insert extends Kohana_Database_Query_Builder_Insert {

        /**
	 * Compile the SQL query and return it
	 *
	 * @param   object  Database instance
	 * @return  string
	 */
	public function compile(Database $db)
	{
		// Start an insertion query
		$sql = $query = 'INSERT INTO '.$db->quote_table($this->_table);

		// Add the column names
		$query .= ' ('.implode(', ', array_map(array($db, 'quote_column'), $this->_columns)).') ';

		if (is_array($this->_values))
		{
                        if (count($this->_values)>1)
                        {
                            throw new Exception('Multiple inserts are not currently supported.');
                        }
                        // Callback for quoting values
                        $quote = array($db, 'quote');

                        $groups = array();
                        foreach ($this->_values as $group)
                        {
                                foreach ($group as $offset => $value)
                                {
                                        if ((is_string($value) AND array_key_exists($value, $this->_parameters)) === FALSE)
                                        {
                                                // Quote the value, it is not a parameter
                                                $group[$offset] = $db->quote($value);
                                        }
                                }

                                $groups[] = '('.implode(', ', $group).')';
                        }

                        // Add the values
                        $query .= 'VALUES '.implode(', ', $groups);
		}
		else
		{
			// Add the sub-query
			$query .= (string) $this->_values;
		}

		$this->_sql = $query;

		$sql = parent::compile($db);

                //firebird idiossincrasies
                return $sql.' RETURNING id';
	}

}
