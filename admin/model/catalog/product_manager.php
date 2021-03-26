<?php
class ModelCatalogProductManager extends Model
{
    public function getNotHasProductOption($product_ids, $option_id)
    {
        $query = $this->db->query(
            'SELECT product_id FROM ' .
                DB_PREFIX .
                'product_option WHERE product_id IN(' .
                implode(',', array_map('intval', $product_ids)) .
                ") AND option_id= '" .
                (int) $option_id .
                "'"
        );

        foreach ($query->rows as $id) {
            $exist = array_search($id['product_id'], $product_ids);
            if ($exist >= 0) {
                unset($product_ids[$exist]);
            }
        }

        return $product_ids;
    }

    public function addProductOption($product_ids, $option_id, $value,$required) {
        $sql =
            'INSERT INTO ' .
            DB_PREFIX .
            'product_option (product_id, option_id, value, required) VALUES ';

        foreach ($product_ids as $id) {
            $sql =
                $sql .
                '(' .
                (int) $id .
                ',' .
                (int) $option_id .
                ',"' .
                (string) $value .
                '",' .
                (int) $required .
                ')';

            if (end($product_ids) == $id) {
                $sql = $sql . ';';
            } else {
                $sql = $sql . ',';
            }
        }

        $this->db->query($sql);
    }
}
