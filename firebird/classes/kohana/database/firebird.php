<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Firebird database connection.
 *
 * @package    Kohana/Firebird
 * @category   Drivers
 * @author     Anderson Marques Ferraz
 * @copyright  (c) 2011 Anderson Marques Ferraz
 * @license    http://kohanaphp.com/license
 */
class Kohana_Database_Firebird extends Database {


    /**
     * A very big query only for database inflection. Damn, Firebird.
     * @var string
     */
    protected $_sql_list_table_columns =
        'SELECT
            lower(trim(relation_fields.RDB$FIELD_NAME)) as "FIELD",
            trim(iif(fields.RDB$FIELD_TYPE <>8,
                  CASE fields.RDB$FIELD_TYPE
                  WHEN 261 THEN \'blob\'
                  WHEN 14 THEN \'char\'
                  WHEN 40 THEN \'cstring\'
                  WHEN 11 THEN \'d_float\'
                  WHEN 27 THEN \'double\'
                  WHEN 10 THEN \'float\'
                  WHEN 16 THEN \'int64\'
                  WHEN 9 THEN \'quad\'
                  WHEN 7 THEN \'smallint\'
                  WHEN 12 THEN \'date\'
                  WHEN 13 THEN \'time\'
                  WHEN 35 THEN \'timestamp\'
                  WHEN 37 THEN \'varchar\'
                  ELSE \'unknow\'
                END,
                iif(fields.RDB$FIELD_SUB_TYPE = 1, \'numeric\',
                iif(fields.RDB$FIELD_SUB_TYPE = 2, \'decimal\', \'integer\')))) AS "TYPE",

                fields.RDB$CHARACTER_LENGTH "CHARACTER_LENGTH",
                fields.RDB$FIELD_PRECISION "PRECISION",
                fields.RDB$FIELD_SCALE*-1 "SCALE",

                trim(charsets.RDB$DEFAULT_COLLATE_NAME) as "COLLATION",

                cast(iif(iif(relation_fields.RDB$NULL_FLAG is not null, relation_fields.RDB$NULL_FLAG, fields.RDB$NULL_FLAG)  is not null,\'no\', \'yes\') as varchar(3)) as "NULL",

                cast(iif(pk_fields.RDB$FIELD_NAME = relation_fields.RDB$FIELD_NAME, \'PRI\', \'\') as varchar(3)) as "KEY",

                substring(iif(relation_fields.RDB$DEFAULT_SOURCE is not null, relation_fields.RDB$DEFAULT_SOURCE, fields.RDB$DEFAULT_SOURCE) from 9 ) as "DEFAULT",

                cast(iif(priv_select.RDB$PRIVILEGE is not null, \'yes\', \'no\') as varchar(3)) as "SELECT",
                cast(iif(priv_insert.RDB$PRIVILEGE is not null, \'yes\', \'no\') as varchar(3)) as "INSERT",
                cast(iif(priv_update.RDB$PRIVILEGE is not null, \'yes\', \'no\') as varchar(3)) as "UPDATE",
                cast(iif(priv_delete.RDB$PRIVILEGE is not null, \'yes\', \'no\') as varchar(3)) as "DELETE",
                cast(iif(priv_refer.RDB$PRIVILEGE is not null, \'yes\', \'no\') as varchar(3)) as "REFERENCES"

        FROM
            RDB$RELATION_FIELDS relation_fields

            JOIN RDB$FIELDS fields on (relation_fields.rdb$field_source = fields.rdb$field_name)

            LEFT JOIN RDB$CHARACTER_SETS charsets on (fields.RDB$CHARACTER_SET_ID = charsets.RDB$CHARACTER_SET_ID)

            left join RDB$RELATION_CONSTRAINTS primary_key on (primary_key.RDB$RELATION_NAME = relation_fields.RDB$RELATION_NAME and primary_key.RDB$CONSTRAINT_TYPE = \'PRIMARY KEY\')
            left join RDB$INDEX_SEGMENTS pk_fields on (pk_fields.RDB$INDEX_NAME = primary_key.RDB$INDEX_NAME)

            left join RDB$USER_PRIVILEGES priv_select on (priv_select.RDB$RELATION_NAME = relation_fields.RDB$RELATION_NAME and priv_select.RDB$PRIVILEGE=\'S\' and priv_select.RDB$USER = current_user)
            left join RDB$USER_PRIVILEGES priv_insert on (priv_insert.RDB$RELATION_NAME = relation_fields.RDB$RELATION_NAME and priv_insert.RDB$PRIVILEGE=\'I\' and priv_insert.RDB$USER = current_user)
            left join RDB$USER_PRIVILEGES priv_update on (priv_update.RDB$RELATION_NAME = relation_fields.RDB$RELATION_NAME and priv_update.RDB$PRIVILEGE=\'U\' and priv_update.RDB$USER = current_user)
            left join RDB$USER_PRIVILEGES priv_delete on (priv_delete.RDB$RELATION_NAME = relation_fields.RDB$RELATION_NAME and priv_delete.RDB$PRIVILEGE=\'D\' and priv_delete.RDB$USER = current_user)
            left join RDB$USER_PRIVILEGES priv_refer  on (priv_refer.RDB$RELATION_NAME  = relation_fields.RDB$RELATION_NAME and priv_refer.RDB$PRIVILEGE= \'R\' and priv_refer.RDB$USER = current_user)

        WHERE
            relation_fields.RDB$SYSTEM_FLAG = 0';


	// Database in use by each connection
	protected static $_current_databases = array();

	// Identifier for this connection within the PHP driver
	protected $_connection_id;


        // Identifier for this transaction within the Firebird connection
	protected $_transaction;

	// Firebird uses a double-quote for identifiers
	protected $_identifier = '';

	public function connect()
	{
		if ($this->_connection)
			return;

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'database'   => '',
			'hostname'   => '',
			'username'   => '',
			'password'   => '',
			'persistent' => FALSE,
		));

		// Prevent this information from showing up in traces
		//  can't do that: Firebird needs to reconect.
//		unset($this->_config['connection']['username'], $this->_config['connection']['password']);

		try
		{
			if ($persistent)
			{
				// Create a persistent connection
				$this->_connection = ibase_pconnect($hostname.":".$database, $username, $password, $this->_config['charset']);
			}
			else
			{
				// Create a connection and force it to be a new link
				$this->_connection = ibase_connect($hostname.":".$database, $username, $password, $this->_config['charset']);
			}
		}
		catch (ErrorException $e)
		{
			// No connection exists
			$this->_connection = NULL;

			throw new Database_Exception(ibase_errcode(), '[:code] :error', array(
					':code' => ibase_errcode(),
					':error' => ibase_errmsg(),
				));
		}

		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname.":".$database.'_'.$username.'_'.$password);


	}

	/**
	 * Select the database
	 *
	 * @param   string  Database
	 * @return  void
	 */
	protected function _select_db($database)
	{
                //gracefully do nothing

	}

        /**
         * Reconnects with database.
         */
        public function reconnect()
        {
            $this->disconnect();
            $this->connect();
        }

	public function disconnect()
	{
		try
		{
			// Database is assumed disconnected
			$status = TRUE;

			if (is_resource($this->_connection))
			{
				if ($status = ibase_close($this->_connection))
				{
					// Clear the connection
					$this->_connection = NULL;
				}
			}
		}
		catch (Exception $e)
		{
			// Database is probably not disconnected
			$status = ! is_resource($this->_connection);
		}

		return $status;
	}

	public function set_charset($charset)
	{
                //gracefully do nothing. Can't select firebird connection
                // while connected connection

	}

	public function query($type, $sql, $as_object = FALSE, array $params = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! empty($this->_config['profiling']))
		{
			// Benchmark this query for the current instance
			$benchmark = Profiler::start("Database ({$this->_instance})", $sql);
		}


		// Execute the query
		if (($result = ibase_query($this->_connection,$sql)) === FALSE)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				Profiler::delete($benchmark);
			}

			throw new Database_Exception(ibase_errcode(), '[:code] :error ( :query )', array(
				':code' => ibase_errcode(),
				':error' => ibase_errmsg(),
				':query' => $sql,
			));
		}



               
		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			// Return an iterator of results
			return new Database_Firebird_Result($result, $sql, $as_object, $params);
		}
		elseif ($type === Database::INSERT)
		{

                        // simulating mysqls' mysql_insert_id()
                        $data = array();

                        if ($result != TRUE){
                            $data = ibase_fetch_assoc($result);
                        }

                        $data = Arr::get($data, 'ID', null);
                        
			// Return a list of insert id and rows created
			return array(
				$data['ID'],
                                ibase_affected_rows($this->_connection),
			);
		}
		else
		{
			// Return the number of rows affected
			return ibase_affected_rows($this->_connection);
		}
	}

        
	public function datatype($type)
	{
            
		static $types = array
		(
                    'blob'       => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '-1'),
                    'char'       => array('type' => 'string'),
                    'date'       => array('type' => 'string'),
                    'decimal'    => array('type' => 'float', 'exact' => TRUE),
                    'double'     => array('type' => 'float', 'exact' => TRUE),
                    'float'      => array('type' => 'float'),
                    'int64'      => array('type' => 'int', 'min' => '-9223372036854775808', 'max' =>'9223372036854775807'),
                    'integer'    => array('type' => 'int', 'min' => '-2147483648', 'max' =>'2147483647'),
                    'numeric'    => array('type' => 'float'),
                    'smallint'   => array('type' => 'int', 'min' => '-32,768', 'max' =>'32,767'),
                    'time'       => array('type' => 'string'),
                    'timestamp'  => array('type' => 'string'),
                    'varchar'    => array('type' => 'string', 'character_maximum_length' => '32765'),
		);

                return $types[$type];

	}

	/**
	 * Start a SQL transaction
	 *
	 * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function begin($mode = NULL)
	{
            
		// Make sure the database is connected
		$this->_connection or $this->connect();


                $this->_transaction = ibase_trans(IBASE_DEFAULT, $this->_connection );

                if (!$this->_transaction){
                    throw new Database_Exception(ibase_errcode(), '[:code] :error', array(
					':code' => ibase_errcode(),
					':error' => ibase_errmsg(),
				));
                }

                return (bool) $this->_transaction;

	}

	/**
	 * Commit a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function commit($mode = null)
	{
		// Make sure the database is connected and a transaction has started
		$this->_connection or $this->connect();
                $this->_transaction or $this->begin();
                
		return ibase_commit($this->_transaction);
	}

	/**
	 * Rollback a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function rollback()
	{
		// Make sure the database is connected and a transaction has started
		$this->_connection or $this->connect();
                $this->_transaction or $this->begin();
                
		return  ibase_rollback($this->_transaction);
	}

	public function list_tables($like = NULL)
	{

            $sql_list_tables = 'select lower(trim(relations.RDB$RELATION_NAME)) as table_names
                                from RDB$RELATIONS relations
                                where relations.RDB$SYSTEM_FLAG = 0 ';
            
            throw new Exception('Not implemented yet');
		if (is_string($like))
		{
			// Search for table names
			$result = $this->query(Database::SELECT,
                                $sql_list_tables.' and lower(trim(relations.RDB$RELATION_NAME)) like lower(trim('.$this->quote($like).'))', FALSE);
		}
		else
		{
			// Find all table names
			$result = $this->query(Database::SELECT, $sql_list_tables, FALSE);
		}

		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
	}


        
	public function list_columns($table, $like = NULL, $add_prefix = TRUE)
	{

		if (is_string($like))
		{
                    throw new Exception('Not implemented yet.');
			// Search for column names
			$result = $this->query(Database::SELECT, 'SHOW FULL COLUMNS FROM '.$table.' LIKE '.$this->quote($like), FALSE);
		}
		else
		{
			// Find all column names
                        $formatted_sql = $this->_sql_list_table_columns.' and trim(relation_fields.RDB$RELATION_NAME)=upper(\''.$table.'\');';
			$result = $this->query(Database::SELECT, $formatted_sql, FALSE);
		}

		$count = 0;
		$columns = array();
		foreach ($result as $row)
		{
			$column = $this->datatype($row['type']);

			$column['column_name']      = $row['field'];
			$column['column_default']   = $row['default'];
			$column['data_type']        = $row['type'];
			$column['is_nullable']      = ($row['null'] == 'yes');
			$column['ordinal_position'] = ++$count;

			switch ($column['type'])
			{
				case 'float':
                                        if (Arr::get($row, 'precision', FALSE))
                                        {
                                            $column['numeric_precision'] = $row['precision'];
                                            $column['numeric_scale'] = $row['scale'];
                                        }
				break;
				case 'string':
					switch ($column['data_type'])
					{
						case 'CHAR':
						case 'VARCHAR':
							$column['character_maximum_length'] = $row['character_length'];
						break;
					}
				break;
			}

                        
			$column['key']          = $row['key'];

			$column['can_select']    = ($row['select'] == 'yes');
			$column['can_insert']    = ($row['insert'] == 'yes');
			$column['can_update']    = ($row['update'] == 'yes');
			$column['can_delete']    = ($row['delete'] == 'yes');
			$column['can_reference'] = ($row['references'] == 'yes');

			$columns[$row['field']] = $column;
		}

		return $columns;
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();


                $value = str_replace(array('\\','\''),array('\\\\','\\\''),$value);
//		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

} // End Database_Firebird
