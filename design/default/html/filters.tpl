<div>
	<div class="form">
		<form action="" id="filter" category_id="{if $category}{$category->id}{/if}">

			<!-- ЦЕНА -->
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

			<!-- СВОЙСТВА -->
			{if $features}
			{foreach $features as $f}
			<div class="box_filter" data-feature="{$f->id}">
				<div class="title">{$f->name|escape}</div>

				<div class="bord">
					{foreach from=$f->options item=o name=features}
					<div class='checkbox {if $smarty.foreach.features.index >= "5"}hide{/if} {if !$o->actual}disabled{/if}' {if $smarty.foreach.features.index >= "5"}style="display: none;"{/if}>
						<input type="checkbox" name="{$f->id}[]" value="{$o->value}" id="feature_{$f->id}_{$smarty.foreach.features.index}" {if !$o->actual}disabled{/if} {if $o->selected}checked{/if}/>

						<label for="feature_{$f->id}_{$smarty.foreach.features.index}">
							<div class="name">{$o->value|escape}</div>
						</label>
					</div>
					{/foreach}

					<a href="#" class="more_list"><span>Еще +</span></a>
				</div>
			</div>
			{/foreach}
			{/if}
			<!-- /СВОЙСТВА -->
			<div class="show_box" id="filter_box">
				<div class="text">{$total_products_num|plural:'Найден':'Найдено':'Найдено'}</div>

				<div class="number">{$total_products_num} {$total_products_num|plural:'товар':'товара':'товаров'}</div>

				<input type="submit" class="showBtn" value="Показать"/>
			</div>
		</form>
	</div>
</div>