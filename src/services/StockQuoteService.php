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
            // update current values or fallback to stale cache
            if ($refresh = $this->fetchQuote($symbol))
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
        if (sizeof($data) < 2) 
        {
            Craft::getLogger()->log('StockQuote Error: Invalid or incomplete stock data.', LogLevel::ERROR, true);
            return false;
        }

        // meta
        $metaData = $data['Meta Data'];
        $lastRefresh = $metaData['3. Last Refreshed'];
        $timezone = $metaData['5. Time Zone'];

        // prices
        $timeSeries = $data['Time Series (Daily)'];
        $last = array_shift($timeSeries);
        $lastDate = new DateTime($lastRefresh);
        $lastDate->sub(new DateInterval('P1D'));
        $prevDate = $lastDate->format('Y-m-d');
        
        foreach ($timeSeries as $date => $day) 
        {
            if ($date == $prevDate) {
                $prev = $day;
                break;
            }
        }

        // assemble values and calculate change
        $open      = round($last['1. open'], 2);
        $high      = round($last['2. high'], 2);
        $low       = round($last['3. low'], 2);
        $lastClose = round($last['4. close'], 2);
        $prevClose = round($prev['4. close'], 2);
        $change    = $lastClose - $prevClose;
        $percent   = round(($change / $prevClose) * 100, 2);

        // populate data model
        $quote = new StockQuoteModel();
        $quote->symbol   = $symbol;
        $quote->timezone = $timezone;
        $quote->last     = number_format($lastClose, 2);
        $quote->date     = $lastRefresh;
        $quote->change   = number_format($change, 2);
        $quote->open     = number_format($open, 2);
        $quote->high     = number_format($high, 2);
        $quote->low      = number_format($low, 2);
        $quote->volume   = $last['5. volume'];
        $quote->previous = number_format($prevClose, 2);
        $quote->percent  = $percent;

        return $quote;
    }

    /**
     * Fetch the quote from Alpha Vantage parse results.
     *
     * @see https://www.alphavantage.co/documentation/#daily
     */
    public function fetchQuote($symbol)
    {   
        $plugin = StockQuote::$plugin;
        $apiKey = $plugin->getSettings()->apiKey;

        if(empty($apiKey))
        {
            Craft::getLogger()->log('StockQuote Error: API Key setting missing.', LogLevel::ERROR, true);
            return false;
        } 

        $url = sprintf("https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=%s&apikey=%s", $symbol, $apiKey);

        if($response = $this->request($url))
        {
            $data = json_decode($response, true);

            if(isset($data['Error Message']))
            {
                Craft::getLogger()->log('StockQuote Error: '.$data['Error Message'], LogLevel::ERROR, true);
                return false;
            }

            if (sizeof($data) < 2) {
                Craft::getLogger()->log('StockQuote Error: Invalid or incomplete data.', LogLevel::ERROR, true);
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
