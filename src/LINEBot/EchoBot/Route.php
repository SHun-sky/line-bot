<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace LINE\LINEBot\EchoBot;

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\Exception\UnknownEventTypeException;
use LINE\LINEBot\Exception\UnknownMessageTypeException;

class Route
{
    public function register(\Slim\App $app)
    {
        $app->post('/callback', function (\Slim\Http\Request $req, \Slim\Http\Response $res) {
            /** @var \LINE\LINEBot $bot */
            $bot = $this->bot;
            /** @var \Monolog\Logger $logger */
            $logger = $this->logger;

            $signature = $req->getHeader(HTTPHeader::LINE_SIGNATURE);
            if (empty($signature)) {
                return $res->withStatus(400, 'Bad Request');
            }

            // Check request with signature and parse request
            try {
                $events = $bot->parseEventRequest($req->getBody(), $signature[0]);
            } catch (InvalidSignatureException $e) {
                return $res->withStatus(400, 'Invalid signature');
            } catch (UnknownEventTypeException $e) {
                return $res->withStatus(400, 'Unknown event type has come');
            } catch (UnknownMessageTypeException $e) {
                return $res->withStatus(400, 'Unknown message type has come');
            } catch (InvalidEventRequestException $e) {
                return $res->withStatus(400, "Invalid event request");
            }

            foreach ($events as $event) {
                if (!($event instanceof MessageEvent)) {
                    $logger->info('Non message event has come');
                    continue;
                }

                if (!($event instanceof TextMessage)) {
                    $logger->info('Non text message has come');
                    continue;
                }

                // $replyText = $event->getText();
                $message = $event -> getTetx();

                // $res_contents = get_request($message);
                // リクエスト
                // $replyText = 'うるせーばかやろー。';
                $carousel_message = getCarouselMessage();
                $confirm_message = getConfirm();

                $message = new MultiMessageBuilder();
                $message->add($carousel_message);
                $message->add($confirm_message);

                $logger->info('Reply text: ' . $replyText);
                $resp = $bot->replyText($event->getReplyToken(), $replyText);
                $logger->info($resp->getHTTPStatus() . ': ' . $resp->getRawBody());
            }

            $res->write('OK');
            return $res;
        });
    }

    function get_request($param) {
      $base_url = 'https://qiita.com';

      $tag = 'PHP';
      $query = ['page'=>'1','per_page'=>'5'];

      $response = file_get_contents(
                  $base_url.'/api/v2/tags/'.$tag.'/items?' .
                  http_build_query($query)
            );
            // https://qiita.com/api/v2/tags/PHP/items?page=1&per_page=5

      // 結果はjson形式で返されるので
      $result = json_decode($response,true);

      return $result;

    }

    function getCarouselMessage() {
      $columns = []; // カルーセル型カラムを5つ追加する配列
      foreach ($lists as $list) {
          // カルーセルに付与するボタンを作る
          $action = new UriTemplateActionBuilder("クリックしてね", "http://www.backlog.jp/git-guide/stepup/stepup2_3.html" );
          // カルーセルのカラムを作成する
          $column = new CarouselColumnTemplateBuilder("タイトル(40文字以内)", "追加文", "https://ja.wikipedia.org/wiki/%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB:Shoyu_ramen,_at_Kasukabe_Station_(2014.05.05)_2.jpg", [$action]);
          $columns[] = $column;
      }
      // カラムの配列を組み合わせてカルーセルを作成する
      $carousel = new CarouselTemplateBuilder($columns);
      // カルーセルを追加してメッセージを作る
      $carousel_message = new TemplateMessageBuilder("メッセージのタイトル", $carousel);
      return $carousel_message;
    }

    function getConfirm() {
      // 「はい」ボタン
      $yes_post = new PostbackTemplateActionBuilder("はい", "page=-1");
      // 「いいえ」ボタン
      $no_post = new PostbackTemplateActionBuilder("いいえ", "page=-1");
      // Confirmテンプレートを作る
      $confirm = new ConfirmTemplateBuilder("メッセージ", [$yes_post, $no_post]);
      // Confirmメッセージを作る
      $confirm_message = new TemplateMessageBuilder("メッセージのタイトル", $confirm);
    }

}
