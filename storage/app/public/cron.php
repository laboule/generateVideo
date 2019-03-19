<?php

// CRON JOB 0 5 * * 0 (every sunday at 5 in the morning)
// Delete Files from Archives
$list = glob("./archives/*.{docx,tmp}", GLOB_BRACE);

foreach($list as $file)
{
unlink($file);
}

unset($list);

// Move Files from roadbook folder to Archives
$list = glob("./roadbook/*.{docx,tmp}", GLOB_BRACE);

foreach($list as $file)
{
$fileName =  basename($file);
rename($file, './archives/'.$fileName);
} 




