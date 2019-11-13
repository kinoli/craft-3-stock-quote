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

namespace kinoli\stockquote\models;

use kinoli\stockquote\StockQuote;

use Craft;
use craft\base\Model;

/**
 * @author    Jesse Knowles
 * @package   StockQuote
 * @since     1.0.0
 */
class StockQuoteModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $apiKey = '';
    public $symbol = '';
    public $timezone = '';
    public $last = '';
    public $date = '';
    public $change = '';
    public $open = '';
    public $high = '';
    public $low = '';
    public $volume = '';
    public $previous = '';
    public $percent = '';
    public $price = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['symbol', 'string'],
            ['timezone', 'string'],
            ['last', 'string'],
            ['date', 'string'],
            ['change', 'string'],
            ['open', 'string'],
            ['high', 'string'],
            ['low', 'string'],
            ['volume', 'string'],
            ['previous', 'string'],
            ['percent', 'string'],
            ['price', 'string'],
        ];
    }
}
