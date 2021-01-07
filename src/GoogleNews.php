<?php
namespace Quiec;
use GuzzleHttp\Client;
use DiDom\Document;

class GoogleNews {
    public $rssTopNewsUrl = "https://news.google.com/news/rss/";
    public $rssTopicUrl = "/headlines/section/topic/";
    public $langConfig = [];
    public $Client;
    public $openGraph = false;

    public function __construct($language = 'en', $location = 'US', $topic = 'NATION', $max = 100, $openGraph = False) {
        $this->rssTopicUrl = $this->rssTopicUrl . $topic;
        $this->langConfig = [
            'hl' => $language,
            'gl' => $location,
            'ceid' => strtoupper($location) . ':' . strtolower($language)
        ];
        $this->Client = new Client([
            'headers' => [
                'User-Agent' => \Campo\UserAgent::random()
            ],
            'verify' => false
        ]);
        $this->openGraph = $openGraph;
        $this->Max = $max;
    }

    public function getNews() {
        $News = $this->Client->get($this->rssTopNewsUrl . $this->rssTopicUrl, [
            'query' => $this->langConfig + ['num' => $this->Max]
        ]);
        return $this->parseNews($News->getBody()->getContents());
    }

    public function parseNews($Content) {
        $Document = new Document();
        $Document->loadXml($Content);
        $News = [];
        
        $Items = $Document
            ->first('rss')
            ->first('channel')
            ->find('item');
        foreach ($Items as $Item) {
            $Data = [
                'title' => $Item->first('title')->text(),
                'link' => $Item->first('link')->text(),
                'time' => strtotime($Item->first('pubDate')->text()),
                'description' => $Item->first('description')->text(),
                'source' => [
                    'name' => $Item->first('source')->text(),
                    'url' => $Item->first('source')->url
                ]
            ];

            if ($this->openGraph) {
                $Haber = $this->Client->get($Item->first('link')->text());  
                $Haber_Document = new Document();
                $Haber_Document->loadHtml($Haber->getBody()->getContents());
                $Metas = $Haber_Document->find('meta');
    
                foreach ($Metas as $Meta) {
                    if ($Meta->hasAttribute('property')) {
                        $Property = $Meta->getAttribute('property');
                        $Content = $Meta->getAttribute('content');
    
                        if ($Property == 'og:image') {
                            $Data['image']['url'] = $Content;
                        } else if ($Property == 'og:description') {
                            $Data['description'] = $Content;
                        } else if ($Property == 'og:url') {
                            $Data['link'] = $Content;
                        } else if ($Property == 'og:title') {
                            $Data['title'] = $Content;
                        }
                    }
                }     
            }

            $News[] = $Data;
        }
        
        return $News;
    }
}