# Installation & Setup Guide

## Requirements

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose
- Git

## Steps

### 1. Clone the repository

```sh
git clone <your-repo-url>
cd flagship-template
```

### 2. (Optional) Configure a GitHub personal access token

The app fetches data from the GitHub API. Without a token you are limited to
60 unauthenticated requests per hour; with one you get 5 000.

Copy the example env override and add your token:

```sh
cp .env .env.local
```

Edit `.env.local` and uncomment/set:

```dotenv
GITHUB_TOKEN=ghp_yourPersonalAccessTokenHere
```

### 3. (Optional) Change default ports

The web server defaults to **port 8080** and the database to **port 8306**.
To override them:

```sh
cp compose.override.yaml.dist compose.override.yaml
```

Edit `compose.override.yaml` as needed, e.g.:

```yaml
services:
  web:
    ports:
      - "9000:80"
```

### 4. Start the Docker containers

```sh
docker compose up -d --build
```

The first run will pull the PHP and MariaDB images and install PHP extensions
— this typically takes a few minutes.

### 5. Install Composer dependencies

```sh
docker compose exec web composer install
```

### 6. Run database migrations

```sh
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

This creates both the `test` table (from the base template) and the
`github_repository` table used by this application.

### 7. Open the application

| URL | Description |
|---|---|
| http://localhost:8080/ | Home page |
| http://localhost:8080/github | Top starred PHP repositories |

## Using the application

1. Go to **http://localhost:8080/github**
2. Click **"Refresh from GitHub"** to load the top 30 most-starred public PHP
   repositories from the GitHub API into the database.
3. The table shows each project's name and star count.
4. Click any row to see full details: description, repository URL, created date,
   and last push date.
5. Click **"Refresh from GitHub"** at any time to pull the latest data.

## Useful commands

```sh
# Clear the Symfony cache
docker compose exec web php bin/console cache:clear

# Stop containers
docker compose down

# View application logs
docker compose logs -f web
```
