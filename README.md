# Cloud Native Utilities for Laravel

This library contains a collection of resources we use at Jobilla for our Laravel
microservices. It's aimed at minimising the friction of setting up a new service
by providing sensible defaults from the get-go.

## What you get out of the box

- standardised metrics output in Prometheus format on `/metrics`
- JSON-formatted logs with minimal setup

## Installation

1. `composer require jobilla/cloud-native-laravel`
1. `php artisan vendor:publish --tag=cloud-native-config` (note that this will also publish
   a `logging.php` that overwrites the default Laravel logging config)
1. Optionally, run `php artisan vendor:publish --tag=dockerfile` to publish a `Dockerfile`
   to your project root
