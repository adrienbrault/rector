<?php

namespace RectorPrefix20210529;

if (\class_exists('t3lib_matchCondition_abstract')) {
    return;
}
class t3lib_matchCondition_abstract
{
}
\class_alias('t3lib_matchCondition_abstract', 't3lib_matchCondition_abstract', \false);