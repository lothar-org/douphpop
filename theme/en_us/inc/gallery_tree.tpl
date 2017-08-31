<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<div class="treeBox">
 <h3>{$lang.gallery_tree}</h3>
 <ul>
  <!--{foreach from=$gallery_category item=cate}-->
  <li{if $cate.cur} class="cur"{/if}><a href="{$cate.url}">{$cate.cat_name}</a></li>
  <!--{if $cate.child}-->
  <ul>
   <!--{foreach from=$cate.child item=child}-->
   <li{if $child.cur} class="cur"{/if}>-<a href="{$child.url}">{$child.cat_name}</a></li>
   <!--{/foreach}-->
  </ul>
  <!--{/if}-->
  <!--{/foreach}-->
 </ul>
</div>