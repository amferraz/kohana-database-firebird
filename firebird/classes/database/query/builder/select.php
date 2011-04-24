<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query builder for Firebird SELECT statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Firebird
 * @category   Query
 * @author     Anderson Marques Ferraz
 * @copyright  (c) 2011 Anderson Marques Ferraz
 * @license    http://kohanaphp.com/license
 */
class Database_Query_Builder_Select extends Kohana_Database_Query_Builder_Select {

	
	/**
	 * Compile the SQL query and return it.
	 *
	 * @param   object  Database instance
	 * @return  string
	 */
	public function compile(Database $db)
	{
		// Callback to quote columns
		$quote_column = array($db, 'quote_column');

		// Callback to quote tables
		$quote_table = array($db, 'quote_table');

		// Start a selection query
		$query = 'SELECT ';

		if ($this->_distinct === TRUE)
		{
			// Select only unique results
			$query .= 'DISTINCT ';
		}

		if (empty($this->_select))
		{
			// Select all columns
			$query .= '*';
		}
		else
		{
			// Select all columns
			$query .= implode(', ', array_unique(array_map($quote_column, $this->_select)));
		}

		if ( ! empty($this->_from))
		{
			// Set tables to select from
			$query .= ' FROM '.implode(', ', array_unique(array_map($quote_table, $this->_from)));
		}

		if ( ! empty($this->_join))
		{
			// Add tables to join
			$query .= ' '.$this->_compile_join($db, $this->_join);
		}

		if ( ! empty($this->_where))
		{
			// Add selection conditions
			$query .= ' WHERE '.$this->_compile_conditions($db, $this->_where);
		}

		if ( ! empty($this->_group_by))
		{
			// Add sorting
			$query .= ' GROUP BY '.implode(', ', array_map($quote_column, $this->_group_by));
		}

		if ( ! empty($this->_having))
		{
			// Add filtering conditions
			$query .= ' HAVING '.$this->_compile_conditions($db, $this->_having);
		}

		if ( ! empty($this->_order_by))
		{
			// Add sorting
			$query .= ' '.$this->_compile_order_by($db, $this->_order_by);
		}

                /* MySql's LIMIT <limit> TO <offset> in Firebird is
                 * ROWS <limit> TO <offset
                 *
                 */
		if ($this->_limit !== NULL)
		{
			// Add limiting
                        $query .= ' ROWS '.$this->_limit;
			
		}

		if ($this->_offset !== NULL)
		{
			// Add offsets
                        $query .= ' TO '.$this->_offset;
		}
		
		if ( ! empty($this->_union))
		{
			foreach ($this->_union as $u) {
				$query .= ' UNION ';
				if ($u['all'] === TRUE)
				{
					$query .= 'ALL ';
				}
				$query .= $u['select']->compile($db);
			}
		}

		$this->_sql = $query;

                
                //this is from from Kohana_Database_Query
		$sql = $this->_sql;

		if ( ! empty($this->_parameters))
		{
			// Quote all of the values
			$values = array_map(array($db, 'quote'), $this->_parameters);

			// Replace the values in the SQL
			$sql = strtr($sql, $values);
		}

		return $sql;
	}

	

} // End Database_Query_Builder_Select

