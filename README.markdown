Provide a command line to clear opcache cache from the console.

The problem with opcache is that it's impossible to clear it from command line.
Because even if you enable opcache for PHP CLI, it's a different instance than,
say, your Apache PHP or PHP-CGI opcache instance.

The trick here is to create a file in the web dir, execute it through HTTP,
then remove it.

Installation
============

  1. Add it to your composer.json:

      ```json
      {
          "require": {
              "sixdays/opcache-bundle": "dev-master"
          }
      }
      ```

     or:

      ```sh
          composer require sixdays/opcache-bundle
          composer update sixdays/opcache-bundle
      ```

  2. Add this bundle to your application kernel:

          // app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Sixdays\OpcacheBundle\SixdaysOpcacheBundle(),
                  // ...
              );
          }

  3. Configure `sixdays_opcache` service:

          # app/config/config.yml
          sixdays_opcache:
              host_ip:    127.0.0.1:80
              host_name:  example.com
              web_dir:    %kernel.root_dir%/../web


Usage
=====

Clear all opcache cache:

          $ php app/console opcache:clear


Capifony usage
==============

To automatically clear apc cache after each capifony deploy you can define a custom task

```ruby
namespace :symfony do
  desc "Clear opcache cache"
  task :clear_opcache do
    capifony_pretty_print "--> Clear opcache cache"
    run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} opcache:clear --env=#{symfony_env_prod}'"
    capifony_puts_ok
  end
end
```

and add this hook

```ruby
# apc
after "deploy", "symfony:clear_opcache"
```

Nginx configuration
===================

If you are using nginx and limiting PHP scripts that you are passing to fpm you need to allow 'apc' prefixed php files. Otherwise your web server will return the requested PHP file as text and the system won't be able to clear the apc cache.

Example configuration:
```
# Your virtual host
server {
  ...
  location ~ ^/(app|app_dev|opcache-.*)\.php(/|$) { { # This will allow opcache (opcache-{MD5HASH}.php) files to be processed by fpm
    fastcgi_pass                127.0.0.1:9000;
    ...
``` 