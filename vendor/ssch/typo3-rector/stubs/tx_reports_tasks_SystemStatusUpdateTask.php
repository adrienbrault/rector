<?php

namespace RectorPrefix20210529;

if (\class_exists('tx_reports_tasks_SystemStatusUpdateTask')) {
    return;
}
class tx_reports_tasks_SystemStatusUpdateTask
{
}
\class_alias('tx_reports_tasks_SystemStatusUpdateTask', 'tx_reports_tasks_SystemStatusUpdateTask', \false);