DrupalCI - Results API
======================

## Overview

An API for interacting with the Drupal 8 results site.

## Installation

Add the following to your composer.json

```
{
  "require": {
    "drupalci/results-api": "dev-master"
  }
}
```

## Usage

The following commands will set up the API

```
$api = new API();
$api->setUrl('http://example.com');
$api->setAuth('username', 'password');
```

Take a look at the class for the remaining API options.

(will documentation at a later date)
