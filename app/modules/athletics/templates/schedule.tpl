{include file="findInclude:common/templates/header.tpl"}

{$schedulelist = array()}
{$event = array()}
{foreach $schedules as $schedule}
{capture name="scheduleTitle" assign="scheduleTitle"}
    {include file="findInclude:modules/athletics/templates/schedule_summary.tpl" schedule=$schedule}
{/capture}
{$event['title'] = $scheduleTitle}
{$event['url'] = $schedule.url}
{$schedulelist[] = $event}
{/foreach}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$schedulelist}

{include file="findInclude:common/templates/footer.tpl"}
