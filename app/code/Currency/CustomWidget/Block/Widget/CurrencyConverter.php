<?php 
namespace Currency\CustomWidget\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface; 

class CurrencyConverter extends Template implements BlockInterface {
    protected $_template = "widget/CurrencyConverter.phtml";
}