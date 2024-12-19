# Laravel NATS PHP Listener

# TODO

| Done ?                   | Name                                                               | Version       |
|:-------------------------|:-------------------------------------------------------------------|:--------------|
| :white_check_mark:       | Add command `nats:pause` and `nats:continue`.                      | 0.1.5         |
| :white_check_mark:       | Write error messages more clearly and translate them into English. | 0.1.7         |
| :hourglass_flowing_sand: | Write a Sender for this Listener.                                  | Pending       |
| :white_large_square:     | Writing documentation.                                             | In the future |
| :white_large_square:     | Add middleware method.                                             | In the future |
| :white_large_square:     | Add Docs Auth Middleware.                                          | In the future |

# Install

```
composer require akbarali/nats-listener
```

After installing Nats Listener, publish its assets using the `nats:install` Artisan command:

```aiignore
php artisan nats:install
```

# Configuration

After publishing Nats Listener's assets, its primary configuration file will be located at `config/nats.php`.
This configuration file allows you to configure the queue worker options for your application.
Each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

# Running Nats Listener

Once you have configured your supervisors and workers in your application's `config/nats.php` configuration file, you may start Nats Listener using the `nats:listener` Artisan command.
This single command will start all the configured worker processes for the current environment:

```aiignore
php artisan nats:listener
```

You may pause the Nats Listener process and instruct it to continue processing jobs using the `nats:pause` and `nats:continue` Artisan commands:

```
php artisan nats:pause
```

```aiignore
php artisan nats:continue
```

# Deploying Nats Listener

When you're ready to deploy Nats Listener to your application's actual server, you should configure a process monitor to monitor the php artisan Nats Listener command and restart it if it exits unexpectedly.
Don't worry, we'll discuss how to install a process monitor below.
During your application's deployment process, you should instruct the Nats Listener process to terminate so that it will be restarted by your process monitor and receive your code changes:

```
php artisan nats:terminate
```

# Supervisor Configuration

Supervisor configuration files are typically stored within your server's `/etc/supervisor/conf.d` directory.
Within this directory, you may create any number of configuration files that instruct supervisor how your processes should be monitored.
For example, let's create a `nats.conf` file that starts and monitors a nats listener process:

```
[program:nats_listener]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan nats:listener
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
user=forge
redirect_stderr=true
stdout_logfile=/var/www/supervisor/nats_listener_queue.log
```

# Emotional Damage

I originally wrote this package because RabbitMQ was slow and had a lot of issues with our entire project.
I thought NATS would be faster and more reliable.
But in the end, it turned out that RabbitMQ is 25% faster.

![alt text](/art/emotional-damage.gif)
