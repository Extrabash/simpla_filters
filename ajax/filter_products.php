<?php
require_once('../api/Simpla.php');
$simpla = new Simpla();

// Тут нам нужны все префильтры что и в productsView.php
$serialized_query = $simpla->request->get('serialized_query');
$simpla->design->assign('serialized_query', $serialized_query);
parse_str($serialized_query, $parsed_query);

$category_id = $simpla->request->get('category_id');

// Фильтры кроме сумм применимы только внутри категорий
// Фильтры по ценам доступны и для всех товаров - каталога
if(!empty($category_id))
{
	$category = $simpla->categories->get_category(intval($category_id));
	if (empty($category) || !$category->visible)
		return false;

	$filter['category_id'] = $category->children;


	$features = array();

	$features = array();
	foreach($simpla->features->get_features(array('category_id'=>$category->id, 'in_filter'=>1)) as $feature)
	{
		$features[$feature->id] = $feature;
		if(!empty($val = $parsed_query[$feature->id]))
		{
			$filter['features'][$feature->id] = $val;
		}
	}

	$options_filter['visible'] = 1;
	$options_filter['in_stock'] = 1;

	$features_ids = array_keys($features);
	if(!empty($features_ids))
		$options_filter['feature_id'] = $features_ids;
	$options_filter['category_id'] = $category->children;
	if(isset($filter['features']))
		$options_filter['features'] = $filter['features'];
	if(!empty($brand))
		$options_filter['brand_id'] = $brand->id;

	$options = $simpla->features->get_options($options_filter);

	foreach($options as $option)
	{
		if(isset($features[$option->feature_id]))
			$features[$option->feature_id]->options[] = $option;
	}

	foreach($features as $i=>&$feature)
	{
		if(empty($feature->options))
			unset($features[$i]);
	}

	$simpla->design->assign('features', $features);
}

// выводим и считаем страницы только для товаров в наличии 
$filter['in_stock'] = 1;

// Найдем минимальную и максмальную цены без 
// самой фильтрации цен, потом добавим её в фильтр
$min_max_prices = new stdClass;
$min_max_prices = $simpla->products->get_min_max_prices($filter);


// Обработка диапазона цен - передадим актуальные диапазоны
if(!empty($min_max_prices))
{
	$simpla->design->assign('min_max_prices', $min_max_prices);
	if(!empty($parsed_query['price_from']) || !empty($parsed_query['price_to']))
	{
		$min_max_prices_actual = new stdClass;

		if(!empty($parsed_query['price_from']))
		{
			$min_max_prices_actual->min_price = intval($parsed_query['price_from']);
			$filter['min_price'] = $min_max_prices_actual->min_price;
		}

		if(!empty($parsed_query['price_to']))
		{
			$min_max_prices_actual->max_price = intval($parsed_query['price_to']);
			$filter['max_price'] = $min_max_prices_actual->max_price;
		}
	}
}

// Вычисляем количество продуктов
$products_count = $simpla->products->count_products($filter);
$simpla->design->assign('total_products_num', $products_count);

// Обработка диапазона цен - передадим актуальные диапазоны
if(!empty($min_max_prices_actual))
	$simpla->design->assign('min_max_prices_actual', $min_max_prices_actual);




$result = $simpla->design->fetch('filters.tpl');

header("Content-type: application/json; charset=UTF-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");
print json_encode($result);