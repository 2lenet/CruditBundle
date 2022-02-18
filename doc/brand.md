# How to customize the brand

Crudit allow you to put the brand and the realease in the footer.

In your `.env`, you have to add :

```dotenv
APP_NAME=Crudit
APP_VERSION=1.0.0
```

Edit `config/packages/twig.yaml` as following :

```yaml
twig:
    globals:
        app_name: '%env(APP_NAME)%'
        app_version: '%env(APP_VERSION)%'
```

To save your variables in differents environment, you sould add in your `Dockerfile` :

```dockerfile
ENV APP_NAME="My app name"
ENV APP_VERSION="1.2.3"
```