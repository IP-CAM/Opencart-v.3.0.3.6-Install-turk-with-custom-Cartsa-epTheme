<?php
class ModelCatalogProductManager extends Model
{
    public function getHasProducOption($product_ids, $option_id)
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

        return $query->rows;
    }
}
