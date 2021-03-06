<form action="" id="filter" category_id="{if $category}{$category->id}{/if}">

	{* Фильтрация по цене *}
	{if $settings->filters_summ && $prices_info}
	<div class="box_filter">
		<div class="title">Цена</div>
		<div class="range_container">
			{*
			full_min: {$prices_info->total->min_price}<br/>
			full_max: {$prices_info->total->max_price}<br/><br/>

			actual_min: {$prices_info->actual->min_price}<br/>
			actual_max: {$prices_info->actual->max_price}<br/><br/>

			get_min: {$prices_info->get_min}<br/>
			get_max: {$prices_info->get_max}<br/><br/>
			*}

			{*Добавить инпуты и скрипты*}

			<input
			type="text"
			style="display: none;"
			id="prices_range"
			class="range_slider js-range-slider"

			data-type="double"

			{*Полный минимум и максимум*}
			data-min="{$prices_info->total->min_price}"
			data-max="{$prices_info->total->max_price}"

			{*Пользовательские минимум и максимум*}
			data-from="{if $prices_info->from_price}{$prices_info->from_price}{else}{$prices_info->total->min_price}{/if}"
			data-to="{if $prices_info->to_price}{$prices_info->to_price}{else}{$prices_info->total->max_price}{/if}"

			{*Актуальные минимум и максимум*}
			data-fake_shadow="true"
			data-fake_from_min="{$prices_info->actual->min_price}"
			data-fake_from_max="{$prices_info->actual->max_price}"
			/>

			min: <input class="range_from" type="text" name="from_price" value="{if $prices_info->from_price}{$prices_info->from_price}{else}{$prices_info->total->min_price}{/if}" title="Минимальная цена"/>
			<br/>
			max: <input class="range_to" type="text" name="to_price" value="{if $prices_info->to_price}{$prices_info->to_price}{else}{$prices_info->total->max_price}{/if}" title="Максимальная цена"/>
		</div>

	</div>
	<!-- /ЦЕНА -->
	{/if}
	{* Фильтрация по цене (The End) *}

	{foreach $features as $f}
	<div data-feature="{$f->id}">
		<div class="title"><b>{$f->name|escape}</b></div>

		<div>
			{if $f->options}

				{foreach from=$f->options item=o name=features}
				<div class='checkbox'>
					<input type="checkbox" name="{$f->id}[]" value="{$o->value}" id="feature_{$f->id}_{$smarty.foreach.features.index}" {if !$o->actual}disabled{/if} {if $o->selected}checked{/if} {*onchange="this.form.submit();*}"/>

					<label for="feature_{$f->id}_{$smarty.foreach.features.index}">
						<div class="name">{$o->value|escape}</div>
					</label>
				</div>
				{/foreach}

			{else}

			<div class="range_container">
				{*
				full_min: {$f->full_min->value}<br/>
				full_max: {$f->full_max->value}<br/><br/>


				actual_min: {$f->actual_min->value}<br/>
				actual_max: {$f->actual_max->value}<br/><br/>

				get_min: {$f->get_min}<br/>
				get_max: {$f->get_max}<br/><br/>
				*}

				{*Добавить инпуты и скрипты*}

				<input
				type="text"
				style="display: none;"
				id="prices_range"
				class="range_slider js-range-slider"

				data-type="double"

				{*Полный минимум и максимум*}
				data-min="{$f->full_min->value}"
				data-max="{$f->full_max->value}"

				{*Пользовательские минимум и максимум*}
				data-from="{if $f->get_min}{$f->get_min}{else}{$f->full_min->value}{/if}"
				data-to="{if $f->get_max}{$f->get_max}{else}{$f->full_max->value}{/if}"

				{*Актуальные минимум и максимум*}
				data-fake_shadow="true"
				data-fake_from_min="{$f->actual_min->value}"
				data-fake_from_max="{$f->actual_max->value}"
				/>

				min: <input type="text" class="range_from"  name="min_{$f->id}" value="{if $f->get_min}{$f->get_min}{else}{$f->full_min->value}{/if}"
				title="Минимальное значение {$f->name|escape}"/>
				<br/>
				max: <input type="text" class="range_to"  name="max_{$f->id}" value="{if $f->get_max}{$f->get_max}{else}{$f->full_max->value}{/if}" title="Максимальное значение {$f->name|escape}"/>
			</div>

			{/if}
		</div>
	</div>
	<br/>
	{/foreach}

	<div class="submit">
		<input type="submit" value="Применить" class="submit_btn">
	</div>


	{* Плашка для аякса - позволит показать кнопку фильтрации с количеством *}
	<div class="show_box" style="display: none;">
		<div class="text">{$total_products_num|plural:'Найден':'Найдено':'Найдено'}</div>
		<div class="number">{$total_products_num} {$total_products_num|plural:'товар':'товара':'товаров'}</div>
		<input type="submit" class="showBtn" value="Показать"/>
	</div>

</form>
