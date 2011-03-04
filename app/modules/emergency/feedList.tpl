{foreach $feeds as $key=>$item}     
  <ul class="nav feedItem">
      <li>
         {$key}
      </li>   
      <li>
         <label>URL</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][BASE_URL]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][BASE_URL]" value="{$item.BASE_URL|escape}" />
      </li>
      <li>
         <label>Controller</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][CONTROLLER_CLASS]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][CONTROLLER_CLASS]" value="{$item.CONTROLLER_CLASS|escape}" />
      </li>
  </ul>
  
  
  
{/foreach}
