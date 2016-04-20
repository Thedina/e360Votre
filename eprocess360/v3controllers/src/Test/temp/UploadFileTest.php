<?php

use \eprocess360\v3core\DB;
use \eprocess360\v3modules\Fee\Model\FeeTypes;
use \eprocess360\v3modules\Fee\Model\FeeTemplates;
use \eprocess360\v3modules\Fee\Model\FeeTags;

/*
 * Output structure
 * {
 *    [tableName]=>
 *          {[columnName] => value}
 * }
 */

if (false) {
    $sql = "SELECT * FROM FeeTypes";
    $fees = DB::sql($sql);
    var_dump($fees);
    $sql = "DELETE FROM FeeTemplates WHERE idFeeTemplate > 5 ";
    $fees = DB::sql($sql);
    $sql = "DELETE FROM FeeTypes WHERE idFeeType > 12 ";
    $fees = DB::sql($sql);

}


$directory = 'eprocess360/v3controllers/src/Test/temp/';
$temp = csv_to_array($directory.'fee_templates.csv');
$array = CSVmapping($temp);


// Convert CSV to PHP Array
function csv_to_array($filename, $delimiter = ',')
{
    if (!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();

    // Open File as read only
    if (($handle = fopen($filename, 'r')) !== FALSE) {
        // Gets Array of CSV based on delimiter. 2nd parameter will need to be bigger for larger CSV.
        while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
            // Determines if the row is a header row. Skips if header is already defined
            if (!$header && in_array('Title', $row)) {
                $header = $row;
            } elseif (!is_null($header)) {
                // fills array with header row as the key
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }
    return $data;
}

function CSVmapping($data)
{
    $formulaMapping = array(
        'Unit' => array('table'=>'FeeTemplates','column'=>'formula'),
        'Formula' => array('table'=>'FeeTemplates','column'=>'formula'),
        'Matrix' => array('table'=>'FeeTemplates','column'=>'matrixFormula'),
        'Fixed' => array('table'=>'FeeTemplates','column'=>'fixedAmount')
    );
    $basicMapping = array(
        'Title' => array('table'=>'FeeTemplates','column'=>'title','isRequired'=>1),
        'Description' => array('table'=>'FeeTemplates','column'=>'description'),
        'Formula' => array('table'=>'FeeTemplates','column'=>$formulaMapping,'isRequired'=>1),
        'Minimum' => array('table'=>'FeeTemplates','column'=>'minimumValue','type'=>'float'),
        'Deposit' => array('table'=>'FeeTypes','column'=>'feeTypeFlags','bit'=>'isDeposit'),
        'Payable' => array('table'=>'FeeTypes','column'=>'feeTypeFlags','bit'=>'isPayable'),
        'Open' => array('table'=>'FeeTypes','column'=>'feeTypeFlags','bit'=>'isOpen'),
        'Tags' => array('table'=>'FeeTags','column'=>'feeTagValue','function'=>'explode')
    );
    $calcMapping = array(
        'Fixed' => array('table'=>'FeeTemplates','column'=>'isFixed'),
        'Unit' => array('table'=>'FeeTemplates','column'=>'isUnit'),
        'Formula' => array('table'=>'FeeTemplates','column'=>'isFormula'),
        'Matrix' => array('table'=>'FeeTemplates','column'=>'isMatrix'),
        'isRequired' => 1
    );

    $output = array();
    $checkTemplate = array();
    $check = array();
    $failed = array();
    foreach ($basicMapping as $key=>$v){
        if(array_key_exists('isRequired',$v)) {
            if ($v['isRequired']) $checkTemplate[$key] = 1;
        }
    }
    $checkTemplate['Calculation Type'] = 1;
    foreach ($data as $key => $fee) {
        $title = '';
        $check = $checkTemplate;
        foreach ($fee as $k => $element) {
            if(array_key_exists($k,$basicMapping)) {
                $mapKey = $basicMapping[$k];
                $mapTable = $mapKey['table'];
                $mapColumn = $mapKey['column'];
                $isDone = 0;
                if(is_array($mapColumn)){
                    if ($k == 'Formula') {
                        $cType = $fee['Calculation Type'];
                        $output[$key][$mapTable][$mapColumn[$cType]['column']] = $element;
                        $isDone = 1;
                    } else {
                        $output[$key][$mapTable][$mapColumn][] = $element;
                        $isDone = 1;
                    }
                } 
                if(array_key_exists('bit',$mapKey)) {
                    $output[$key][$mapTable][$mapColumn][$mapKey['bit']] = ((int)$element?1:0);
                    $isDone = 1;
                }
                if(array_key_exists('function',$mapKey)) {
                    if($mapKey['function'] == 'explode') {
                        $values = explode(",", $element);
                        foreach ($values as $v) $output[$key][$mapTable][$mapColumn][] = rtrim($v);
                        $isDone = 1;
                    }
                }
                if (!$isDone) {
                    $output[$key][$mapTable][$mapColumn] = $element;
                    $isDone = 1;
                }
                if(array_key_exists('isRequired',$mapKey)) {
                    if($mapKey['isRequired'] && $isDone) {
                        unset($check[$k]);
                    }
                }
            } elseif ($k == "Calculation Type") {
                $output[$key][$calcMapping[$element]['table']]['calculationMethod'][$calcMapping[$element]['column']] = 1;
                unset($check[$k]);
            }
        }
        if(count($check)) {
            $failed[$fee['Title']] = $check;
        }
    }

    foreach ($failed as $k => $v) {
        $error[] = '"'.$k.'" is missing the following: '.implode(', ',array_keys($v));
    }
    var_dump($error);

    foreach ($output as $o) {
        sql_insert($o);
    }
}

function sql_insert($array)
{
    foreach ($array as $table => $values) {
        if ($table == "FeeTemplates") {
            foreach (["isFormula", "isFixed", "isUnit", "isMatrix"] as $bit) {
                if (!array_key_exists($bit, $values["calculationMethod"])) $values["calculationMethod"][$bit] = 0;
            }
            $sql = "SELECT * FROM FeeTemplates WHERE title='" . $values["title"] . "'";
            if (!count(DB::sql($sql))) {
                $fee = FeeTemplates::create(0, 0, $values["title"], $values["description"],
                    $values["minimumValue"], (array_key_exists("fixedAmount", $values) ? $values["fixedAmount"] : 0),
                    (array_key_exists("matrixFormula", $values) ? $values["matrixFormula"] : 0), (array_key_exists("formula", $values) ? $values["formula"] : 0),
                    $values["calculationMethod"], ['isActive' => true]);
//                var_dump($fee);
            }

        }
        if ($table == "FeeTypes") {
            foreach (["isPayable", "isOpen", "isDeposit"] as $bit) {
                if (!array_key_exists($bit, $values["feeTypeFlags"])) $values["feeTypeFlags"][$bit] = 0;
            }
            $feeTypeFlags = (int)$values["feeTypeFlags"]["isPayable"].(int)$values["feeTypeFlags"]["isOpen"].(int)$values["feeTypeFlags"]["isDeposit"];
            $feeTypeFlags = bindec($feeTypeFlags);
            $sql = "SELECT idFeeType FROM FeeTypes WHERE feeTypeFlags_0 = " . $feeTypeFlags;
            $sqlout = DB::sql($sql);
            var_dump("$$",$feeTypeFlags,$sqlout);

            if (!count($sqlout)) {
                $feeType = FeeTypes::create(2062, 0, (int)$values["feeTypeFlags"]["isPayable"],(int)$values["feeTypeFlags"]["isOpen"],(int)$values["feeTypeFlags"]["isDeposit"]);
                var_dump("##",$feeType);
            } else {
                $idFeeType = current(reset($sqlout));
                $fee = FeeTemplates::editFeeTemplate($fee["idFeeTemplate"],$idFeeType,null, null, null, null,
                        null, null, null, null);
//                var_dump($fee,$sqlout);
            }
        }
        /*
        if ($table == "FeeTags") {
            FeeTags::make(0, $values, 0);
        }*/
    }
}

?>