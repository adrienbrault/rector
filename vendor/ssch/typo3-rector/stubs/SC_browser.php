<?php

namespace RectorPrefix20210529;

if (\class_exists('SC_browser')) {
    return;
}
class SC_browser
{
}
\class_alias('SC_browser', 'SC_browser', \false);