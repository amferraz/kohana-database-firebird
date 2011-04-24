<?php
/**
 * Just some tests. Heavily based on the tests by https://github.com/samsoir,
 *  in his kohana module kohana-database-sqlsrv
 *
 * @package    Kohana/Firebird
 * @category   Query
 * @author     Anderson Marques Ferraz
 * @copyright  (c) 2011 Anderson Marques Ferraz
 * @license    http://kohanaphp.com/license
 */

class DatabaseFirebirdTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @var Database
     */
    static protected $_test_instance;

    public function setUp()
    {
        

        self::$_test_instance = Database::instance('default', array (
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
            "CREATE TABLE SOMEROWS
                (
                  ID Integer,
                  VARCHARFIELD Varchar(50),
                  CHARFIELD Char(10),
                  INTEGERFIELD Integer,
                  SMALLINTFIELD Smallint,
                  NUMERICFIELD Numeric(3,1),
                  DECIMALFIELD Decimal(3,1),
                  FLOATFIELD Float,
                  DOUBLEPRECISIONFIELD Double precision,
                  DATEFIELD Date,
                  TIMEFIELD Time,
                  TIMESTAMPFIELD Timestamp,
                  BLOBFIELD Blob sub_type 0,
                  PRIMARY KEY (ID)
                );"
        );


        self::$_test_instance->query( Database::UPDATE,
            "create generator GEN_SOMEROWS_ID;" );

        self::$_test_instance->query( Database::UPDATE,
            "set generator gen_somerows_id to 0;" );

        self::$_test_instance->query( Database::UPDATE,
            "CREATE TRIGGER SOMEROWS_BI FOR SOMEROWS
            ACTIVE BEFORE INSERT POSITION 0
            AS
            BEGIN
            NEW.ID = GEN_ID(GEN_SOMEROWS_ID, 1);
            END;"
        );

       //I think there's a problem with ibase driver...
       self::$_test_instance->reconnect();

    }


    public function tearDown()
    {
        self::$_test_instance->query(Database::DELETE, "drop trigger SOMEROWS_BI");
        self::$_test_instance->query(Database::DELETE, "drop generator GEN_SOMEROWS_ID");
        self::$_test_instance->query(Database::DELETE, "drop table somerows");

        self::$_test_instance->disconnect();
    }

    
    public function testInsert()
    {
       
        self::$_test_instance->reconnect();
        $result = self::$_test_instance->query(Database::INSERT, "INSERT INTO SOMEROWS (varcharfield, integerfield) VALUES('the answer is', 42)");

        $this->assertGreaterThanOrEqual($result[0], 1);
    }

    /**
     * This test depends on testInsert
     */
    public function testSelect()
    {
        $this->testInsert();
        $result = self::$_test_instance->query(Database::SELECT, "SELECT varcharfield, integerfield FROM somerows WHERE varcharfield = 'the answer is'")->as_array();

        $this->assertEquals($result[0]['integerfield'], 42);
    }


    /**
     * This test depends on testSelect
     */
    public function testUpdate()
    {
        $this->testSelect();
        $affected = self::$_test_instance->query(Database::UPDATE , "UPDATE somerows SET varcharfield = 'the answer for life, universe and everything' WHERE integerfield = 42");

        $this->assertEquals($affected, 1);
    }

    /**
     * This test depends on testUpdate
     */
    public function testDelete()
    {
        $this->testUpdate();
        $affected = self::$_test_instance->query(Database::DELETE, "DELETE FROM somerows WHERE varcharfield = 'the answer for life, universe and everything'");

        $this->assertEquals($affected, 1);
    }

    /**
     * This test depends on testDelete
     */
    public function testQueryBuilder()
    {
        $result = DB::insert('somerows')->columns(array('varcharfield', 'integerfield'))->values(array('the answer for life, universe and everything', '42'))->execute(self::$_test_instance);
        $this->assertGreaterThanOrEqual($result[0], 1);

        $result = DB::select('varcharfield')
                ->from('somerows')
                ->where('integerfield', '=', '42')->execute(self::$_test_instance)->as_array();
        $this->assertEquals($result[0]['varcharfield'], 'the answer for life, universe and everything');

        $affected = DB::update('somerows')->value('varcharfield','the final answer')->execute();
        $this->assertEquals($affected, 1);

        $affected = DB::delete('somerows')->where('varcharfield', '=', 'the final answer')->execute();
        $this->assertEquals($affected, 1);
    }
}

?>
