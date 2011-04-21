<?php


class DatabaseFirebirdTest extends PHPUnit_Framework_TestCase
{
    
    static protected $_test_instance;

    public function setUp()
    {
        self::$_test_instance = Database::instance('custom', array(
		'type'       => 'firebird',
		'connection' => array(
			'hostname'   => 'localhost',
			'database'   => 'kohana',
			'username'   => 'kohana',
			'password'   => 'kohana',
			'persistent' => FALSE,
		),
		'table_prefix' => '',
		'charset'      => 'utf8',
		'caching'      => FALSE,
		'profiling'    => TRUE,
	));

        self::$_test_instance->connect();
	
	
        self::$_test_instance->query( Database::UPDATE,
            "create table t1(
                 id integer not null primary key
            );"
        );

        self::$_test_instance->query( Database::UPDATE,
            "create generator gen_t1_id;" );

        self::$_test_instance->query( Database::UPDATE,
            "set generator gen_t1_id to 0;" );

        self::$_test_instance->query( Database::UPDATE,
            "CREATE TRIGGER T1_BI FOR t1
            ACTIVE BEFORE INSERT POSITION 0
            AS
            BEGIN
            NEW.ID = GEN_ID(GEN_T1_ID, 1);
            END;"
        );

    }


    public function tearDown()
    {
        self::$_test_instance->query(Database::DELETE, "drop trigger t1_bi");
        self::$_test_instance->query(Database::DELETE, "drop generator gen_t1_id");
        self::$_test_instance->query(Database::DELETE, "drop table t1");

        self::$_test_instance->disconnect();
    }

    public function testOne()
    {
        //nothing yet :)
    }
}

?>
