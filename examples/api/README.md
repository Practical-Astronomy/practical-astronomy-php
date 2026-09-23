# Simple REST Service for Practical Astronomy (PHP)

This project provides a simple implementation of a REST interface to the Practical Astronomy single-file PHP library.

## Setup

You'll need Composer.  If you don't already have it, you can install it from your package manager, or from [here](https://getcomposer.org/).

The service uses the Flight PHP framework.  Install dependencies with this:

```bash
make depend
```

or, directly:

```bash
composer install
```

## Call the API

Run the service:

```bash
make serve
```

or, directly:

```bash
php -S localhost:8000
```

Once the service is running, you can call endpoints using the definitions in `call/test.http`.
