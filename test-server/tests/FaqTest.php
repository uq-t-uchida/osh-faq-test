<?php

use PHPUnit\Framework\TestCase;
use Goutte\Client;

class FaqTest extends TestCase
{

    private $baseUrl = 'https://m1.staging.osohshiki.jp';
    // private $baseUrl = 'http://local.osohshiki-stg.jp/';

    public function setup() :void
    {
        parent::setup();
        $this->client = new Client();
    }

    /**
     * @dataProvider targetUrls
     */
    public function test($url)
    {   
        $pcUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.74 Safari/537.36 Edg/79.0.309.43';
        $spUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1';

        $crawler = [];

        // PCページ取得
        $this->client->setServerParameter('HTTP_USER_AGENT', $pcUserAgent);
        $crawler['PC'] = $this->client->request('GET', $this->baseUrl. $url);

        // SPページ取得
        $this->client->setServerParameter('HTTP_USER_AGENT', $spUserAgent);
        $crawler['SP'] = $this->client->request('GET', $this->baseUrl. $url);

        // PCでのアクセス ===========================================================================

        // PCページからあらかじめ判定のための情報を取得しておく
        $accessMeta = $this->accessMeta($crawler['PC']);
        $panoramaMeta = $this->panoramaMeta($crawler['PC']);
        $feeMeta = $this->feeMeta($crawler['PC']);
        $parkingMeta = $this->parkingMeta($crawler['PC']);
        $stayMeta = $this->stayMeta($crawler['PC']);
        $imageMeta = $this->imageMeta($crawler['PC']);
        $reviewMeta = $this->reviewMeta($crawler['PC']);
        $visitMeta = $this->visitMeta($crawler['PC']);
        $barrierFreeMeta = $this->barrierFreeMeta($crawler['PC']);
        $diningMeta = $this->diningMeta($crawler['PC']);

        
        // 施設名取得
        $facilityName = $this->flattenText($crawler['PC']->filter('h2.sougijou-name')->first()->text(null));
        $this->assertFalse($facilityName === null);

        foreach (['PC','SP'] as $device) {
            // アクセス情報 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('アクセス情報を教えてください。', $device, $crawler[$device]);
            if ($accessMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    "{$facilityName}のアクセスは以下になります。". $accessMeta['text'],
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // パノラマビュー ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('バーチャル見学は可能でしょうか。', $device, $crawler[$device]);
            if ($panoramaMeta['show']) {
                // パノラマビューあり   
                $this->assertSame(
                    "{$facilityName}の施設を360°パノラマビューでバーチャル見学することができます。",
                    $answer['text']
                );
            } else {
                // パノラマビューなし
                $this->assertNull($answer);
            }

            // 利用料金 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('利用料金はいくらでしょうか。', $device, $crawler[$device]);
            if ($feeMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    "{$facilityName}は葬儀料金119,000円からご利用いただけます。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // 駐車場 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('駐車場はありますか？また、その他の施設情報を教えてください。', $device, $crawler[$device]);
            if ($parkingMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    $parkingMeta['has']
                        ? "{$facilityName}には駐車場がございます。"
                        : "{$facilityName}には駐車場がございません。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }
            
            // 宿泊 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('宿泊できますか？また、その他サービスについて教えてください。', $device, $crawler[$device]);
            if ($stayMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    $stayMeta['has']
                        ? "{$facilityName}は宿泊可能です。その他、サービス詳細は下記よりご確認ください。"
                        : "{$facilityName}は宿泊できません。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // 写真 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('施設に関する写真を確認できますか。', $device, $crawler[$device]);
            if ($imageMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    "{$facilityName}の写真を下記よりご覧いただけます。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // 口コミ ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('ご利用されたお客様の口コミを教えてください。', $device, $crawler[$device]);
            if ($reviewMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    "{$facilityName}をご利用されたお客様の口コミ平均スコアは{$reviewMeta['rate']}点です。以下、実際にご利用されたお客様のレビューになります。「{$reviewMeta['firstReview']}」",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // 面会 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('ご安置中の面会は可能でしょうか。', $device, $crawler[$device]);
            if ($visitMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    $visitMeta['has']
                        ? "はい、{$facilityName}ではご安置中でも面会が可能です。"
                        : "いいえ、{$facilityName}ではご安置中での面会は行えません。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // バリアフリー ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('バリアフリー対応していますか。', $device, $crawler[$device]);
            if ($barrierFreeMeta['show']) {
                // 対応あり   
                $this->assertSame(
                    $barrierFreeMeta['has']
                        ? "はい、{$facilityName}はバリアフリー対応しております。"
                        : "いいえ、{$facilityName}はバリアフリー対応しておりません。",
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }

            // 会食室 ////////////////////////////////////////////////////////////////////
            $answer = $this->getAnswerByTitle('会食室はありますか。', $device, $crawler[$device]);
            if ($diningMeta['show']) {
                // 対応あり   
                $test = $diningMeta['has']
                            ? "はい、{$facilityName}には会食室がございます。"
                            : "いいえ、{$facilityName}には会食室はございません。";
                if ($diningMeta['max']) {
                    $test .= "着席で{$diningMeta['max']}人まで収容可能です。";
                }
                $this->assertSame(
                    $test,
                    $answer['text']
                );
            } else {
                // 対応なし
                $this->assertNull($answer);
            }
        }                
    }

    /**
     * アクセス
     */
    private function accessMeta($crawler)
    {
        $element = $crawler
                    ->filter('section#sougijou-detail dl.access ul')
                    ->first();
        return [
            'show' => $element->count() ? true : false,
            'text' => $element->count()
                        ? $this->flattenText($element->text(''))
                        : null,
        ];
    }

    /**
     * パノラマビュー
     */
    private function panoramaMeta($crawler) 
    {
        return [
            'show' => $crawler
                        ->filter('#panorama_view')
                        ->count() ? true : false
        ];
    }

    /**
     * 料金
     */
    private function feeMeta($crawler)
    {
        return [
            'show' => true,
        ];
    }

    /**
     * 駐車場
     */
    private function parkingMeta($crawler)
    {
        $element = $crawler
                    ->filter('#sougijou-detail .access-item dt')
                    ->reduce(function($node){
                        return trim($node->text()) === '駐車場';
                    })
                    ->first()
                    ->siblings('dd')
                    ->first();
        $text = $this->getTextNodeValue($element);
        $res = [
            'show' => true,
        ];
        if (preg_match('/あり/u', $text, $matched)) {
            $res['has'] = true;
        } else {
            $res['has'] = false;
        }
        return $res;
    }

    /**
     * 宿泊
     */
    private function stayMeta($crawler)
    {
        $element = $crawler
                        ->filter('#sougijou-table th')
                        ->reduce(function($node){
                            return trim($this->getTextNodeValue($node)) === '宿泊';
                        })
                        ->first()
                        ->nextAll('td')
                        ->first()
                        ->text();
        return [
            'show' => true,
            'has' => $this->flattenText($element) === '〇'
                        ? true
                        : false,
        ];
    }

    /**
     * 画像
     */
    private function imageMeta($crawler)
    {
        return [
            'show' => $crawler
                        ->filter('.sougijou-slider li')
                        ->reduce(function($node){
                            return !empty($node->attr('data-alt'));
                        })
                        ->count() ? true : false,
        ]; 
    }

    /**
     * レビュー
     */
    private function reviewMeta($crawler)
    {
        $has = $crawler->filter('.review-modal-content .review-text')->count() > 0;
        return [
            'show' => $has,
            'rate' => $has 
                        ? $crawler->filter('#sougijou-header .sougijou-rating-value')->first()->text()
                        : null, 
            'firstReview' => $has
                                ? $this->flattenText($crawler->filter('.review-modal-content .review-text')->first()->text())
                                : null,
        ];
    }

    /**
     * 面会
     */
    private function visitMeta($crawler)
    {
        return [
            'show' => true,
            'has' => $crawler
                        ->filter('#sougijou-table th')
                        ->reduce(function($node){
                            return trim($this->getTextNodeValue($node)) === 'ご安置中の面会';
                        })
                        ->first()
                        ->nextAll('td')
                        ->first()
                        ->text() === '〇',
        ];
    }

    /**
     * バリアフリー
     */
    private function barrierFreeMeta ($crawler)
    {
        return [
            'show' => true,
            'has' => $crawler
                        ->filter('#sougijou-table th')
                        ->reduce(function($node){
                            return trim($this->getTextNodeValue($node)) === 'バリアフリー';
                        })
                        ->first()
                        ->nextAll('td')
                        ->first()
                        ->text() === '〇',
        ];
    }

    /**
     * 会食
     */
    private function diningMeta($crawler)
    {
        $text = $crawler
                    ->filter('#sougijou-table th')
                    ->reduce(function($node){
                        return trim($this->getTextNodeValue($node)) === '会食室';
                    })
                    ->first()
                    ->nextAll('td')
                    ->first()
                    ->text();
        $res = [
            'show' => true,
        ];
        if (preg_match('/〇(（着席(\d+)人）)?/u', $text, $matched)) {
            $res['has'] = true;
            if (!empty($matched[2])) {
                $res['max'] = $matched[2];
            }
        } else {
            $res['has'] = false;
        }
        return $res;
    }
  
    /**
     * 回答要素取得
     */
    private function getAnswerByTitle($title, $device, $crawler) 
    {
        if ($device === 'PC') {
            return $this->getAnswerByTitlePC($title, $crawler);
        } else if ($device === 'SP') {
            return $this->getAnswerByTitleSP($title, $crawler);
        }
        return null;
    }

    /**
     * PCの回答要素取得
     */
    private function getAnswerByTitlePC($title, $crawler)
    {
        $resultElement = null;
        $el = $crawler->filter('section.faq .question_title')->each(function($node, $idx) use ($crawler, $title, &$resultElement) {
            if (preg_match("/{$title}$/", $node->text())) {
                // 該当するタイトルを検知
                $resultElement = $crawler->filter('section.faq .answer')->eq($idx);
            }
        });
        if ($resultElement === null) return null;

        return [
            'element' => $resultElement,
            'text' => $this->getTextNodeValue($resultElement->filter('.txt')),
            'link' => $resultElement->filter('.anchor_link')->count()
                        ? [
                            'href' => $resultElement->filter('.anchor_link')->first()->attr('href'),
                            'text' => $this->flattenText($resultElement->filter('.anchor_link')->first()->text('')),
                        ]
                        : null
        ];
    }

    /**
     * SPの回答要素取得
     */
    private function getAnswerByTitleSP($title, $crawler)
    {
        $resultElement = null;
        $crawler->filter('section.s-faq .accordion-header')->each(function($node, $idx) use ($title, &$resultElement) {
            if (preg_match("/{$title}$/", $node->text())) {
                // 該当するタイトルを検知
                $resultElement = $node->siblings('.accordion-content')->first()->filter('.c-icon-a div')->first();
            }
        });
        if ($resultElement === null) return null;

        return [
            'element' => $resultElement,
            'text' => $this->getTextNodeValue($resultElement),
            'link' => $resultElement->filter('.anchor_link')->count()
                        ? [
                            'href' => $resultElement->filter('.anchor_link')->first()->attr('href'),
                            'text' => $this->flattenText($resultElement->filter('.anchor_link')->first()->text('')),
                        ]
                        : null
        ];
    }

    private function getFaqJson($crawler) {
        $element = $crawler->filter('script[type="application/ld+json"]')->reduce(function($elm){
            $json = json_decode($this->flattenText($elm->text()));
            return $json->{'@type'} === 'FAQPage';
        });
        return json_decode(html_entity_decode($element->text()), true);
    }

    /**
     * 要素直下のテキストノードを連結して返す
     */
    private function getTextNodeValue($element) {
        $texts = [];
        foreach ($element->getNode(0)->childNodes as $node) {
            if ($node->nodeType != XML_TEXT_NODE) {
                continue;
            }
            $texts[] = $node->nodeValue;
        }
        return $this->flattenText(implode($texts));
    }

    /**
     * テキストのホワイトスペース、改行を除く
     */
    private function flattenText($text) {
        $text = preg_replace('/[\s\r\n]*/', '', $text);
        return $text;
    }

    /**
     * テストデータ
     */
    public function targetUrls ()
    {
        $urls = [
            // パノラマあり
            '/area/osaka/habikino-shi/sougijou-31.html',
            '/area/tokyo/taito-ku/sougijou-222.html',
            '/area/kanagawa/yokohama-shi/naka-ku/sougijou-30402.html',
            '/area/nagano/saku-shi/sougijou-34045.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-27419.html',

            // パノラマなし
            '/area/osaka/izumi-shi/sougijou-32858.html',
            '/area/nara/kashihara-shi/sougijou-26117.html',
            '/area/nara/nara-shi/sougijou-29256.html',
            '/area/kagoshima/kagoshima-shi/sougijou-29235.html',
            '/area/nagasaki/sasebo-shi/sougijou-29226.html',

            // 駐車場あり
            '/area/osaka/osaka-shi/tsurumi-ku/sougijou-6.html',
            '/area/saitama/fujimino-shi/sougijou-30412.html',
            '/area/aichi/yatomi-shi/sougijou-27142.html',
            '/area/nagasaki/minamishimabara-shi/sougijou-28134.html',
            '/area/okinawa/nanjo-shi/sougijou-34393.html',

            // 駐車場なし
            '/area/osaka/osaka-shi/asahi-ku/sougijou-27137.html',
            '/area/tokyo/setagaya-ku/sougijou-204.html',
            '/area/miyagi/sendai-shi/taihaku-ku/sougijou-27936.html',
            '/area/saga/karatsu-shi/sougijou-31190.html',
            '/area/oita/nakatsu-shi/sougijou-27130.html',

            // 宿泊あり
            '/area/osaka/osaka-shi/abeno-ku/sougijou-3.html',
            '/area/hokkaido/iwamizawa-shi/sougijou-28776.html',
            '/area/tochigi/oyama-shi/sougijou-26769.html',
            '/area/aichi/takahama-shi/sougijou-34444.html',
            '/area/hiroshima/kure-shi/sougijou-28753.html',

            // 宿泊なし
            '/area/osaka/kishiwada-shi/sougijou-27159.html',
            '/area/tokyo/chuo-ku/sougijou-27869.html',
            '/area/saitama/warabi-shi/sougijou-637.html',
            '/area/ibaraki/naka-shi/sougijou-26488.html',
            '/area/okinawa/ogimi-son/sougijou-29098.html',

            // 写真あり
            '/area/osaka/osaka-shi/sumiyoshi-ku/sougijou-29523.html',

            // 写真なし
            '/area/osaka/osaka-shi/chuo-ku/sougijou-27374.html',
            '/area/kanagawa/yokohama-shi/isogo-ku/sougijou-33309.html',
            '/area/yamagata/yamagata-shi/sougijou-34509.html',
            '/area/okinawa/nishihara-cho/sougijou-33542.html',

            // 口コミあり
            '/area/osaka/osaka-shi/tennoji-ku/sougijou-27376.html',
            '/area/osaka/osaka-shi/chuo-ku/sougijou-27374.html',
            '/area/wakayama/shingu-shi/sougijou-28531.html',
            '/area/wakayama/nachikatsura-cho/sougijou-28530.html',
            '/area/iwate/oshu-shi/sougijou-27953.html',

            // 口コミなし
            '/area/iwate/oshu-shi/sougijou-29205.html',
            '/area/niigata/shibata-shi/sougijou-28544.html',
            '/area/tottori/chizu-cho/sougijou-29005.html',
            '/area/kochi/susaki-shi/sougijou-34336.html',
            '/area/kagawa/sanuki-shi/sougijou-26478.html',
            
            // 面会あり
            '/area/osaka/osaka-shi/taisho-ku/sougijou-5.html',
            '/area/osaka/toyonaka-shi/sougijou-27463.html',
            '/area/tochigi/ashikaga-shi/sougijou-31804.html',
            '/area/okayama/akaiwa-shi/sougijou-25838.html',
            '/area/okinawa/nanjo-shi/sougijou-34393.html',

            // 面会なし
            '/area/osaka/osaka-shi/nishiyodogawa-ku/sougijou-4.html',
            '/area/hyogo/kobe-shi/nada-ku/sougijou-34428.html',
            '/area/tokyo/taito-ku/sougijou-95.html',
            '/area/miyagi/sendai-shi/miyagino-ku/sougijou-31527.html',
            '/area/oita/nakatsu-shi/sougijou-33325.html',

            // バリアフリー
            '/area/osaka/osaka-shi/abeno-ku/sougijou-3.html',
            '/area/saitama/misato-machi/sougijou-34038.html',
            '/area/aichi/toyota-shi/sougijou-26312.html',
            '/area/nagasaki/omura-shi/sougijou-27562.html',
            '/area/okinawa/nanjo-shi/sougijou-34393.html',

            // バリアフリーなし
            '/area/osaka/osaka-shi/nishiyodogawa-ku/sougijou-4.html',
            '/area/chiba/matsudo-shi/sougijou-29963.html',
            '/area/mie/kameyama-shi/sougijou-25945.html',
            '/area/okinawa/okinawa-shi/sougijou-33234.html',

            // 会食あり
            '/area/osaka/osaka-shi/tsurumi-ku/sougijou-6.html',
            '/area/osaka/kawachinagano-shi/sougijou-28123.html',
            '/area/niigata/niigata-shi/higashi-ku/sougijou-27681.html',
            '/area/aichi/nagoya-shi/moriyama-ku/sougijou-26506.html',
            '/area/kochi/kochi-shi/sougijou-34327.html',

            // 会食なし
            '/area/osaka/kawachinagano-shi/sougijou-27868.html',
            '/area/saitama/kawaguchi-shi/sougijou-611.html',
            '/area/tochigi/utsunomiya-shi/sougijou-30443.html',
            '/area/fukuoka/kurate-machi/sougijou-26356.html',
            '/area/saga/imari-shi/sougijou-27292.html',

            // ランダム
            '/area/kanagawa/yokohama-shi/midori-ku/sougijou-525.html',
            '/area/osaka/sennan-shi/sougijou-28307.html',
            '/area/gumma/isesaki-shi/sougijou-26057.html',
            '/area/yamagata/oguni-machi/sougijou-27663.html',
            '/area/tochigi/utsunomiya-shi/sougijou-26756.html',
            '/area/chiba/noda-shi/sougijou-664.html',
            '/area/kanagawa/kawasaki-shi/tama-ku/sougijou-31698.html',
            '/area/fukuoka/asakura-shi/sougijou-29952.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-28104.html',
            '/area/chiba/sakura-shi/sougijou-26650.html',
            '/area/tokyo/hachioji-shi/sougijou-28289.html',
            '/area/kumamoto/kikuchi-shi/sougijou-26886.html',
            '/area/gumma/fujioka-shi/sougijou-31913.html',
            '/area/nara/yamatokoriyama-shi/sougijou-28213.html',
            '/area/tokyo/nerima-ku/sougijou-331.html',
            '/area/fukushima/fukushima-shi/sougijou-26787.html',
            '/area/ibaraki/kamisu-shi/sougijou-29906.html',
            '/area/osaka/suita-shi/sougijou-34179.html',
            '/area/kanagawa/kawasaki-shi/takatsu-ku/sougijou-33420.html',
            '/area/tochigi/utsunomiya-shi/sougijou-26008.html',
            '/area/nagano/tatsuno-machi/sougijou-27271.html',
            '/area/aichi/toyohashi-shi/sougijou-28209.html',
            '/area/chiba/sakura-shi/sougijou-34527.html',
            '/area/tochigi/utsunomiya-shi/sougijou-33618.html',
            '/area/tokyo/machida-shi/sougijou-33505.html',
            '/area/fukuoka/asakura-shi/sougijou-29953.html',
            '/area/wakayama/wakayama-shi/sougijou-29337.html',
            '/area/hokkaido/asahikawa-shi/sougijou-33513.html',
            '/area/aichi/toyohashi-shi/sougijou-26090.html',
            '/area/fukuoka/mizumaki-machi/sougijou-31504.html',
            '/area/shizuoka/fuji-shi/sougijou-34183.html',
            '/area/gumma/maebashi-shi/sougijou-27561.html',
            '/area/osaka/higashiosaka-shi/sougijou-32912.html',
            '/area/tochigi/yaita-shi/sougijou-26764.html',
            '/area/shizuoka/fukuroi-shi/sougijou-29132.html',
            '/area/aichi/nagoya-shi/mizuho-ku/sougijou-28620.html',
            '/area/tokyo/taito-ku/sougijou-31551.html',
            '/area/hokkaido/sapporo-shi/minami-ku/sougijou-29311.html',
            '/area/saitama/tokorozawa-shi/sougijou-34381.html',
            '/area/hokkaido/sapporo-shi/nishi-ku/sougijou-29569.html',
            '/area/hyogo/kobe-shi/suma-ku/sougijou-33537.html',
            '/area/aichi/gamagori-shi/sougijou-29258.html',
            '/area/yamagata/sakata-shi/sougijou-27865.html',
            '/area/hokkaido/sapporo-shi/atsubetsu-ku/sougijou-31933.html',
            '/area/tokyo/ota-ku/sougijou-238.html',
            '/area/iwate/oshu-shi/sougijou-32144.html',
            '/area/gifu/gifu-shi/sougijou-27332.html',
            '/area/tokyo/nishitokyo-shi/sougijou-362.html',
            '/area/tokyo/suginami-ku/sougijou-191.html',
            '/area/aichi/ichinomiya-shi/sougijou-32856.html',
            '/area/nagasaki/unzen-shi/sougijou-26360.html',
            '/area/gumma/tomioka-shi/sougijou-32854.html',
            '/area/chiba/asahi-shi/sougijou-31511.html',
            '/area/tokyo/nakano-ku/sougijou-266.html',
            '/area/gifu/takayama-shi/sougijou-28943.html',
            '/area/aichi/nagoya-shi/minato-ku/sougijou-28909.html',
            '/area/fukushima/nihommatsu-shi/sougijou-26785.html',
            '/area/tokyo/edogawa-ku/sougijou-27917.html',
            '/area/gifu/gifu-shi/sougijou-28937.html',
            '/area/tokyo/minato-ku/sougijou-32466.html',
            '/area/chiba/kashiwa-shi/sougijou-663.html',
            '/area/osaka/higashiosaka-shi/sougijou-28811.html',
            '/area/fukushima/sukagawa-shi/sougijou-26779.html',
            '/area/miyazaki/miyakonojo-shi/sougijou-27591.html',
            '/area/gifu/sekigahara-cho/sougijou-29099.html',
            '/area/saitama/kawaguchi-shi/sougijou-26714.html',
            '/area/okayama/okayama-shi/kita-ku/sougijou-28740.html',
            '/area/osaka/osaka-shi/chuo-ku/sougijou-27373.html',
            '/area/saitama/hidaka-shi/sougijou-34141.html',
            '/area/niigata/niigata-shi/chuo-ku/sougijou-31651.html',
            '/area/tochigi/utsunomiya-shi/sougijou-31506.html',
            '/area/ibaraki/kasumigaura-shi/sougijou-30439.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-27929.html',
            '/area/tokushima/tokushima-shi/sougijou-25926.html',
            '/area/shiga/koka-shi/sougijou-26332.html',
            '/area/gumma/ota-shi/sougijou-26058.html',
            '/area/kagawa/tadotsu-cho/sougijou-33434.html',
            '/area/tokyo/ota-ku/sougijou-244.html',
            '/area/tokyo/akiruno-shi/sougijou-366.html',
            '/area/chiba/kisarazu-shi/sougijou-28032.html',
            '/area/ibaraki/moriya-shi/sougijou-31756.html',
            '/area/tokyo/suginami-ku/sougijou-190.html',
            '/area/saitama/kawagoe-shi/sougijou-29050.html',
            '/area/hyogo/tamba-shi/sougijou-46.html',
            '/area/fukushima/iwaki-shi/sougijou-26796.html',
            '/area/shizuoka/hamamatsu-shi/minami-ku/sougijou-28177.html',
            '/area/osaka/sakai-shi/naka-ku/sougijou-28125.html',
            '/area/kanagawa/zushi-shi/sougijou-34466.html',
            '/area/tokyo/sumida-ku/sougijou-104.html',
            '/area/tokyo/hachioji-shi/sougijou-30122.html',
            '/area/saitama/hidaka-shi/sougijou-630.html',
            '/area/miyazaki/nichinan-shi/sougijou-27599.html',
            '/area/yamaguchi/kudamatsu-shi/sougijou-32418.html',
            '/area/tochigi/utsunomiya-shi/sougijou-30313.html',
            '/area/shizuoka/hamamatsu-shi/hamakita-ku/sougijou-31346.html',
            '/area/ibaraki/tsuchiura-shi/sougijou-26257.html',
            '/area/chiba/matsudo-shi/sougijou-28576.html',
            '/area/aichi/toyota-shi/sougijou-31017.html',
            '/area/tochigi/sano-shi/sougijou-26061.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-26271.html',
            '/area/fukuoka/fukuoka-shi/minami-ku/sougijou-27075.html',
            '/area/tokyo/shinjuku-ku/sougijou-164.html',
            '/area/chiba/matsudo-shi/sougijou-31548.html',
            '/area/kumamoto/kumamoto-shi/chuo-ku/sougijou-27958.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-26274.html',
            '/area/saitama/koshigaya-shi/sougijou-31656.html',
            '/area/gifu/tajimi-shi/sougijou-31787.html',
            '/area/hiroshima/hiroshima-shi/nishi-ku/sougijou-29159.html',
            '/area/shizuoka/shizuoka-shi/aoi-ku/sougijou-30384.html',
            '/area/aichi/anjo-shi/sougijou-34255.html',
            '/area/tokyo/katsushika-ku/sougijou-33103.html',
            '/area/miyagi/tagajo-shi/sougijou-29532.html',
            '/area/nagano/saku-shi/sougijou-32521.html',
            '/area/saitama/konosu-shi/sougijou-33369.html',
            '/area/shizuoka/hamamatsu-shi/naka-ku/sougijou-28633.html',
            '/area/aichi/ichinomiya-shi/sougijou-31767.html',
            '/area/hiroshima/etajima-shi/sougijou-28755.html',
            '/area/osaka/osaka-shi/yodogawa-ku/sougijou-31528.html',
            '/area/shiga/otsu-shi/sougijou-29018.html',
            '/area/saitama/toda-shi/sougijou-26715.html',
            '/area/hokkaido/asahikawa-shi/sougijou-29306.html',
            '/area/tokyo/machida-shi/sougijou-387.html',
            '/area/miyagi/osaki-shi/sougijou-27642.html',
            '/area/shiga/hikone-shi/sougijou-30134.html',
            '/area/chiba/abiko-shi/sougijou-34447.html',
            '/area/nagasaki/saza-cho/sougijou-34411.html',
            '/area/shizuoka/hamamatsu-shi/naka-ku/sougijou-30333.html',
            '/area/saitama/miyoshi-machi/sougijou-26733.html',
            '/area/gifu/gifu-shi/sougijou-30259.html',
            '/area/hokkaido/kitami-shi/sougijou-29026.html',
            '/area/saitama/kawaguchi-shi/sougijou-641.html',
            '/area/fukuoka/kitakyushu-shi/kokurakita-ku/sougijou-28878.html',
            '/area/tokyo/shibuya-ku/sougijou-153.html',
            '/area/kanagawa/isehara-shi/sougijou-27402.html',
            '/area/kanagawa/yokohama-shi/tsurumi-ku/sougijou-34000.html',
            '/area/gifu/toki-shi/sougijou-31746.html',
            '/area/aichi/nagoya-shi/minami-ku/sougijou-28060.html',
            '/area/tokyo/itabashi-ku/sougijou-88.html',
            '/area/kumamoto/kikuchi-shi/sougijou-26883.html',
            '/area/osaka/sakai-shi/higashi-ku/sougijou-29862.html',
            '/area/chiba/noda-shi/sougijou-691.html',
            '/area/aichi/handa-shi/sougijou-26223.html',
            '/area/okinawa/kunigami-son/sougijou-29095.html',
            '/area/miyagi/sendai-shi/taihaku-ku/sougijou-28922.html',
            '/area/tochigi/ashikaga-shi/sougijou-26461.html',
            '/area/tokyo/chofu-shi/sougijou-27545.html',
            '/area/miyazaki/nichinan-shi/sougijou-26551.html',
            '/area/gumma/takasaki-shi/sougijou-27896.html',
            '/area/kagawa/takamatsu-shi/sougijou-29845.html',
            '/area/kumamoto/asagiri-cho/sougijou-29100.html',
            '/area/gumma/midori-shi/sougijou-30465.html',
            '/area/tokyo/fuchu-shi/sougijou-27892.html',
            '/area/ibaraki/tsuchiura-shi/sougijou-29265.html',
            '/area/fukushima/aizubange-machi/sougijou-26794.html',
            '/area/kumamoto/kikuchi-shi/sougijou-26882.html',
            '/area/tokyo/setagaya-ku/sougijou-212.html',
            '/area/nagasaki/shimabara-shi/sougijou-31271.html',
            '/area/hiroshima/hiroshima-shi/asaminami-ku/sougijou-27807.html',
            '/area/tokyo/chofu-shi/sougijou-396.html',
            '/area/osaka/takatsuki-shi/sougijou-33116.html',
            '/area/mie/yokkaichi-shi/sougijou-28911.html',
            '/area/shizuoka/yaizu-shi/sougijou-28632.html',
            '/area/kumamoto/kumamoto-shi/minami-ku/sougijou-34044.html',
            '/area/tokyo/adachi-ku/sougijou-27862.html',
            '/area/hyogo/kobe-shi/kita-ku/sougijou-26035.html',
            '/area/saitama/soka-shi/sougijou-34106.html',
            '/area/gifu/kakamigahara-shi/sougijou-29106.html',
            '/area/fukushima/kitakata-shi/sougijou-26793.html',
            '/area/shiga/otsu-shi/sougijou-760.html',
            '/area/aichi/konan-shi/sougijou-26135.html',
            '/area/yamaguchi/yamaguchi-shi/sougijou-29478.html',
            '/area/nagano/chikuma-shi/sougijou-28690.html',
            '/area/ehime/niihama-shi/sougijou-32585.html',
            '/area/kagawa/zentsuji-shi/sougijou-26487.html',
            '/area/hiroshima/higashihiroshima-shi/sougijou-26484.html',
            '/area/shizuoka/hamamatsu-shi/nishi-ku/sougijou-29148.html',
            '/area/nagano/saku-shi/sougijou-34003.html',
            '/area/shiga/omihachiman-shi/sougijou-26508.html',
            '/area/aichi/tsushima-shi/sougijou-28637.html',
            '/area/fukui/fukui-shi/sougijou-34231.html',
            '/area/ibaraki/mito-shi/sougijou-25892.html',
            '/area/tochigi/sakura-shi/sougijou-26765.html',
            '/area/hokkaido/obihiro-shi/sougijou-33517.html',
            '/area/hokkaido/asahikawa-shi/sougijou-31586.html',
            '/area/yamaguchi/yanai-shi/sougijou-32003.html',
            '/area/fukuoka/omuta-shi/sougijou-28955.html',
            '/area/shizuoka/hamamatsu-shi/naka-ku/sougijou-31721.html',
            '/area/hyogo/ichikawa-cho/sougijou-30257.html',
            '/area/kanagawa/yokohama-shi/kohoku-ku/sougijou-489.html',
            '/area/ibaraki/shimotsuma-shi/sougijou-26399.html',
            '/area/yamaguchi/kudamatsu-shi/sougijou-26382.html',
            '/area/tottori/kurayoshi-shi/sougijou-28206.html',
            '/area/tokyo/itabashi-ku/sougijou-31866.html',
            '/area/kanagawa/yamato-shi/sougijou-33784.html',
            '/area/kagawa/zentsuji-shi/sougijou-32995.html',
            '/area/aichi/tahara-shi/sougijou-29134.html',
            '/area/kanagawa/yokohama-shi/kohoku-ku/sougijou-488.html',
            '/area/kumamoto/ozu-machi/sougijou-27995.html',
            '/area/saga/karatsu-shi/sougijou-27571.html',
            '/area/nara/tawaramoto-cho/sougijou-28889.html',
            '/area/fukuoka/kurate-machi/sougijou-26356.html',
            '/area/yamagata/kawanishi-machi/sougijou-27667.html',
            '/area/osaka/matsubara-shi/sougijou-32908.html',
            '/area/tokyo/suginami-ku/sougijou-194.html',
            '/area/mie/nabari-shi/sougijou-27165.html',
            '/area/osaka/takatsuki-shi/sougijou-34176.html',
            '/area/tokyo/sumida-ku/sougijou-29499.html',
            '/area/osaka/osaka-shi/fukushima-ku/sougijou-30438.html',
            '/area/tokyo/fuchu-shi/sougijou-333.html',
            '/area/ibaraki/kasama-shi/sougijou-27942.html',

            '/area/osaka/osaka-shi/sumiyoshi-ku/sougijou-29523.html',
        ];

        $data = [];
        foreach ($urls as $url) {
            $data[] = [$url];
        }
        return $data;
    }

}