# Simple POC about France Travail API

## How to run the project:

1. Clone the project
2. Duplicate the `/backend/.env.dev` file and rename it to `/backend/.env.dev.local`
3. Replace `FRANCE_TRAVAIL_CLIENT_ID` and `FRANCE_TRAVAIL_CLIENT_SECRET` in the `/backend/.env.dev.local` file with your own credentials
4. Run `docker-compose up -d --build` in the root directory of the project
5. Run `docker-compose exec backend php bin/console doctrine:migrations:migrate`
6. Go to [localhost:8080](http://localhost:8080/) to see the project
7. Run `docker-compose down` to stop the project


# Known issues

- If you ever have a missing bundle after shutting down the project and restarting it, run `docker-compose exec backend composer install` to install the missing dependencies
- If you have any issue about migration, remove the `migrations` folder in the `backend` folder, shut down your containers and remove volumes. Then start up again the project and run `docker-compose exec backend php bin/console make:migration` before step 5


Docker template modified from [here](https://github.com/LewisSama/angular-symfony-nginx-mysql-docker-template)