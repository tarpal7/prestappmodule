<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderInvoicePaymentUpsert
{
    public static function init ($data) {
        for ($i = 0; $i < count($data); $i++) {
            
            // Db::getInstance()->insert(
            //     'condition_advice',
            //     array(
            //     'id_condition' => (int) $cond_ids[$cond], 'id_advice' => (int) $id_advice, 'display' => 1)
            // );
            $table_name = $data[$i]->table_name;

            if (isset($data[$i]->value)) {
                $value = $data[$i]->value;
                $exec_value = "INSERT IGNORE INTO `".$table_name."` ";

                $temp_exec_value_field = "";
                $temp_exec_value_field = $temp_exec_value_field."(";

                $temp_exec_value_value = "";
                $temp_exec_value_value = $temp_exec_value_value."(";

                // echo $value[$x]->field_name;
                for ($x = 0; $x < count($value); $x++) {
                    
                    // echo $value[$x]->value;
                    $exec_value_field = "`".$value[$x]->field_name."`";
                    $temp_exec_value_field = $temp_exec_value_field.$exec_value_field;
                    if ($x >= 0 && $x <= count($value) - 2) {
                        $temp_exec_value_field = $temp_exec_value_field.", ";
                    }

                    $exec_value_value = "'".$value[$x]->value."'";
                    $temp_exec_value_value = $temp_exec_value_value.$exec_value_value;
                    if ($x >= 0 && $x <= count($value) - 2) {
                        $temp_exec_value_value = $temp_exec_value_value.", ";
                    }
                }
                $temp_exec_value_field = $temp_exec_value_field.")";
                $temp_exec_value_value = $temp_exec_value_value.")";
                // echo $exec_value." ";
                $exec_value = $exec_value." ".$temp_exec_value_field." VALUES ".$temp_exec_value_value;

                $return = Db::getInstance()->execute($exec_value);
                if ($return == 1) {
                    echo "Executed successfuly ".$exec_value."\n";
                } else {
                    echo "Error ".$exec_value."\n";
                }
            }
        }
    }
}