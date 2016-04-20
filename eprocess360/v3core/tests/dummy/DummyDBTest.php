<?php
    use eprocess360\v3core\tests\base\DBTestBase;

    /**
     * Class DummyDBTest
     * Some very basic DBUnit functionality checks
     */
    class DummyDBTest extends DBTestBase {
        public function getDataSet() {
            return $this->createArrayDataSet(array(
                'projects' => array(
                    array (
                        'idproject' => 2038,
                        'projnumber' => 150252,
                        'projname' => '3157 E DEL MAR DR, SALT LAKE CITY UT 84109',
                        'projstatus' => 15,
                        'projnotes' => 'Residential: Remodel',
                        'projectmeta' => '',
                        'idworkflow' => 16,
                        'lastupdate' => NULL,
                        'startdate' => '2015-02-05 00:00:00',
                        'projdataa' => NULL,
                        'projdatab' => NULL,
                        'kdcol_5' => 'Re-roof',
                        'kdcol_32' => 'Jeremiah Post',
                        'kdcol_2' => '3157 E DEL MAR DR, SALT LAKE CITY UT 84109',
                        'kdcol_33' => '(801) 560-3559',
                        'kdcol_6' => '**  3830 - 3825 S ** ',
                        'kdcol_370' => 'Residential: Roof Conversion',
                        'kdcol_151' => NULL,
                        'kdcol_383' => NULL,
                        'kdcol_7' => '20000',
                        'kdcol_384' => '16354020220000',
                        'kdcol_99' => '1664',
                        'kdcol_385' => 'Jeremiah Post',
                        'kdcol_386' => NULL,
                        'kdcol_355' => 'Jeremiah Post'
                    )
                ),
                'keydict' => array(
                    array(
                        'idkeydict' => '5',
                        'key' => 'PROJ-NAME',
                        'validator' => 'none',
                        'datatype' => 'varchar',
                        'maxlen' => '128',
                        'minlen' => '1',
                        'maxdecimals' => '0',
                        'defaultval' => '',
                        'autocompletescope' => 'user',
                        'target_table' => 'projects',
                        'modal' => '',
                        'usedbyfees' => '0',
                        'arrayelements' => ''
                    )
                ),
                'project_data' => array(
                    array (
                        'idproject_data' => '139724',
                        'key' => 'PROJ-NAME',
                        'createdate' => '2015-02-05',
                        'changedate' => '2015-02-05',
                        'createiduser' => '1107',
                        'changeiduser' => '637',
                        'idproject' => '2038',
                        'varchardata' => 'Re-roof',
                        'intdata' => NULL,
                        'decimaldata' => NULL,
                        'idtextdata' => '0',
                        'datedata' => NULL,
                        'datetimedata' => NULL
                    )
                )
            ));
        }

        public function testCheckConnections() {
            $modsql_rowcount = \sql("SELECT COUNT(*) AS rows FROM projects");
            $modsql_rowcount = (int)$modsql_rowcount[0]['rows'];
            $dbu_rowcount = (int)$this->getConnection()->getRowCount('projects');
            $this->assertEquals($dbu_rowcount, $modsql_rowcount);
        }

        public function testRetrieveProject() {
            $proj = \_i(\sql("SELECT * FROM projects WHERE idproject = 2038"), 0);
            $this->assertEquals($proj['projnumber'], '150252');
        }

        public function testInsertProject() {
            $result = \sql("INSERT INTO projects (idproject, projnumber, projname, projstatus, projnotes, projectmeta, idworkflow, lastupdate, startdate, projdataa, projdatab, kdcol_5, kdcol_32, kdcol_2, kdcol_33, kdcol_6, kdcol_370, kdcol_151, kdcol_383, kdcol_7, kdcol_384, kdcol_99, kdcol_385, kdcol_386, kdcol_355) VALUES (2147, '150423', '1141 E 3900 S, SALT LAKE CITY UT 84124', 15, 'Commercial: Other', '', 16, null, '2015-02-13 00:00:00', null, null, '', 'Craig  Wilson', '1141 E 3900 S, SALT LAKE CITY UT 84124', '(801) 972-6464', '** Valley Mental Health ** New Monument Sign at east driveway entrance on 3900 South ** Old Case #8271 **', 'Commercial: Other', null, null, 0, '16324020220000', 0, 'Craig  Wilson', 'V-B', '2015-03-19')");
            $this->assertEquals((int)$this->getConnection()->getRowCount('projects'), 2);
        }

    }
?>