# Slug-Laravel

Laravel 5.2

### Require

```
    php -v >= 5.5.9
    composer
    nodejs
```

Nginx 指到`public`目录下
```
    location / {
        if (!-e $request_filename) {
            rewrite  ^(.*)$  /index.php?s=$1  last;
            break;
        }
    }
```

### Install

```
    composer create-project --prefer-dist wilon/slug-laravel your_project_name

    # db
    php artisan migrate --force

    # gulp
    cd public
    npm install && npm start
```