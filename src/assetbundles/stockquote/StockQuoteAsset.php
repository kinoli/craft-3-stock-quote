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

namespace kinoli\stockquote\assetbundles\StockQuote;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Jesse Knowles
 * @package   StockQuote
 * @since     1.0.0
 */
class StockQuoteAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@kinoli/stockquote/assetbundles/stockquote/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/StockQuote.js',
        ];

        $this->css = [
            'css/StockQuote.css',
        ];

        parent::init();
    }
}
