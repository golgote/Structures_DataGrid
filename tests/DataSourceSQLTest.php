<?php
/**
 * Unit Tests for Structures_DataGrid
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Olivier Guilyardi <olivier@samalyse.com>,
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * CVS file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @category Structures
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once 'DataSourceTest.php';

/**
 * SQL-based DataSource core tests
 */
class DataSourceSQLTest extends DataSourceTest
{
    protected $dbfile;

    public function setUp()
    {
        parent::setUp();
        if (!isset($this->dbfile)) {
            $this->dbfile = "/tmp/sdgtest.db";
            if (file_exists($this->dbfile)) {
                unlink($this->dbfile);
            }
            $db = sqlite_open($this->dbfile);
            sqlite_query($db, 'CREATE TABLE test (num int not null, "the str" char(255) not null);'); 
            foreach ($this->data as $row) {
                sqlite_query($db, "INSERT INTO test VALUES ({$row['num']}, '{$row['the str']}');");
            }
            sqlite_close($db);
        }

    }

    public function bindDefault()
    {
        $this->datasource->bind("SELECT * FROM test", array('dsn' => $this->getDSN()));
    }

    public function testDistinct()
    {
        $this->datasource->bind("SELECT DISTINCT num FROM test", array('dsn' => $this->getDSN()));
        $this->assertEquals(3, $this->datasource->count());
        $expected = array(
            array('num' => 1),
            array('num' => 2),
            array('num' => 3),
        );
        $this->assertEquals($expected, $this->datasource->fetch());
        $expected = array(
            array('num' => 2),
        );
        $this->assertEquals($expected, $this->datasource->fetch(1,1));
    }

    public function testGroupBy()
    {
        $this->datasource->bind("SELECT * FROM test GROUP BY num", array('dsn' => $this->getDSN()));
        $this->assertEquals(3, $this->datasource->count());
        $expected = array(
            array('num' => '3', 'the str' => ''),
            array('num' => '2', 'the str' => 'viel spaß'),
            array('num' => '1', 'the str' => 'test'),
        );
        $this->assertEquals($expected, $this->datasource->fetch());
        $expected = array(
            array('num' => '2', 'the str' => 'viel spaß'),
        );
        $this->assertEquals($expected, $this->datasource->fetch(1, 1));
    }

    public function testMixedSort()
    {
        $this->datasource->bind("SELECT * FROM test ORDER BY num DESC", array('dsn' => $this->getDSN()));
        $this->datasource->sort('the str', 'DESC');
        $expected = array(
            array('num' => '3', 'the str' => ''),
            array('num' => '2', 'the str' => 'viel spaß'),
            array('num' => '1', 'the str' => 'test'),
            array('num' => '1', 'the str' => 'présent'),
        );
        $this->assertEquals($expected, $this->datasource->fetch());
    }

    public function testCountQuery()
    {
        $this->datasource->bind("SELECT * FROM test WHERE 0 = 1", 
                array('dsn' => $this->getDSN(),
                    'count_query' => 'SELECT COUNT(*) FROM test'));
        $this->assertEquals(count($this->data), $this->datasource->count());
    }

    public function testDatabaseObject()
    {
        $options['dbc'] = $this->getDatabaseObject();
        $this->datasource->bind("SELECT * FROM test", $options);
        $this->assertEquals($this->data, $this->datasource->fetch());
    }
}
?>
