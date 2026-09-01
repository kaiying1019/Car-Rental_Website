# GoCar Rental Website

GoCar is a PHP and MySQL car rental website for browsing vehicles, managing a rental cart, and creating bookings.

## Features

- Browse and search the available vehicle fleet
- Filter cars by transmission, fuel type, seats, and price
- View car details, images, specifications, and reviews
- Register and log in as a customer
- Add cars to a persistent cart
- Select individual cars for checkout
- Change rental days and see prices update in real time
- Calculate rental price, price per day, and refundable deposit
- Choose pick-up and drop-off branches
- Submit driving licence and payment method details
- View booking confirmation details
- Submit car reviews and contact inquiries
- Admin management for cars and fleet status

## Requirements

- Windows, MacOS
- WAMP Server or another Apache/PHP/MySQL environment
- PHP with the `mysqli` extension enabled
- MySQL
- A modern web browser (Chrome, Firefox, Edge)

## Installation With WAMP

1. Copy the project into WAMP's web directory:

   ```text
   C:\wamp64\www\CarRentalWebsite
   ```

2. Start the Apache and MySQL services in WAMP.

3. Open phpMyAdmin at:

   http://localhost/phpmyadmin
   

4. Import [`database.sql`]. The script creates the `carrentalwebsite` database and its tables.

5. Confirm the database settings in [`includes/config.php`](includes/config.php):

   ```php
   DB_HOST = localhost
   DB_PORT = 3306
   DB_USER = root
   DB_PASS =
   DB_NAME = carrentalwebsite
   ```

6. Open the application:

   http://localhost/CarRentalWebsite/
   
## Verifying the Installation
After completing the steps above, the GoCar homepage should load with a 
list of available vehicles. If the page shows a database connection error, 
double-check the values in `includes/config.php` against your MySQL setup.

## Admin Account

The database includes this administrator account:

Email: admin@gocar.com
Password: password

Change or remove this seeded credential before using the application in a shared or production environment.

