<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomerStoreUpsert
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
                $exec_value = "UPDATE `".$table_name."` SET";

                // $temp_exec_value_field = "";
                // $temp_exec_value_field = $temp_exec_value_field."(";

                // $temp_exec_value_value = "";
                // $temp_exec_value_value = $temp_exec_value_value."(";

                $exec_value_store = "";
                $exec_value_customer = "";

                // echo $value[$x]->field_name;
                for ($x = 0; $x < count($value); $x++) {

                    if ($value[$x]->field_name === "id_store" && $exec_value_store === "") {
                        $exec_value_store = $exec_value_store.$value[$x]->field_name." = ".$value[$x]->value;
                    }

                    if ($value[$x]->field_name === "id_customer" && $exec_value_customer === "") {
                        $exec_value_customer = $exec_value_customer.$value[$x]->field_name." = ".$value[$x]->value;
                    }
                    
                    // echo $value[$x]->value;
                    // $exec_value_field = "`".$value[$x]->field_name."`";
                    // $temp_exec_value_field = $temp_exec_value_field.$exec_value_field;
                    // if ($x >= 0 && $x <= count($value) - 2) {
                    //     $temp_exec_value_field = $temp_exec_value_field.", ";
                    // }

                    // $exec_value_value = "'".$value[$x]->value."'";
                    // $temp_exec_value_value = $temp_exec_value_value.$exec_value_value;
                    // if ($x >= 0 && $x <= count($value) - 2) {
                    //     $temp_exec_value_value = $temp_exec_value_value.", ";
                    // }
                }
                // $temp_exec_value_field = $temp_exec_value_field.")";
                // $temp_exec_value_value = $temp_exec_value_value.")";
                // echo $exec_value." ";
                $exec_value = $exec_value." ".$exec_value_store." where ".$exec_value_customer;
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