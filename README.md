## Description

Official repository for the WP VisitorFlow plugin for WordPress.

## Installation

For installation in production environments, please refer to the instructions on the plugin's [website in the WordPress plugin directory](https://wordpress.org/plugins/wp-visitorflow/#installation).

## Development

* Clone this repo.
* Run `composer install` to install PHP vendor packages.
* Run `npm install` to install Node.js packages.

## Testing

* Install WP-Tests environment:

  `./bin/install-wp-tests.sh TEST_DB_NAME DB_ACCOUNT "DB_PASSWORD" localhost latest
npm run test:watch`
* Run `npm run test` to run the tests – or `npm run test:watch` for additional watching for file changes.


