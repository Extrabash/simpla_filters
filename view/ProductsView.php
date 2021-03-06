<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2011 Denis Pikusov
 * @link 		http://simplacms.ru
 * @author 		Denis Pikusov
 *
 * Этот класс использует шаблон products.tpl
 *
 */

require_once('View.php');

class ProductsView extends View
{
 	/**
	 *
	 * Отображение списка товаров
	 *
	 */
	function fetch()
	{
		// GET-Параметры
		$category_url = $this->request->get('category', 'string');
		$brand_url    = $this->request->get('brand', 'string');

		$filter = array();
		$filter['visible'] = 1;

		// Если задан бренд, выберем его из базы
		if (!empty($brand_url))
		{
			$brand = $this->brands->get_brand((string)$brand_url);
			if (empty($brand))
				return false;
			$this->design->assign('brand', $brand);
			$filter['brand_id'] = $brand->id;
		}

		// Выберем текущую категорию
		if (!empty($category_url))
		{
			$category = $this->categories->get_category((string)$category_url);
			if (empty($category) || (!$category->visible && empty($_SESSION['admin'])))
				return false;
			$this->design->assign('category', $category);
			$filter['category_id'] = $category->children;
		}

		// Если задано ключевое слово
		$keyword = $this->request->get('keyword');
		if (!empty($keyword))
		{
			$this->design->assign('keyword', $keyword);
			$filter['keyword'] = $keyword;
		}

		// Сортировка товаров, сохраняем в сесси, чтобы текущая сортировка оставалась для всего сайта
		if($sort = $this->request->get('sort', 'string'))
			$_SESSION['sort'] = $sort;
		if (!empty($_SESSION['sort']))
			$filter['sort'] = $_SESSION['sort'];
		else
			$filter['sort'] = 'position';
		$this->design->assign('sort', $filter['sort']);


		// Система фильтрации
		// Тут мы можем узнать полные диапазоны цен, без учета примененных фильтров
		$prices_info = new stdClass;
		$prices_info->total = array_shift($this->products->get_min_max_prices($filter));

		// Так-же получим диапазон из формы, если он передан
		if(!empty($from_price = $this->request->get('from_price')) && !empty($to_price = $this->request->get('to_price')))
		{
			$prices_info->from_price 				= floatval($from_price);
			$prices_info->to_price					= floatval($to_price);
			$filter['prices_filter']['from_price'] 	= floatval($from_price);
			$filter['prices_filter']['to_price'] 	= floatval($to_price);
			$options_filter['prices_filter'] 		= $filter['prices_filter'];
		}


		// Система фильтрации 1
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

 		// Система фильтрации (The end) 1



		// Постраничная навигация
		$items_per_page = $this->settings->products_num;
		// Текущая страница в постраничном выводе
		$current_page = $this->request->get('page', 'integer');
		// Если не задана, то равна 1
		$current_page = max(1, $current_page);
		$this->design->assign('current_page_num', $current_page);
		// Вычисляем количество страниц
		$products_count = $this->products->count_products($filter);

		// Показать все страницы сразу
		if($this->request->get('page') == 'all')
			$items_per_page = $products_count;

		$pages_num = ceil($products_count/$items_per_page);
		$this->design->assign('total_pages_num', $pages_num);
		$this->design->assign('total_products_num', $products_count);

		$filter['page'] = $current_page;
		$filter['limit'] = $items_per_page;

		///////////////////////////////////////////////
		// Постраничная навигация END
		///////////////////////////////////////////////


		$discount = 0;
		if(isset($_SESSION['user_id']) && $user = $this->users->get_user(intval($_SESSION['user_id'])))
			$discount = $user->discount;

		// Система фильтрации 2
		// Передадим все о цифровых опциях если такие есть
		if(!empty($digital_features))
			$filter['digital_features'] = $digital_features;
		// Система фильтрации end 2


		// Товары
		$products = array();
		foreach($this->products->get_products($filter) as $p)
			$products[$p->id] = $p;

		// Если искали товар и найден ровно один - перенаправляем на него
		// if(!empty($keyword) && $products_count == 1)
		//	 header('Location: '.$this->config->root_url.'/products/'.$p->url);

		if(!empty($products))
		{
			$products_ids = array_keys($products);
			foreach($products as &$product)
			{
				$product->variants = array();
				$product->images = array();
				$product->properties = array();
			}

			$variants = $this->variants->get_variants(array('product_id'=>$products_ids, 'in_stock'=>true));

			foreach($variants as &$variant)
			{
				//$variant->price *= (100-$discount)/100;
				$products[$variant->product_id]->variants[] = $variant;
			}

			$images = $this->products->get_images(array('product_id'=>$products_ids));
			foreach($images as $image)
				$products[$image->product_id]->images[] = $image;

			foreach($products as &$product)
			{
				if(isset($product->variants[0]))
					$product->variant = $product->variants[0];
				if(isset($product->images[0]))
					$product->image = $product->images[0];
			}


			/*
			$properties = $this->features->get_options(array('product_id'=>$products_ids));
			foreach($properties as $property)
				$products[$property->product_id]->options[] = $property;
			*/

			$this->design->assign('products', $products);
 		}

		// Выбираем бренды, они нужны нам в шаблоне
		if(!empty($category))
		{
			$brands = $this->brands->get_brands(array('category_id'=>$category->children, 'visible'=>1));
			$category->brands = $brands;
		}

		// Устанавливаем мета-теги в зависимости от запроса
		if($this->page)
		{
			$this->design->assign('meta_title', $this->page->meta_title);
			$this->design->assign('meta_keywords', $this->page->meta_keywords);
			$this->design->assign('meta_description', $this->page->meta_description);
		}
		elseif(isset($category))
		{
			$this->design->assign('meta_title', $category->meta_title);
			$this->design->assign('meta_keywords', $category->meta_keywords);
			$this->design->assign('meta_description', $category->meta_description);
		}
		elseif(isset($brand))
		{
			$this->design->assign('meta_title', $brand->meta_title);
			$this->design->assign('meta_keywords', $brand->meta_keywords);
			$this->design->assign('meta_description', $brand->meta_description);
		}
		elseif(isset($keyword))
		{
			$this->design->assign('meta_title', $keyword);
		}


		$this->body = $this->design->fetch('products.tpl');
		return $this->body;
	}



}
