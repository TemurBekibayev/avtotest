<?php
$target = '../application/public';
$link = 'api';

if (file_exists($link)) {
    if (is_link($link)) {
        unlink($link);
        echo "Old symlink removed.<br>";
    } else {
        echo "Error: 'api' already exists and is not a symlink. Please delete or rename it first.<br>";
        exit;
    }
}

if (symlink($target, $link)) {
    echo "Success: Symlink 'api' -> '../application/public' created successfully!";
} else {
    echo "Error: Failed to create symlink.";
}
