<?php

/**
 * Simpla CMS
 *
 * @copyright	2011 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('Simpla.php');

class Features extends Simpla
{

	function get_features($filter = array())
	{
		$category_id_filter = '';
		if(isset($filter['category_id']))
			$category_id_filter = $this->db->placehold('AND id in(SELECT feature_id FROM __categories_features AS cf WHERE cf.category_id in(?@))', (array)$filter['category_id']);

		$in_filter_filter = '';
		if(isset($filter['in_filter']))
			$in_filter_filter = $this->db->placehold('AND f.in_filter=?', intval($filter['in_filter']));

		$id_filter = '';
		if(!empty($filter['id']))
			$id_filter = $this->db->placehold('AND f.id in(?@)', (array)$filter['id']);

		// Выбираем свойства
		$query = $this->db->placehold("	SELECT
										id,
										name,
										position,
										in_filter,
										digital
										FROM __features AS f
										WHERE 1
										$category_id_filter
										$in_filter_filter $id_filter
										ORDER BY f.position");
		$this->db->query($query);
		return $this->db->results();
	}

	function get_feature($id)
	{
		// Выбираем свойство
		$query = $this->db->placehold("	SELECT
										id,
										name,
										position,
										in_filter,
										digital
										FROM __features
										WHERE id=?
										LIMIT 1",
										$id);
		$this->db->query($query);
		return $this->db->result();
	}

	function get_feature_categories($id)
	{
		$query = $this->db->placehold("SELECT cf.category_id as category_id FROM __categories_features cf
										WHERE cf.feature_id = ?", $id);
		$this->db->query($query);
		return $this->db->results('category_id');
	}

	public function add_feature($feature)
	{
		$query = $this->db->placehold("INSERT INTO __features SET ?%", $feature);
		$this->db->query($query);
		$id = $this->db->insert_id();
		$query = $this->db->placehold("UPDATE __features SET position=id WHERE id=? LIMIT 1", $id);
		$this->db->query($query);
		return $id;
	}

	public function update_feature($id, $feature)
	{
		$query = $this->db->placehold("UPDATE __features SET ?% WHERE id in(?@) LIMIT ?", (array)$feature, (array)$id, count((array)$id));
		$this->db->query($query);
		return $id;
	}

	public function delete_feature($id = array())
	{
		if(!empty($id))
		{
			$query = $this->db->placehold("DELETE FROM __features WHERE id=? LIMIT 1", intval($id));
			$this->db->query($query);
			$query = $this->db->placehold("DELETE FROM __options WHERE feature_id=?", intval($id));
			$this->db->query($query);
			$query = $this->db->placehold("DELETE FROM __categories_features WHERE feature_id=?", intval($id));
			$this->db->query($query);
		}
	}


	public function delete_option($product_id, $feature_id)
	{
		$query = $this->db->placehold("DELETE FROM __options WHERE product_id=? AND feature_id=? LIMIT 1", intval($product_id), intval($feature_id));
		$this->db->query($query);
	}


	public function update_option($product_id, $feature_id, $value)
	{
		if($value != '')
			$query = $this->db->placehold("REPLACE INTO __options SET value=?, product_id=?, feature_id=?", $value, intval($product_id), intval($feature_id));
		else
			$query = $this->db->placehold("DELETE FROM __options WHERE feature_id=? AND product_id=?", intval($feature_id), intval($product_id));
		return $this->db->query($query);
	}


	public function add_feature_category($id, $category_id)
	{
		$query = $this->db->placehold("INSERT IGNORE INTO __categories_features SET feature_id=?, category_id=?", $id, $category_id);
		$this->db->query($query);
	}

	public function update_feature_categories($id, $categories)
	{
		$id = intval($id);
		$query = $this->db->placehold("DELETE FROM __categories_features WHERE feature_id=?", $id);
		$this->db->query($query);


		if(is_array($categories))
		{
			$values = array();
			foreach($categories as $category)
				$values[] = "($id , ".intval($category).")";

			$query = $this->db->placehold("INSERT INTO __categories_features (feature_id, category_id) VALUES ".implode(', ', $values));
			$this->db->query($query);

			// Удалим значения из options
			$query = $this->db->placehold("DELETE o FROM __options o
			                               LEFT JOIN __products_categories pc ON pc.product_id=o.product_id
			                               WHERE o.feature_id=? AND pc.position=(SELECT MIN(pc2.position) FROM __products_categories pc2 WHERE pc.product_id=pc2.product_id) AND pc.category_id not in(?@)", $id, $categories);
			$this->db->query($query);
		}
		else
		{
			// Удалим значения из options
			$query = $this->db->placehold("DELETE o FROM __options o WHERE o.feature_id=?", $id);
			$this->db->query($query);
		}
	}

	// Система фильтрации
	// Довольно сильно переработана функция
	public function get_options($filter = array(), $adminPanel = false)
	{
		$feature_id_filter = '';
		$product_id_filter = '';
		$category_id_filter = '';
		$visible_filter = '';
		$brand_id_filter = '';
		$features_filter = '1 as actual';

		// Система фильтрации
		// Нужны опции только товаров в наличии и с ценой
		$in_stock_filter = '';
		// Необходимый запрос для отметки выбранных опций сразу
		$selected_options = '';

		if(empty($filter['feature_id']) && empty($filter['product_id']))
			return array();

		$group_by = '';
		if(isset($filter['feature_id']))
			$group_by = 'GROUP BY feature_id, value';

		if(isset($filter['feature_id']))
			$feature_id_filter = $this->db->placehold('AND po.feature_id in(?@)', (array)$filter['feature_id']);

		if(isset($filter['product_id']))
			$product_id_filter = $this->db->placehold('AND po.product_id in(?@)', (array)$filter['product_id']);

		if(isset($filter['category_id']))
			$category_id_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id=po.product_id AND pc.category_id in(?@)', (array)$filter['category_id']);

		if(isset($filter['visible']))
			$visible_filter = $this->db->placehold('INNER JOIN __products p ON p.id=po.product_id AND visible=?', intval($filter['visible']));

		if(isset($filter['brand_id']))
			$brand_id_filter = $this->db->placehold('AND po.product_id in(SELECT id FROM __products WHERE brand_id in(?@))', (array)$filter['brand_id']);

		if(isset($filter['in_stock']))
			$in_stock_filter = $this->db->placehold('AND (SELECT count(*)>0 FROM __variants pv WHERE pv.product_id=po.product_id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock>0) LIMIT 1) = ?', intval($filter['in_stock']));


		/*
		if(isset($filter['features']))
			foreach($filter['features'] as $feature=>$value)
			{
				$features_filter .= $this->db->placehold('AND (po.feature_id=? OR po.product_id in (SELECT product_id FROM __options WHERE feature_id=? AND value=? )) ', $feature, $feature, $value);
			}
		*/

		// Система фильтрации
		if(isset($filter['features']))
		{
			$first_iterration = true;
			$total = count($filter['features']);
			$counter = 0;
			foreach($filter['features'] as $feature=>$value)
			{
				$counter++;

				if($first_iterration)
				{
					$first_iterration  = false;
					$features_filter  = $this->db->placehold('IF ((');
					$selected_options = $this->db->placehold(',IF ((');
				}

				if($filter['digital_features'][$feature]->id != $feature)
				{
					// Запрос для обычных свойств

					$features_filter .= $this->db->placehold('(po.feature_id=? OR po.product_id in (SELECT product_id FROM __options WHERE product_id=po.product_id AND feature_id=? AND value in(?@) )) ', $feature, $feature, $value);

					$selected_options .= $this->db->placehold('(po.feature_id=? AND po.value in(?@) ) ', $feature, $value);

				}
				else
				{
					// Запрос для диапазонных свойств
					$features_filter .= $this->db->placehold('(po.feature_id=? OR po.product_id in (SELECT product_id FROM __options WHERE product_id=po.product_id AND feature_id=? AND value BETWEEN ? AND ? )) ',
											$feature,
											$feature,
											$filter['digital_features'][$feature]->get_min,
											$filter['digital_features'][$feature]->get_max);

					$selected_options .= $this->db->placehold('(po.feature_id=? AND po.value BETWEEN ? AND ? ) ',
											$feature,
											$filter['digital_features'][$feature]->get_min,
											$filter['digital_features'][$feature]->get_max);
				}

				if($counter != $total)
				{
					$features_filter  .= $this->db->placehold('AND ');
					$selected_options .= $this->db->placehold('OR ');
				}
				else
				{
					// В последнем шаге, если есть фильтрация по ценам, нужно добавить и её
					if(!empty($filter['prices_filter']))
					{
						$features_filter  .= $this->db->placehold('AND (po.product_id in (SELECT product_id FROM __variants WHERE po.product_id=product_id AND price BETWEEN ? AND ? ))',
											$filter['prices_filter']['from_price'],
											$filter['prices_filter']['to_price']);
					}

					$features_filter  .= $this->db->placehold('), 1, 0) AS actual');
					$selected_options .= $this->db->placehold('), 1, 0) AS selected');
				}

				// Собрали запрос, который отдаст все опции, но покажет какие актуальные
			}
		}
		elseif(!empty($filter['prices_filter']))
		{

			// Тут нужно обработать фильтрацию по ценам при условии того что фильтрации по другим параметрам нет
			// Пум пурум, пум, пум
			$features_filter  	 = $this->db->placehold('IF ((');
			$features_filter  	.= $this->db->placehold('(po.product_id in (SELECT product_id FROM __variants WHERE price BETWEEN ? AND ? ))',
								$filter['prices_filter']['from_price'],
								$filter['prices_filter']['to_price']);
			$features_filter  	.= $this->db->placehold('), 1, 0) AS actual');

		}

		// ,count(po.product_id) as count
		$query = $this->db->placehold("SELECT
										po.product_id,
										po.feature_id,
										po.value,
										$features_filter
										$selected_options
		    							FROM __options po
		    							$visible_filter
										$category_id_filter
										WHERE 1
										$feature_id_filter
										$product_id_filter
										$brand_id_filter
										$in_stock_filter
										GROUP BY po.feature_id, po.value, actual
										ORDER BY value=0, -value DESC, value");

		$this->db->query($query);
		$mid_result = $this->db->results();


		// Отметим актуальные, оставим униклаьные
		// Разложим сначала все опции по фичам
		$featuredOptions = array();
		$actualizedArray = array();

		if($mid_result && !$adminPanel)
		{
			foreach ($mid_result as $mr) 
			{
				$featuredOptions[$mr->feature_id]['optionObjects'][] = $mr;
				$featuredOptions[$mr->feature_id]['values'][] = $mr->value;
			}
			
			foreach ($featuredOptions as $feature_id => &$featureOptions)
			{
				// Оставим только униальные значения
				$featureOptions['values'] = array_unique($featureOptions['values']);

				foreach ($featureOptions['values'] as $uniqueValue)
				{
					//$actualizedArray
					$actualFlag = 0;
					$selectedFlag = 0;

					foreach($featureOptions['optionObjects'] as $optionObject)
					{
						if($optionObject->value == $uniqueValue)
						{
							if ($optionObject->actual)
								$actualFlag = 1;
							if ($optionObject->selected)
								$selectedFlag = 1;
						}
					}

					$uniqueObj = new stdClass();
					$uniqueObj->value = $uniqueValue;
					$uniqueObj->feature_id = $feature_id;
					$uniqueObj->actual = $actualFlag;
					$uniqueObj->selected = $selectedFlag;

					$actualizedArray[] = $uniqueObj;
				}
			}
		}
		else
			$actualizedArray = $mid_result;

		return $actualizedArray;
	}
	// Система фильтрации (The end)

	public function get_product_options($product_id)
	{
		$query = $this->db->placehold("SELECT f.id as feature_id, f.name, po.value, po.product_id FROM __options po LEFT JOIN __features f ON f.id=po.feature_id
										WHERE po.product_id in(?@) ORDER BY f.position", (array)$product_id);

		$this->db->query($query);
		return $this->db->results();
	}
}
