{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="banner"}
  <h1><img id="logo" src="/modules/home/images/logo-home.png" width="400" height="67" alt="{$SITE_NAME}" /></h1>
{/block}


{block name="homeSearch"}
  <form method="get" action="search.php" id="searchform">
    <div id="homesearch">
      <table class="searchform" cellpadding="0" cellspacing="3px" border="0" width="100%">
        <tr>
          <td width="42%" align="right">
            Search {$SITE_NAME}:
          </td>
          <td class="inputtd" width="50%">
            <input class="forminput" type="text" id="filter" name="search_terms" value="" />
          </td>
          <td width="8%" align="left">
            <input src="/common/images/search_button.png" name="search" id="sch_btn" type="image" value="Search" />
          </td>
        </tr>
      </table>
    </div>
  </form>
{/block}

{block name="homeFooter"}
  <div id="download">
    <a href="../download/">
      <img src="/modules/home/images/download-bbplus.png" width="32" height="26" alt="Download" align="absmiddle" />
      Add the BlackBerry shortcut to your home screen
    </a>
    <br />
  </div>
{/block}
