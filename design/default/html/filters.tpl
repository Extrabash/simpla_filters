<form action="" id="filter" category_id="{if $category}{$category->id}{/if}">

	{* Фильтрация по цене *}
	{if $settings->filters_summ}
	<div class="box_filter">
		<div class="title">Цена</div>

		<div class="bord">
			<div class="range_filter price_range">
				<input type="text" name="" value="" id="price_range" />
				<input type="text" name="price_from" min_val="{$min_max_prices->min_price}" value="{if $min_max_prices_actual->min_price}{$min_max_prices_actual->min_price}{else}{$min_max_prices->min_price}{/if}" class="input ot left" />
				<input type="text" name="price_to" max_val="{$min_max_prices->max_price}" value="{if $min_max_prices_actual->max_price}{$min_max_prices_actual->max_price}{else}{$min_max_prices->max_price}{/if}" class="input do right" />
				<div class="clear"></div>
			</div>

			<div class="submit">
				<input type="submit" value="Применить" class="submit_btn">
			</div>
		</div>
	</div>
	<!-- /ЦЕНА -->
	{/if}
	{* Фильтрация по цене (The End) *}

	{foreach $features as $f}
	<div data-feature="{$f->id}">
		<div class="title"><b>{$f->name|escape} - {$f->id}</b></div>

		<div>
			{if $f->options}
				{foreach from=$f->options item=o name=features}
				<div class='checkbox'>
					<input type="checkbox" name="{$f->id}[]" value="{$o->value}" id="feature_{$f->id}_{$smarty.foreach.features.index}" {if !$o->actual}disabled{/if} {if $o->selected}checked{/if} onchange="this.form.submit();"/>

					<label for="feature_{$f->id}_{$smarty.foreach.features.index}">
						<div class="name">{$o->value|escape}</div>
					</label>
				</div>
				{/foreach}
			{else}


				full_min: {$f->full_min->value}<br/>
				full_max: {$f->full_max->value}<br/><br/>
				
				
				actual_min: {$f->actual_min->value}<br/>
				actual_max: {$f->actual_max->value}<br/><br/>

				get_min: {$f->get_min}<br/>
				get_max: {$f->get_max}<br/><br/>

				{*Добавить инпуты и крипты*}

				min: <input type="text" name="min_{$f->id}" value="
				{if $f->get_min}
				{$f->get_min}
				{else}
				{$f->actual_min->value}
				{/if}" title="Минимальное значение {$f->name|escape}"/>
				<br/>
				max: <input type="text" name="max_{$f->id}" value="
				{if $f->get_max}
				{$f->get_max}
				{else}
				{$f->actual_max->value}
				{/if}" title="Максимальное значение {$f->name|escape}"/>

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