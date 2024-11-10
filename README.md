# Laravel E-commerce Order Processing System

A robust, queue-based order processing system built with Laravel, featuring product management, automated invoice generation, and comprehensive testing. The system is containerized with Docker for easy deployment and scalability.

## Features

-   Asynchronous Order Processing: Queue-based system for handling high-volume orders
-   Product Management: Stock tracking, pricing with tax and discounts
-   Automated Invoicing: Unique invoice generation for each order
-   Docker Integration: Containerized setup with PHP-FPM, Nginx, and SQLite
-   Comprehensive Testing: Unit and feature tests for critical components
-   Stock Management: Automatic stock updates with transaction safety
-   API-First Design: RESTful endpoints for order processing

## Requirements

-   Docker and Docker Compose
-   PHP 8.2+
-   Composer
-   Git

## Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd <project-directory>
```

2. Copy the environment file:

```bash
cp .env.example .env
```

3. Install dependencies:

```bash
composer install
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Create SQLite database:

```bash
touch database/database.sqlite
```

6. Run migrations and seeds:

```bash
php artisan migrate --force
php artisan db:seed --force
```

## Docker Setup

The project includes Docker configuration for easy deployment. The Docker setup includes:

-   PHP-FPM
-   Nginx
-   SQLite database
-   Queue worker

1. Build and start the containers:

```bash
cd deploy
docker compose up --build
```

2. The application will be available at:

```bash
http://localhost:8080
```

# API Endpoints

## Create Order

```json
POST /api/orders
Content-Type: application/json

{
    "products": [
        {
            "product_id": 1,
            "quantity": 2,
            "price": 100
        }
    ]
}
```

Response:

```json
{
    "success": true,
    "message": "Order has been accepted and is being processed",
    "order_reference": "order_64f3a1b7e4c8a"
}
```
