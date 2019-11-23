<?php
// 初期化等
$config = parse_ini_file(dirname(__FILE__) . '/../data/config.ini');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$score = 0    // スコア記録用 最後にまとめて送信

// 可視のカードを取得
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/boards/' . $config['board'] . '/cards/visible?key=' . $config['key'] . '&token=' . $config['token']);
$result_card = json_decode(curl_exec($ch), true);
foreach ($result_card as $value_card) {
    // カードごとのチェックリストをアイテムごと取得して名前を確認
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_POSTFIELDS, null);
    curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/cards/' . $value_card['id'] . '/checklists?key=' . $config['key'] . '&token=' . $config['token'] . '&checkItem_fields=name&fields=name');
    $result_list = json_decode(curl_exec($ch), true);
    foreach ($result_list as $value_list) {
        // 実行時引数でモード変更(day, week, month)
        if (preg_match('/' . $config[$argv[1]] . '/', $value_list['name'])) {
            foreach ($value_list['checkItems'] as $value_item) {
                // 状態をみてスコアを計算(正がチェック→加算, 負がチェック→減算)
                // スコアを見てリセット(スコアが正ならチェック外す、スコアが負ならチェック入れる)
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
                curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/cards/' . $value_card['id'] . '/checkItem/' . $value_item['id']);
                if (preg_match('/\[(-?[0-9]+)\]/', $value_list['name'], $matches)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                        'key' => $config['key'],
                        'token' => $config['token'],
                        'state' => ($matches[1] >= 0)?'incomplete':'complete')));
                    curl_exec($ch);
                    $score += $matches[1];
                }
                else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                        'key' => $config['key'], 
                        'token' => $config['token'], 
                        'state' => 'incomplete'))); 
                    curl_exec($ch); 
                }
            }
        }
    }
}

// スコアを送信
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
curl_setopt($ch, CURLOPT_URL, $config['score_url']);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
    'score' => $score
)));
curl_exec($ch);

// 解放
curl_close($ch);
