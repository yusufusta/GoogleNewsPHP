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
            'hl' => strtolower($language) . '-' . strtoupper($location),
            'gl' => $location,
            'ceid' =>  strtolower($location) . ':' . strtoupper($language)
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
            if (count($News) >= $this->Max) break;
            $Data = [
                'title' => $Item->first('title')->text(),
                'url' => $Item->first('link')->text(),
                'time' => strtotime($Item->first('pubDate')->text()),
                'description' => $Item->first('description')->text(),
                'source_name' => $Item->first('source')->text(),
                'source_url' => $Item->first('source')->url,
                'tags' => []
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
                            $Data['image_url'] = $Content;
                        } else if ($Property == 'og:description') {
                            $Data['description'] = $Content;
                        } else if ($Property == 'og:url') {
                            $Data['url'] = $Content;
                        } else if ($Property == 'og:title') {
                            $Data['title'] = $Content;
                        } else if ($Property == 'description') {
                            $Data['description'] = $Content;
                        } else if ($Property == 'article:published_time') {
                            $Data['time'] = strtotime($Content);
                        } else if ($Property == 'article:tag') {
                            $Data['tags'][] = $Content;
                        }
                    }
                }     
            }

            $News[] = $Data;
        }
        
        return $News;
    }
}
