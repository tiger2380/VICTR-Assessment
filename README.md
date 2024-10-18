# VUMC VICTR Flagship Symfony Application Template

## Prerequisites

* Docker Desktop (or Docker + Docker Compose)
* \[Optional\] MySQL client

## Setup

### 1. Configure ports

By default, the web server will be accessible on port 8080 and the database server will be accessible on port 8306. If these ports are already in use on your machine, you can override the ports:

```sh
cp compose.override.yaml.dist compose.override.yaml
```

Edit `compose.override.yaml` to specify the port you would like to use. For example, if you want to change the web server port from 8080 to 9000, the `web` block should look like:

```yaml
services:
  web:
    ports:
      - "9000:80"
```

### 2. Start docker containers

```sh
docker compose up -d
```

The first time you run this, it will take a few minutes to download the PHP and MariaDB images and install a few PHP extensions.

### 3. Install Composer dependencies

```sh
docker compose exec web composer install
```

The application should be running at http://127.0.0.1:8080/ (or whatever port you specified in `composer.override.yaml`).

### 4. Test database and Doctrine configuration

To confirm that the database configuration is working, a "Test" entity and controller has been added.

First, run the migrations to create the test table:

```sh
docker compose exec web bin/console doctrine:migrations:migrate
```

Go to http://127.0.0.1:8080/test/ to make sure adding and deleting entities is working properly.

## Additional notes

### Web container

In addition to running composer install, you can run any other command by using `docker compose exec web`. For example, to clear the Symfony cache:

```sh
docker compose exec web bin/console clear:cache
```

To run PHPUnit:

```sh
docker compose exec web vendor/bin/phpunit
```

If you want to open a shell to the web container, run:

```sh
docker compose exec web bash
```

### Database container

This project uses the MariaDB Docker image for the database, which should already be configured properly in the Symfony application in the `.env` file.

The official MariaDB container does not include the `mysql` command line client. If you want to connect to the database from your host machine, you will need a MySQL client installed locally. The root user account is configured to work without a password. 

Host: `127.0.0.1`  
Port: `8306` (or the port you specified in `composer.override.yaml`)  
Username: `root`  
Database: `app`

For example, using the `mysql` command line client, you would connect with:

```sh
mysql -uroot -h127.0.0.1 -P8306 app
```

### Cleanup

Stop the containers:

```sh
docker compose stop
```

Stop and remove the containers:

```sh
docker compose down
```

Stop and remove the containers and remove the associated images and volumes:

```sh
docker compose down --volumes --rmi all 
```
