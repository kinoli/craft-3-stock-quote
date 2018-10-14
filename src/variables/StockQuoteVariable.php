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

namespace kinoli\stockquote\variables;

use kinoli\stockquote\StockQuote;

use Craft;

/**
 * @author    Jesse Knowles
 * @package   StockQuote
 * @since     1.0.0
 */
class StockQuoteVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Fetch a single quote.
     *
     * {{ craft.stockQuote.getQuote('GOOG') }}
     */
    public function getQuote($symbol = null, $expire = 1200)
    {
        return StockQuote::$plugin->stockQuoteService->getQuote($symbol, $expire);
    }
}
