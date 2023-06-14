# How to customize the brand

Crudit allow you to put the brand and the realease in the footer.

In your `.env`, you have to add :

```dotenv
APP_NAME=Crudit
APP_VERSION=dev
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
ARG app_version="dev"
ENV APP_VERSION=$app_version
```

To set automatically the tag version, you must add the following lines in your `.gitlab-ci.yml` :

```yaml
script:
    - docker build -t my-image-tag --build-arg app_version=$CI_COMMIT_TAG
only:
    - tags
```
