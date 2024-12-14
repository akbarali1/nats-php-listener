# Laravel NATS PHP listener

# TODO

| Done ?               | Name                                                               | Version       |
|:---------------------|:-------------------------------------------------------------------|:--------------|
| :white_large_square: | Writing documentation.                                             | In the future |
| :white_large_square: | Write a Sender for this Listener.                                  | In the future |
| :white_large_square: | Add middleware method.                                             | In the future |
| :white_large_square: | Add Docs Auth Middleware.                                          | In the future |
| :white_large_square: | Write error messages more clearly and translate them into English. | In the future |

# Install

```
composer require akbarali/nats-listener
```

After installing Nats Listener, publish its assets using the nats:install Artisan command:

```aiignore
php artisan nats:install
```

# Configuration

After publishing Nats Listener's assets, its primary configuration file will be located at `config/nats.php`. This configuration file allows you to configure the queue worker options for your application. Each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

# Deploying Nats Listener

When you're ready to deploy Horizon to your application's actual server, you should configure a process monitor to monitor the php artisan horizon command and restart it if it exits unexpectedly. Don't worry, we'll discuss how to install a process monitor below.

During your application's deployment process, you should instruct the Horizon process to terminate so that it will be restarted by your process monitor and receive your code changes:

```
php artisan nats:terminate
```