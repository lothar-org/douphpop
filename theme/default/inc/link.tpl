<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!--{if $link}-->
<div class="wrap">
 <div class="link"> <strong>{$lang.link}：</strong>
  <!--{foreach from=$link name=link item=l}-->
  <a href="{$l.link_url}" target="_blank" >{$l.link_name}</a>
  <!--{/foreach}-->
 </div>
</div>
<!--{/if}-->