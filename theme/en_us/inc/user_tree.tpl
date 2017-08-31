<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<div class="userTree">
 <h3>{$lang.user}</h3>
 <ul>
  <li{if $rec eq 'default'} class="cur"{/if}><a href="{$url.user}">{$lang.user_main}</a></li>
  <li{if $rec eq 'edit'} class="cur"{/if}><a href="{$url.edit}">{$lang.user_edit}</a></li>
  <li{if $rec eq 'password'} class="cur"{/if}><a href="{$url.password}">{$lang.user_password_edit}</a></li>
  <!--{if $open.order}-->
  <li{if $rec eq 'order_list' || $rec eq 'order'} class="cur"{/if}><a href="{$url.order_list}">{$lang.order_my}</a></li>
  <!--{/if}-->
  <li><a href="{$url.logout}">{$lang.user_logout}</a></li>
 </ul>
</div>