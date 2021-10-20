<?php
class ModelExtensionbganygrp extends Model {
	public function addbganygrp($data) {
		$grpproduct = (isset($data['grpproduct']) && $data['grpproduct']) ? implode(",",$data['grpproduct']) : '';
		$grpcategory = (isset($data['grpcategory']) && $data['grpcategory']) ? implode(",",$data['grpcategory']) : '';
		$grpmanufacturer = (isset($data['grpmanufacturer']) && $data['grpmanufacturer']) ? implode(",",$data['grpmanufacturer']) : '';	
		$customer_group = (isset($data['customer_group']) && $data['customer_group']) ? implode(",",$data['customer_group']) : '';
		$store = (isset($data['store']) && $data['store'] != '') ? implode(",",$data['store']) : ''; 
		//print_r($store);exit;
 
		$this->db->query("INSERT INTO " . DB_PREFIX . "bganygrp SET name = '" . $this->db->escape($data['name']) . "', discount_type = '" . (int)$data['discount_type'] . "', discount_value = '" . (float)$data['discount_value'] . "', buyqty = '" . (int)$data['buyqty'] . "', getqty = '" . (int)$data['getqty'] . "', `ribbontext` = '" . $this->db->escape(json_encode($data['ribbontext'], true)) . "',  `ordertotaltext` = '" . $this->db->escape(json_encode($data['ordertotaltext'], true)) . "', display_offer_at = '" . (int)$data['display_offer_at'] . "', `offer_heading_text` = '" . $this->db->escape(json_encode($data['offer_heading_text'], true)) . "', `offer_content` = '" . $this->db->escape(json_encode($data['offer_content'], true)) . "', grpproduct = '" . $this->db->escape($grpproduct) . "', grpcategory = '" . $this->db->escape($grpcategory) . "', grpmanufacturer = '" . $this->db->escape($grpmanufacturer) . "', customer_group = '" . $this->db->escape($customer_group) . "', store = '" . $this->db->escape($store) . "', status = '" . (int)$data['status'] . "' ");

		$bganygrp_id = $this->db->getLastId();
    
 		$this->cache->delete('bganygrp');

		return $bganygrp_id;
	}

	public function editbganygrp($bganygrp_id, $data) {
		$grpproduct = (isset($data['grpproduct']) && $data['grpproduct']) ? implode(",",$data['grpproduct']) : '';
		$grpcategory = (isset($data['grpcategory']) && $data['grpcategory']) ? implode(",",$data['grpcategory']) : '';
		$grpmanufacturer = (isset($data['grpmanufacturer']) && $data['grpmanufacturer']) ? implode(",",$data['grpmanufacturer']) : '';	
		$customer_group = (isset($data['customer_group']) && $data['customer_group']) ? implode(",",$data['customer_group']) : '';
		$store = (isset($data['store']) && $data['store'] != '') ? implode(",",$data['store']) : ''; 
		
		$this->db->query("UPDATE " . DB_PREFIX . "bganygrp SET name = '" . $this->db->escape($data['name']) . "', discount_type = '" . (int)$data['discount_type'] . "', discount_value = '" . (float)$data['discount_value'] . "', buyqty = '" . (int)$data['buyqty'] . "', getqty = '" . (int)$data['getqty'] . "', `ribbontext` = '" . $this->db->escape(json_encode($data['ribbontext'], true)) . "',  `ordertotaltext` = '" . $this->db->escape(json_encode($data['ordertotaltext'], true)) . "', display_offer_at = '" . (int)$data['display_offer_at'] . "', `offer_heading_text` = '" . $this->db->escape(json_encode($data['offer_heading_text'], true)) . "', `offer_content` = '" . $this->db->escape(json_encode($data['offer_content'], true)) . "', grpproduct = '" . $this->db->escape($grpproduct) . "', grpcategory = '" . $this->db->escape($grpcategory) . "', grpmanufacturer = '" . $this->db->escape($grpmanufacturer) . "', customer_group = '" . $this->db->escape($customer_group) . "', store = '" . $this->db->escape($store) . "', status = '" . (int)$data['status'] . "' WHERE bganygrp_id = '" . (int)$bganygrp_id . "'");
				 
 		$this->cache->delete('bganygrp');
	}

	public function deletebganygrp($bganygrp_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "bganygrp WHERE bganygrp_id = '" . (int)$bganygrp_id . "'");
  
		$this->cache->delete('bganygrp');
	}

	public function getbganygrp($bganygrp_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "bganygrp WHERE bganygrp_id = '" . (int)$bganygrp_id . "' ");

		return $query->row;
	}

	public function getbganygrps($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "bganygrp WHERE 1 ";

		if (!empty($data['filter_name'])) {
			$sql .= " AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " GROUP BY bganygrp_id";

		$sort_data = array(
			'name',
			'bganygrp_id',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
 
	public function getTotalbganygrps() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bganygrp");

		return $query->row['total'];
	} 
}