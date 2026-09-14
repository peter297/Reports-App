<?php
$target = '/home/alameena/public_html/reports.alameenacademy.com/storage/app/public/attachments';
$link = '/home/alameena/public_html/reports.alameenacademy.com/public/storage/attachments';

if (symlink($target, $link)) {
    echo 'Symlink created successfully.';
} else {
    echo 'Failed to create symlink.';
}
?>

