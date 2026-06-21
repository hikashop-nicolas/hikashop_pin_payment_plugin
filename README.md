# HikaShop::PinPayments

[HikaShop](https://www.hikashop.com) plugin to support making and taking
payments via [Pin Payments](https://pinpayments.com).

## Installation

Download the latest release from [releases](https://github.com/hikashop-nicolas/hikashop_pin_payment_plugin/releases),
then install the zip from the Joomla extension manager (Extensions > Install).

## Dependencies

* [Joomla!](https://downloads.joomla.org) 3.8+, 4.x, 5.x or 6.x
* [HikaShop](https://www.hikashop.com) (any edition)
* PHP 7.4+ (PHP 8 compatible)

This plugin is self-contained: it has no external Composer dependencies.
Card payments are sent directly to the
[Pin Payments charges API](https://pinpayments.com/developers/api-reference)
over HTTPS, and cards are tokenised in the browser with Pin's hosted fields.

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push the branch (`git push -u origin my-new-feature`)
5. Create a new pull request to the upstream repository

## License

MIT. See the LICENSE.txt file for details
