<?php
require_once('../api/Simpla.php');
$simpla = new Simpla();

// Тут нам нужны все префильтры что и в productsView.php
$serialized_query = $simpla->request->get('serialized_query');
$simpla->design->assign('serialized_query', $serialized_query);
parse_str($serialized_query, $parsed_query);

// Нам нужно знать категорию
$category_url = $simpla->request->get('category', 'string');

$filter = array();
$filter['visible'] = 1;	

// Выберем текущую категорию
if (!empty($category_url))
{
	$category = $simpla->categories->get_category((string)$category_url);
	if (empty($category) || (!$category->visible && empty($_SESSION['admin'])))
		return false;
	$simpla->design->assign('category', $category);
	$filter['category_id'] = $category->children;
}

// Система фильтрации
// Тут мы можем узнать полные диапазоны цен, без учета примененных фильтров
$prices_info = new stdClass;
$prices_info->total = array_shift($simpla->products->get_min_max_prices($filter));

// Так-же получим диапазон из формы, если он передан
if(!empty($from_price = $this->request->get('from_price')) && !empty($to_price = $this->request->get('to_price')))
{
	$prices_info->from_price 				= floatval($from_price);
	$prices_info->to_price					= floatval($to_price);
	$filter['prices_filter']['from_price'] 	= floatval($from_price);
	$filter['prices_filter']['to_price'] 	= floatval($to_price);
	$options_filter['prices_filter'] 		= $filter['prices_filter'];
}

// Система фильтрации
		// Свойства товаров
if(!empty($category))
{
	$features = array();
	$digital_features = array();
	foreach($this->features->get_features(array('category_id'=>$category->id, 'in_filter'=>1)) as $feature)
	{
				// Собираем массив всех фич
		$features[$feature->id] = $feature;
		if(!empty($val = $this->request->get($feature->id)))
		{
					// Добавляем в фильтрацию товаров
					// Отбор по заполненным фильтрам
			$filter['features'][$feature->id] = $val;
		}
		elseif($feature->digital)
		{

			$digital_features[$feature->id]->id = $feature->id;

			if(!empty($min_val = $this->request->get('min_'.$feature->id)) && !empty($max_val = $this->request->get('max_'.$feature->id)))
			{
						// Тут мы узнали заданные пользователем диапазоны
				$features[$feature->id]->get_min = floatval($min_val);
				$features[$feature->id]->get_max = floatval($max_val);

						// Флаг для учета конкретного диапазона для сборки запроса с фильтрацией
				$filter['features'][$feature->id] = true;

				$digital_features[$feature->id]->get_min = floatval($min_val);
				$digital_features[$feature->id]->get_max = floatval($max_val);
			}
		}
	}

			// Нам нужны опции только от видимых товаров
	$options_filter['visible'] 	= 1;
			// И только от товаров в наличии
	$options_filter['in_stock'] = 1;

	$features_ids = array_keys($features);

	if(!empty($features_ids))
		$options_filter['feature_id'] = $features_ids;

	$options_filter['category_id'] = $category->children;

	if(!empty($brand))
		$options_filter['brand_id'] = $brand->id;


			// Проверяем фильтр заполненных фич,
			// Получим только актуальные опции
	if(isset($filter['features']))
		$options_filter['features'] = $filter['features'];

			// Передадим все о цифровых опциях если такие есть
	if(!empty($digital_features))
	{

		$options_filter['digital_features'] = $digital_features;


				// Первая фильтрация, позволит узнать полные края диапазонов
		$options_mid = $this->features->get_options($options_filter);

		foreach ($options_mid as $option) {
			if(isset($features[$option->feature_id]))
			{
						// Диапазоны
				if($features[$option->feature_id]->digital)
				{

							// Тут нужно узнать полный минимум и максимум
					if(!isset($features[$option->feature_id]->full_min))
						$features[$option->feature_id]->full_min = $option;
					if(!isset($features[$option->feature_id]->full_max))
						$features[$option->feature_id]->full_max = $option;

					if($option->value < $features[$option->feature_id]->full_min->value)
						$features[$option->feature_id]->full_min = $option;

					if($option->value > $features[$option->feature_id]->full_max->value)
						$features[$option->feature_id]->full_max = $option;

				}
			}
		}

				// Очистим опцию, чтобы не влетела в следующий перебор
		unset($option);


				// Узнав полные диапазоны фильтра,
				// Мы можем учитывать его в фильтрации, или не учитывать
				// Основываясь на этой информации

		foreach ($digital_features as $df) {
			if(($features[$df->id]->full_min->value == $df->get_min) && ($features[$df->id]->full_max->value == $df->get_max))
				unset($filter['features'][$df->id]);
		}

		if(isset($filter['features']))
			$options_filter['features'] = $filter['features'];

	}

			// Тут мы можем узнать края цен, учитывая правильную фильтрацию по всем опциям
	$prices_info->actual = array_shift($this->products->get_min_max_prices($options_filter));


			// Тут нам нужны ВСЕ опции,
			// Нужно узнать какие из них Актуальные,
			// А какие еще и выделены в данный момент
	$options = $this->features->get_options($options_filter);

			// Тут мы уже знаем все опции всех подходящих товаров, в том числе и цифровые,
			// Без учета фильтрации по диапазону,
			// Самое время узнать минимум и максимум диапазона
	foreach($options as $option)
	{
		if(isset($features[$option->feature_id]))
		{
			if(!$features[$option->feature_id]->digital)
			{
						// Обычные свойства
				$features[$option->feature_id]->options[] = $option;
			}
			else
			{

						// Тут нужно узнать актуальные минимумы и максимумы 
				if($option->actual)
				{ 
							// Актуальные, но не учитывая остальные диапазоны
					if(!isset($features[$option->feature_id]->actual_min))
						$features[$option->feature_id]->actual_min = $option;
					if(!isset($features[$option->feature_id]->actual_max))
						$features[$option->feature_id]->actual_max = $option;

					if($option->value < $features[$option->feature_id]->actual_min->value)
						$features[$option->feature_id]->actual_min = $option;

					if($option->value > $features[$option->feature_id]->actual_max->value)
						$features[$option->feature_id]->actual_max = $option;
				}

			}
		}
	}

			// проверка - если у фичи нет ни 1 опции,
			// Или нет минимума и максимума под диапазон,
			// то выпилим ее и выводить не будем
	foreach($features as $i=>&$feature)
	{
		if( (empty($feature->options) && !$feature->digital) || ($feature->digital && (!isset($feature->full_max) || !isset($feature->full_min))) )
			unset($features[$i]);
	}

	$this->design->assign('features', $features);
}

 		// Передадим информацию по фильтрации цен
$this->design->assign('prices_info', $prices_info);

 		// Система фильтрации (The end)

header("Content-type: application/json; charset=UTF-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");
print json_encode($serialized_query);