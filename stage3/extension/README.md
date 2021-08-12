Usage
==========
```bash
sudo apt install libsqlite3-dev
phpize7.2 # or the appropriate phpize
./configure
make
```

The add the following lines to php.ini for the desired SAPI:
```
extension="/home/vagrant/php-src/ext/seccomp/modules/seccomp.so"
seccomp.enable = 1
seccomp.app_base="/home/vagrant/wordpress"
seccomp.db_path="/home/vagrant/phpdb.sqlite"
```
Modify the `extension` key to point to the path of seccomp.so(in the directory
you cloned the repo to)
Modify `app_base`, and `db_path` to reflect the root of the web app(no trailing
slash), and the path to the profile db built with the php-api-deps tools

Logging
==========
In order to log seccomp violations along with call stack info:
 * Modify /etc/php/7.2/fpm/pool.d/www.conf and uncomment(or add)
`php_admin_value[error_log] = /var/log/fpm-php.www.log`
 * Make sure you create /var/log/fpm-php.www.log and make it writable by the
	 webserver user
 * By default this will print the file and line that triggered the fault. To
	 output a complete callstack, simply install xdebug
