<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
$settings = (require_once __DIR__ . '/config/settings.php');

$task = $argv[1] ?? '';
if (empty($task)) {
    categories($settings['one'], $settings['flexget']);
    categories($settings['two'], $settings['flexget']);
    categories($settings['three'], $settings['flexget']);
    categories($settings['four'], $settings['flexget']);
    feeds($settings['five'], $settings['flexget']);
    feeds($settings['six'], $settings['flexget']);
    sync_files($settings);
} else {
    $run = $settings[$task];
    if (!empty($run)) {
        if ($run['type'] === 'cats') {
            categories($run, $settings['flexget']);
        } elseif ($run['type'] === 'feeds') {
            feeds($run, $settings['flexget']);
        }
        sync_files($settings);
    }
}

function categories($a, $flexget)
{
    check_status($a['pid'], true);
    $cats = $a['cats'];
    foreach ($cats as $cat) {
        $torrent = sprintf($a['torrent'], $cat);
        $data    = $a['data'];
        $task    = "{$a['task']}_cat_{$cat}";
        $url     = sprintf($a['url'], $cat);
        process($torrent, $data, $flexget, $task, $url);
    }
    check_status($a['pid'], false);
}

function feeds($a, $flexget)
{
    check_status($a['pid'], true);
    $feeds = $a['feeds'];
    foreach ($feeds as $feed => $task) {
        $torrent = sprintf($a['torrent'], $task);
        $data    = $a['data'];
        $task    = "{$a['task']}_cat_{$task}";
        $url     = $feed;
        process($torrent, $data, $flexget, $task, $url);
    }
    check_status($a['pid'], false);
}

function make_dir($dir)
{
    if (!file_exists($dir)) {
        mkdir("{$dir}", 0777, true);
    }
}

function sync_files($settings)
{
    if ($settings['rsync_1']) {
        passthru($settings['rsync_1']);
    }
    if ($settings['rsync_2']) {
        make_dir('/data/Torrents/Trackers');
        //passthru($settings['rsync_2']);
    }
}

function check_status(string $file, bool $start)
{
    if ($start) {
        if (file_exists($file)) {
            exit("Already Running\n\n");
        }

        file_put_contents($file, 'running');
    } else {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

function process($torrent, $data, $flexget, $task, $url)
{
    make_dir($torrent);
    make_dir($data);
    $temp = "tasks:
  {$task} task:
    rss: {$url}
    verify_ssl_certificates: no
    accept_all: yes
    set:
      filename: '{{title}}.torrent'
    download:
      overwrite: no
      path: {$torrent}
";
    if (file_exists($data . '.config-lock')) {
        unlink($data . '.config-lock');
    }
    file_put_contents($data . 'config.yml', $temp);
    //$cmd = "{$settings['flexget']} -c {$data}config.yml --loglevel critical execute";
    $cmd = "{$flexget} -c {$data}config.yml execute";
    passthru($cmd);
}
