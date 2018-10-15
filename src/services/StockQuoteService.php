<?php
/**
 * StockQuote plugin for Craft CMS 3.x
 *
 * 
Simple real-time stock quotes from the Alpha Vantage API.
 *
 * @link      http://www.jesseknowles.com
 * @copyright Copyright (c) 2018 Jesse Knowles
 */

namespace kinoli\stockquote\services;

use kinoli\stockquote\StockQuote;
use kinoli\stockquote\models\StockQuoteModel;

use Craft;
use craft\base\Component;
use Psr\Log\LogLevel;
use \Datetime;
use \DateInterval;

/**
 * @author    Jesse Knowles
 * @package   StockQuote
 * @since     1.0.0
 */
class StockQuoteService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get the quote data from cache or refresh.
     */
    public function getQuote($symbol, $expire)
    {
        // check for symbol
        if(empty($symbol))
        {
            return false;
        }

        // check cache or refresh data
        if(Craft::$app->cache->get($symbol)) 
        {
            $data = Craft::$app->cache->get($symbol);
        }
        else
        {
            $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=%s&apikey=%s";
            // update current values or fallback to stale cache
            if ($refresh = $this->fetchQuote($symbol, $url))
            {
                $data = $refresh;
                Craft::$app->cache->set($symbol, $data, $expire);
                Craft::$app->cache->set($symbol.':stale', $data, 0);
            }
            else
            {
                $data = Craft::$app->cache->get($symbol.':stale');
            }
        }

        // be sure we have valid data
        if (!$data) 
        {
            Craft::getLogger()->log('StockQuote Error: Invalid or incomplete stock data.', LogLevel::ERROR, true);
            return false;
        }

        // prices
        $last = $data['Global Quote'];

        // assemble values and calculate change
        $open      = round($last['02. open'], 2);
        $high      = round($last['03. high'], 2);
        $low       = round($last['04. low'], 2);
        $lastClose = round($last['08. previous close'], 2);
        $change = round($last['09. change'], 2);
        $percent = round($last['10. change percent'], 2);

        $lastDate = $last['07. latest trading day'];

        // populate data model
        $quote = new StockQuoteModel();
        $quote->symbol   = $symbol;
        $quote->last     = number_format($lastClose, 2);
        $quote->date     = $lastDate;
        $quote->change   = number_format($change, 2);
        $quote->open     = number_format($open, 2);
        $quote->high     = number_format($high, 2);
        $quote->low      = number_format($low, 2);
        $quote->volume   = number_format($last['06. volume'], 0);
        $quote->percent  = $percent;

        return $quote;
    }

    /**
     * Get the historical quote data from cache or refresh.
     */
    public function getHistoryJson($symbol, $expire)
    {
        // check for symbol
        if(empty($symbol))
        {
            return false;
        }

        $cacheKey = $symbol . '-hist';

        // check cache or refresh data
        if(Craft::$app->cache->get($cacheKey)) 
        {
            $data = Craft::$app->cache->get($cacheKey);
        }
        else
        {
            // update current values or fallback to stale cache
            $url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=%s&outputsize=full&apikey=%s";
            if ($refresh = $this->fetchQuote($symbol, $url))
            {
                $data = $refresh;
                Craft::$app->cache->set($cacheKey, $data, $expire);
                Craft::$app->cache->set($cacheKey.':stale', $data, 0);
            }
            else
            {
                $data = Craft::$app->cache->get($cacheKey.':stale');
            }
        }

        // be sure we have valid data
        if (sizeof($data) < 2) 
        {
            Craft::getLogger()->log('StockQuote Error: Invalid or incomplete stock data.', LogLevel::ERROR, true);
            return false;
        }

        // meta
        $metaData = $data['Meta Data'];
        $lastRefresh = $metaData['3. Last Refreshed'];
        // $timezone = $metaData['5. Time Zone'];

        // prices
        $timeSeries = $data['Time Series (Daily)'];

        return json_encode($timeSeries);
    }

    /**
     * Fetch the quote from Alpha Vantage parse results.
     *
     * @see https://www.alphavantage.co/documentation/#daily
     */
    public function fetchQuote($symbol, $url)
    {   
        $plugin = StockQuote::$plugin;
        $apiKey = $plugin->getSettings()->apiKey;

        if(empty($apiKey))
        {
            Craft::getLogger()->log('StockQuote Error: API Key setting missing.', LogLevel::ERROR, true);
            return false;
        } 
        $url = sprintf($url, $symbol, $apiKey);

        if($response = $this->request($url))
        {
            $data = json_decode($response, true);

            if(isset($data['Error Message']))
            {
                Craft::getLogger()->log('StockQuote Error: '.$data['Error Message'], LogLevel::ERROR, true);
                return false;
            }

            Craft::getLogger()->log('StockQuote: Successful request to Alpha Vantage API.', LogLevel::INFO); // only logged in devMode
            return $data;
        }

        Craft::getLogger()->log('StockQuote Error: Unable to connect to Alpha Vantage API.', LogLevel::ERROR, true);
        return false;
    }

    /**
     * Use Craft's included Guzzle vendor package for the request.
     */
    private function request($url) {
        try
        {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', $url);

            return $res->getBody(true);
        } 
        catch(\Exception $e)
        {
            Craft::getLogger()->log($e->getResponse(), LogLevel::ERROR, true);
            $response = $e->getResponse();

            return $response;
        }
    }
}
