<?php
// 初期化等
$config = parse_ini_file('../data/config.ini');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 可視のカードを取得
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/boards/' . $config['board'] . '/cards/visible?key=' . $config['key'] . '&token=' . $config['token']);
$result_card =  json_decode(curl_exec($ch), true);
foreach ($result_card as $value_card) {
    // カードごとのチェックリストをアイテムごと取得して名前を確認
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/cards/' . $value_card['id'] . '/checklists?key=' . $config['key'] . '&token=' . $config['token'] . '&checkItem_fields=name&fields=name');
    $result_list = json_decode(curl_exec($ch), true);
    foreach ($result_list as $value_list) {
        // 実行時引数でモード変更(day, week, month)
        if (preg_match('/' . $config[$argv[1]] . '/', $value_list['name'])) {
            foreach ($value_list['checkItems'] as $value_item) {
                // チェックを外す
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/cards/' . $value_card['id'] . '/checkItem/' . $value_item['id']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                    'key' => $config['key'],
                    'token' => $config['token'],
                    'state' => 'incomplete'
                )));
                curl_exec($ch);
            }
        }
    }
}

// 解放
curl_close($ch);
