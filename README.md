## Description

Official repository of the WP VisitorFlow plugin for WordPress.

WP VisitorFlow provides detailed information about visitors to your website. With WP VisitorFlow you can see at a glance how visitors interact with your WordPress website: All paths taken by your visitors are summarized in a comprehensive flowchart:

![alt text](https://ps.w.org/wp-visitorflow/assets/screenshot-1.png?rev=1656985)

## Installation

For installation in production environments, please refer to the instructions on the plugin's [website in the WordPress plugin directory](https://wordpress.org/plugins/wp-visitorflow/#installation).

## Development

* Clone this repo.
* Run `composer install` to install PHP vendor packages.
* Run `npm install` to install Node.js packages.

## Testing

* Install WP-Tests environment:

  `./bin/install-wp-tests.sh TEST_DB_NAME DB_ACCOUNT "DB_PASSWORD" localhost latest`

* Run `npm run test` to run the tests – or `npm run test:watch` for additional watching for file changes.


