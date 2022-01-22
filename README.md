# Renick.Matomo

Just a simple OctoberCMS plugin for embedding matomo analytics. These features are provided by the initial release of 
this plugin:

- [x] store matomo api key as well as page id
- [x] middleware for matomo server-side tracking
- [x] component for matomo javascript integration
- [x] component for matomo tag manager javascript integration
- [x] integration of analytic reports within the OctoberCMS backend

To install this plugin, you can just run the following command:
```bash
php artisan plugin:install Renick.Matomo --from=git@github.com:renickbuettner/matomo-plugin.git
```

Feel free to ask, raise ideas, mind bugs or contribute on the Github repository. Please create a new issue or pull 
request.

---

Future ideas: 

- [] measure impact of accessing plugins config from database on site performance - may add support for in-memory cache? 
- [] add more report widgets (api is prepared...)
