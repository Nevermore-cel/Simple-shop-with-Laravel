# Простейший Интернет-Магазин на Laravel

Этот проект представляет собой реализацию простейшего интернет-магазина на PHP с использованием фреймворка Laravel 11 и базы данных PostgreSQL. Проект реализован с использованием REST API.

## Технологии

*   PHP 8+
*   Laravel 11+
*   PostgreSQL 16+
*   Laravel Sanctum (для аутентификации)
*   Postman (для проверки запросов)

## Установка и Настройка

1.  **Клонирование репозитория:**

    ```bash
    git clone https://github.com/Nevermore-cel/Simple-shop-with-Laravel.git
    cd shop 
    ```

2.  **Установка зависимостей:**

    ```bash
    composer install
    ```

3.  **Настройка .env:**

    *   Скопируйте файл `.env.example` в `.env`:

        ```bash
        cp .env.example .env
        ```

    *   Отредактируйте файл `.env`, указав следующие параметры:
        *   `APP_NAME`: Название вашего приложения (например, Shop).
        *   `APP_ENV`:  Окружение (`local`, `production`).
        *   `APP_KEY`:  Сгенерируйте ключ приложения: `php artisan key:generate`
        *   `APP_DEBUG`:  `true` для разработки, `false` для production.
        *   `APP_URL`:  URL вашего приложения (например, `http://localhost`).
        *   `DB_CONNECTION`: `pgsql`.
        *   `DB_HOST`: Адрес хоста вашей базы данных (например, `127.0.0.1`).
        *   `DB_PORT`: Порт базы данных (обычно `5432`).
        *   `DB_DATABASE`: Название вашей базы данных (например, `shop`).
        *   `DB_USERNAME`: Имя пользователя для доступа к базе данных.
        *   `DB_PASSWORD`: Пароль пользователя для доступа к базе данных.

4.  **Миграции базы данных:**

    ```bash
    php artisan migrate
    ```

5.  **(Необязательно) Заполнение базы данных тестовыми данными:**

    ```bash
    php artisan db:seed
    ```
    (Это заполнит базу данных категориями, товарами, пользователями и администратором).

6.  **Установка Sanctum (если еще не сделано):**

    ```bash
    composer require laravel/sanctum
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    php artisan migrate
    ```

## API Endpoints

Этот проект предоставляет REST API для доступа к данным магазина. Все endpoints используют формат JSON для запросов и ответов. Нет Клиентской части приложения, для проверки работы запросов использовать Postman или аналоги.

### Authentication (Аутентификация)

*   `POST /register`:  Регистрация нового пользователя.
    *   **Параметры (в теле запроса, JSON):**
        *   `name` (string, required) - Имя пользователя.
        *   `email` (string, required, unique) - Адрес электронной почты.
        *   `password` (string, required, min:8) - Пароль пользователя.
    *   **Ответ (201 Created):**  Данные пользователя, токен доступа и тип токена.
*   `POST /login`:  Авторизация пользователя.
    *   **Параметры (в теле запроса, JSON):**
        *   `email` (string, required) - Адрес электронной почты.
        *   `password` (string, required) - Пароль пользователя.
    *   **Ответ (200 OK):** Данные пользователя, токен доступа и тип токена.
*   `POST /logout`:  Выход из системы (требует аутентификации).
    *   **Заголовки:** `Authorization: Bearer <Ваш токен доступа>`
    *   **Ответ (200 OK):** Сообщение об успешном выходе.

### Public Endpoints (Публичные endpoints)

*   `GET /categories`:  Получение списка категорий товаров.
    *   **Параметры (Query Parameters):**
        *   `per_page` (integer, optional, default: 10) - Количество элементов на странице (для пагинации).
    *   **Ответ (200 OK):**  Список категорий (с пагинацией), включая id, name, и количество товаров в каждой категории.
*   `GET /categories/{category}`: Получение информации о конкретной категории.
    *   `{category}`:  ID категории.
    *   **Ответ (200 OK):**  Данные категории.
*   `GET /products`:  Получение списка товаров.
    *   **Параметры (Query Parameters):**
        *   `search` (string, optional) - Поиск по названию товара (подстрока).
        *   `category_id` (integer, optional) - Фильтрация по ID категории.
        *   `sort` (string, optional) - Сортировка по полю (например, `name`, `price`).
        *   `direction` (string, optional, default: `asc`) - Направление сортировки (`asc` или `desc`).
        *   `per_page` (integer, optional, default: 10) - Количество элементов на странице (для пагинации).
    *   **Ответ (200 OK):** Список товаров (с пагинацией), включая id, category_id, name, price, quantity, category_name.
*   `GET /products/{product}`:  Получение информации о конкретном товаре.
    *   `{product}`: ID товара.
    *   **Ответ (200 OK):**  Данные товара.

### Customer Endpoints (Для покупателей, требуется аутентификация)

*   `GET /profile`:  Получение информации о своем профиле.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Ответ (200 OK):**  Данные пользователя.
*   `GET /orders`:  Получение списка своих заказов.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Ответ (200 OK):**  Список заказов пользователя.
*   `POST /orders`:  Создание нового заказа.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Параметры (в теле запроса, JSON):**
        *   `products` (array, required) -  Массив объектов, описывающих товары в заказе.
            *   `id` (integer, required, exists:products,id) - ID товара.
            *   `quantity` (integer, required, min:1) - Количество товара.
    *   **Ответ (201 Created):**  Данные созданного заказа.

### Administrator Endpoints (Для администраторов, требуется аутентификация и роль `admin`)

*   `GET /users`:  Получение списка всех пользователей.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Ответ (200 OK):**  Список всех пользователей.
*   `GET /orders/all`:  Получение списка всех заказов.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Ответ (200 OK):**  Список всех заказов.
*   `PUT /orders/{order}/status`:  Изменение статуса заказа.
    *   `{order}`: ID заказа.
    *   **Заголовки:** `Authorization: Bearer <YOUR_ACCESS_TOKEN>`
    *   **Параметры (в теле запроса, JSON):**
        *   `status` (string, required, in:new,confirmed,cancelled) - Новый статус заказа.
    *   **Ответ (200 OK):**  Обновленные данные заказа.

## Логирование

*   Логи ошибок записываются в файл `storage/logs/laravel.log`.
