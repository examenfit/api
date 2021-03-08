# ExamenFit API

## System requirements
1. PHP 7.4
2. MariaDB 10.5

## Other software
1. Install pdftotext
```
sudo apt-get install poppler-utils
```

2. Install Imagemagick
```
sudo apt install imagemagick
```

3. Install KaTeX
```
npx katex
```

4. Install Composer
```
sudo apt-get install composer
```


## Installation
1. Install Composer packages
```
composer install
```

2. Create `.env` file
```
cp .env.example .env
```

3. Check configuration
- For local development, the default front-end URL (Sanctum & CORS) is already set.
- Make sure the right `DB_` information is set

4. Generate secret key
```
php artisan key:generate
```

5. Link storage path
```
php artisan storage:link
```

6. Run server
```
php artisan serve
```
