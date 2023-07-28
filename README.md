# Personal weather

Этот репозиторий содержит код для Symfony-приложения, которое предоставляет CRUD-операции для пользователей и возможность выгрузки данных в файл формата .xlsx.

## Требования
Перед началом работы убедитесь, что у вас установлены следующие компоненты:

- PHP версии 7.2 или выше
- Composer
- MySQL сервер

## Установка
- Склонируйте репозиторий на свой локальный компьютер:

`git clone https://github.com/example/repository.git`
- Перейдите в директорию проекта:

`cd repository`

- Установите зависимости, запустив команду:
 
`composer install`

- Создайте базу данных и настройте подключение к ней в файле .env.

- Выполните миграции, чтобы создать необходимые таблицы в базе данных:

`php bin/console doctrine:migrations:migrate`
<details>
<summary>если не получилось</summary>
CREATE TABLE `Category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

</details>  

## Использование
Страница списка пользователей

После запуска приложения вы можете открыть страницу списка пользователей, перейдя по следующему URL:

http://personal-weather/public/index.php/category/

На этой странице вы можете просмотреть список всех пользователей, добавить нового пользователя, отредактировать или удалить существующего.

## Зависимости

- Symfony 5.x
- Doctrine ORM
- PhpSpreadsheet
- Pyrrah OpenWeatherMapBundle

### Заметки
- Некоторые функции, такие как вывод погоды, еще не реализованы и требуют разработки.
